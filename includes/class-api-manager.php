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

	private Cache $cache;
	private Rate_Limiter $rate_limiter;
	private Error_Handler $error_handler;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->cache         = new Cache();
		$this->rate_limiter  = new Rate_Limiter();
		$this->error_handler = new Error_Handler();
	}

	/**
	 * Fetch data from a named source with caching, rate limiting, and fallback.
	 *
	 * @param string $source_name Source slug.
	 * @param bool   $force_refresh Skip cache and re-fetch.
	 * @return array{success: bool, data: mixed, status_code: int, error: string, cached: bool}
	 */
	public function fetch( string $source_name, bool $force_refresh = false ): array {
		$source = API_Source::get( $source_name );
		if ( null === $source ) {
			return self::error_response(
				/* translators: %s: source name */
				sprintf( __( 'Source "%s" not found.', 'wp-fetch' ), $source_name )
			);
		}

		return $this->fetch_with_cache( $source, $force_refresh );
	}

	/**
	 * Integrated flow: cache check → rate limit → HTTP fetch → cache store → fallback.
	 *
	 * @param API_Source $source        The source to fetch.
	 * @param bool      $force_refresh  Skip reading from cache.
	 * @return array{success: bool, data: mixed, status_code: int, error: string, cached: bool}
	 */
	public function fetch_with_cache( API_Source $source, bool $force_refresh = false ): array {
		$cache_key = 'source_' . $source->get_name();

		if ( ! $force_refresh ) {
			$cached = $this->cache->get( $cache_key );
			if ( null !== $cached ) {
				return array(
					'success'     => true,
					'data'        => $cached,
					'status_code' => 200,
					'error'       => '',
					'cached'      => true,
				);
			}
		}

		if ( ! $this->rate_limiter->allow( $source->get_name() ) ) {
			$stale = $this->cache->get_stale( $cache_key );
			if ( null !== $stale ) {
				return array(
					'success'     => true,
					'data'        => $stale,
					'status_code' => 200,
					'error'       => '',
					'cached'      => true,
				);
			}
			$this->error_handler->log( $source->get_name(), __( 'Rate limit exceeded.', 'wp-fetch' ) );
			return self::error_response( __( 'Rate limit exceeded.', 'wp-fetch' ) );
		}

		$this->rate_limiter->record( $source->get_name() );
		$result = $this->fetch_from_source( $source );

		if ( $result['success'] ) {
			$ttl = $source->get_cache_ttl();
			if ( $ttl > 0 ) {
				$this->cache->set( $cache_key, $result['data'], $ttl );
			}
			$result['cached'] = false;
			return $result;
		}

		$this->error_handler->log( $source->get_name(), $result['error'] );

		$stale = $this->cache->get_stale( $cache_key );
		if ( null !== $stale ) {
			return array(
				'success'     => true,
				'data'        => $stale,
				'status_code' => 200,
				'error'       => '',
				'cached'      => true,
			);
		}

		$fallback = $source->get_fallback();
		if ( ! empty( $fallback ) ) {
			return array(
				'success'     => false,
				'data'        => $fallback,
				'status_code' => 0,
				'error'       => $result['error'],
				'cached'      => false,
			);
		}

		$result['cached'] = false;
		return $result;
	}

	/**
	 * Get the error handler instance.
	 */
	public function get_error_handler(): Error_Handler {
		return $this->error_handler;
	}

	/**
	 * Get the cache instance.
	 */
	public function get_cache(): Cache {
		return $this->cache;
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
