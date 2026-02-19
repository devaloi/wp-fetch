<?php
/**
 * Transient cache wrapper with stale-data fallback.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch;

/**
 * Wraps the WordPress Transients API with stale-data support.
 */
class Cache {

	private const PREFIX       = 'wp_fetch_';
	private const STALE_PREFIX = 'wp_fetch_stale_';
	private const STALE_TTL    = DAY_IN_SECONDS;

	/**
	 * Get cached data.
	 *
	 * @param string $key Cache key.
	 * @return mixed|null Cached data or null on miss.
	 */
	public function get( string $key ): mixed {
		$data = get_transient( self::PREFIX . $key );
		return ( false === $data ) ? null : $data;
	}

	/**
	 * Store data in cache.
	 *
	 * @param string $key  Cache key.
	 * @param mixed  $data Data to cache.
	 * @param int    $ttl  Time-to-live in seconds.
	 */
	public function set( string $key, mixed $data, int $ttl = 300 ): bool {
		set_transient( self::STALE_PREFIX . $key, $data, self::STALE_TTL );
		return set_transient( self::PREFIX . $key, $data, $ttl );
	}

	/**
	 * Delete cached data.
	 *
	 * @param string $key Cache key.
	 */
	public function delete( string $key ): bool {
		delete_transient( self::STALE_PREFIX . $key );
		return delete_transient( self::PREFIX . $key );
	}

	/**
	 * Get stale (expired primary but still in long-TTL backup) data.
	 *
	 * @param string $key Cache key.
	 * @return mixed|null Stale data or null.
	 */
	public function get_stale( string $key ): mixed {
		$data = get_transient( self::STALE_PREFIX . $key );
		return ( false === $data ) ? null : $data;
	}
}
