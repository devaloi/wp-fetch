<?php
/**
 * Error handler — logging and fallback logic.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch;

/**
 * Manages error logging and retrieval for API sources.
 */
class Error_Handler {

	private const OPTION_KEY = 'wp_fetch_errors';
	private const MAX_ERRORS = 50;

	/**
	 * Log an error for a source.
	 *
	 * @param string $source_name Source slug.
	 * @param string $message     Error message.
	 */
	public function log( string $source_name, string $message ): void {
		$errors = get_option( self::OPTION_KEY, array() );

		if ( ! isset( $errors[ $source_name ] ) ) {
			$errors[ $source_name ] = array();
		}

		array_unshift(
			$errors[ $source_name ],
			array(
				'message' => sanitize_text_field( $message ),
				'time'    => current_time( 'mysql' ),
			)
		);

		$errors[ $source_name ] = array_slice( $errors[ $source_name ], 0, self::MAX_ERRORS );
		update_option( self::OPTION_KEY, $errors );
	}

	/**
	 * Get errors for a source.
	 *
	 * @param string $source_name Source slug.
	 * @return array List of error entries.
	 */
	public function get_errors( string $source_name ): array {
		$errors = get_option( self::OPTION_KEY, array() );
		return $errors[ $source_name ] ?? array();
	}

	/**
	 * Count errors in the last 24 hours for a source.
	 *
	 * @param string $source_name Source slug.
	 */
	public function count_recent( string $source_name ): int {
		$errors   = $this->get_errors( $source_name );
		$cutoff   = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		$count    = 0;

		foreach ( $errors as $entry ) {
			if ( $entry['time'] >= $cutoff ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Clear errors for a source.
	 *
	 * @param string $source_name Source slug.
	 */
	public function clear( string $source_name ): void {
		$errors = get_option( self::OPTION_KEY, array() );
		unset( $errors[ $source_name ] );
		update_option( self::OPTION_KEY, $errors );
	}
}
