<?php
/**
 * Tests for the Rate_Limiter class.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch\Tests;

use PHPUnit\Framework\TestCase;
use WP_Fetch\Rate_Limiter;

class RateLimiterTest extends TestCase {

	private Rate_Limiter $limiter;

	protected function setUp(): void {
		\WP_Test_Store::reset();
		\WP_Test_Store::$options['wp_fetch_rate_limit'] = 3;
		$this->limiter = new Rate_Limiter();
	}

	public function test_allows_under_limit(): void {
		$this->assertTrue( $this->limiter->allow( 'test-source' ) );
	}

	public function test_blocks_when_limit_exceeded(): void {
		for ( $i = 0; $i < 3; $i++ ) {
			$this->limiter->record( 'test-source' );
		}
		$this->assertFalse( $this->limiter->allow( 'test-source' ) );
	}

	public function test_count_increments(): void {
		$this->assertSame( 0, $this->limiter->get_count( 'test-source' ) );
		$this->limiter->record( 'test-source' );
		$this->assertSame( 1, $this->limiter->get_count( 'test-source' ) );
		$this->limiter->record( 'test-source' );
		$this->assertSame( 2, $this->limiter->get_count( 'test-source' ) );
	}

	public function test_window_reset(): void {
		$this->limiter->record( 'test-source' );
		$this->limiter->record( 'test-source' );
		$this->limiter->record( 'test-source' );

		// Simulate window expiry.
		\WP_Test_Store::$transients['wp_fetch_rl_test-source']['expiry'] = time() - 1;

		$this->assertTrue( $this->limiter->allow( 'test-source' ) );
		$this->assertSame( 0, $this->limiter->get_count( 'test-source' ) );
	}
}
