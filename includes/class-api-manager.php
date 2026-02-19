<?php
/**
 * API Manager — orchestrates fetching from external sources.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch;

/**
 * Handles HTTP requests to configured API sources.
 */
class API_Manager {

	private const TIMEOUT = 15;

	/**
	 * Fetch data from a named source (direct HTTP, no caching layer).
	 *
	 * @param string $source_name Source slug.
	 * @return array{success: bool, data: mixed, status_code: int, error: string}
	 */
	public function fetch( string $source_name ): array {
		$source = API_Source::get( $source_name );
		if ( null === $source ) {
			return self::error_response(
				/* translators: %s: source name */
				sprintf( __( 'Source "%s" not found.', 'wp-fetch' ), $source_name )
			);
		}

		return $this->fetch_from_source( $source );
	}

	/**
	 * Perform the HTTP request for a given source object.
	 *
	 * @param API_Source $source The source to fetch.
	 * @return array{success: bool, data: mixed, status_code: int, error: string}
	 */
	public function fetch_from_source( API_Source $source ): array {
		$args = $this->build_request_args( $source );

		if ( 'POST' === $source->get_method() ) {
			$response = wp_remote_post( $source->get_url(), $args );
		} else {
			$response = wp_remote_get( $source->get_url(), $args );
		}

		if ( is_wp_error( $response ) ) {
			return self::error_response( $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			return self::error_response(
				/* translators: %d: HTTP status code */
				sprintf( __( 'HTTP %d error.', 'wp-fetch' ), $status_code ),
				$status_code
			);
		}

		$data = json_decode( $body, true );
		if ( null === $data && '' !== $body ) {
			$data = $body;
		}

		$transform = $source->get_transform();
		if ( ! empty( $transform ) && is_array( $data ) ) {
			$data = self::apply_transform( $data, $transform );
		}

		return array(
			'success'     => true,
			'data'        => $data,
			'status_code' => $status_code,
			'error'       => '',
		);
	}

	/**
	 * Build wp_remote_* request arguments.
	 *
	 * @param API_Source $source API source.
	 */
	private function build_request_args( API_Source $source ): array {
		$headers = $source->get_headers();

		switch ( $source->get_auth_type() ) {
			case 'bearer':
				$headers['Authorization'] = 'Bearer ' . $source->get_auth_value();
				break;
			case 'api_key':
				$headers['X-API-Key'] = $source->get_auth_value();
				break;
		}

		return array(
			'timeout' => self::TIMEOUT,
			'headers' => $headers,
		);
	}

	/**
	 * Apply dot-notation transform to extract nested data.
	 *
	 * @param array  $data Raw data.
	 * @param string $path Dot-notation path (e.g., "data.results").
	 * @return mixed Extracted data or original if path not found.
	 */
	public static function apply_transform( array $data, string $path ): mixed {
		$keys    = explode( '.', $path );
		$current = $data;

		foreach ( $keys as $key ) {
			if ( ! is_array( $current ) || ! array_key_exists( $key, $current ) ) {
				return $data;
			}
			$current = $current[ $key ];
		}

		return $current;
	}

	/**
	 * Build an error response array.
	 *
	 * @param string $message Error message.
	 * @param int    $status_code HTTP status code.
	 */
	private static function error_response( string $message, int $status_code = 0 ): array {
		return array(
			'success'     => false,
			'data'        => null,
			'status_code' => $status_code,
			'error'       => $message,
		);
	}
}
