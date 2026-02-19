<?php
/**
 * Tests for the Cache class.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch\Tests;

use PHPUnit\Framework\TestCase;
use WP_Fetch\Cache;

class CacheTest extends TestCase {

	private Cache $cache;

	protected function setUp(): void {
		\WP_Test_Store::reset();
		$this->cache = new Cache();
	}

	public function test_get_returns_null_on_miss(): void {
		$this->assertNull( $this->cache->get( 'nonexistent' ) );
	}

	public function test_set_and_get(): void {
		$data = array( 'temp' => 72 );
		$this->cache->set( 'weather', $data, 300 );
		$this->assertSame( $data, $this->cache->get( 'weather' ) );
	}

	public function test_delete_removes_data(): void {
		$this->cache->set( 'key', 'value', 300 );
		$this->cache->delete( 'key' );
		$this->assertNull( $this->cache->get( 'key' ) );
	}

	public function test_stale_data_available_after_primary_expires(): void {
		$data = array( 'id' => 1 );
		$this->cache->set( 'test', $data, 1 );

		// Manually expire primary transient.
		\WP_Test_Store::$transients['wp_fetch_test']['expiry'] = time() - 1;

		$this->assertNull( $this->cache->get( 'test' ) );
		$this->assertSame( $data, $this->cache->get_stale( 'test' ) );
	}

	public function test_get_stale_returns_null_when_no_data(): void {
		$this->assertNull( $this->cache->get_stale( 'missing' ) );
	}
}
