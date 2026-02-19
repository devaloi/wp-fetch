<?php
/**
 * Rate limiter for outbound API requests.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch;

/**
 * Sliding-window rate limiter using WordPress transients.
 */
class Rate_Limiter {

	private const PREFIX       = 'wp_fetch_rl_';
	private const WINDOW       = 60;
	private const DEFAULT_LIMIT = 30;

	/**
	 * Check whether a request is allowed for the given source.
	 *
	 * @param string $source_name Source slug.
	 * @return bool True if under the limit.
	 */
	public function allow( string $source_name ): bool {
		$count = $this->get_count( $source_name );
		$limit = $this->get_limit();
		return $count < $limit;
	}

	/**
	 * Record a request for the given source.
	 *
	 * @param string $source_name Source slug.
	 */
	public function record( string $source_name ): void {
		$key   = self::PREFIX . $source_name;
		$count = (int) get_transient( $key );

		if ( 0 === $count ) {
			set_transient( $key, 1, self::WINDOW );
		} else {
			set_transient( $key, $count + 1, self::WINDOW );
		}
	}

	/**
	 * Get current request count for a source.
	 *
	 * @param string $source_name Source slug.
	 */
	public function get_count( string $source_name ): int {
		$count = get_transient( self::PREFIX . $source_name );
		return ( false === $count ) ? 0 : (int) $count;
	}

	/**
	 * Get the configured rate limit (requests per minute).
	 */
	public function get_limit(): int {
		return (int) get_option( 'wp_fetch_rate_limit', self::DEFAULT_LIMIT );
	}
}
