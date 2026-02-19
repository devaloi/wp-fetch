<?php
/**
 * Tests for the Error_Handler class.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch\Tests;

use PHPUnit\Framework\TestCase;
use WP_Fetch\Error_Handler;

class ErrorHandlerTest extends TestCase {

	private Error_Handler $handler;

	protected function setUp(): void {
		\WP_Test_Store::reset();
		$this->handler = new Error_Handler();
	}

	public function test_log_and_retrieve_errors(): void {
		$this->handler->log( 'weather', 'Connection timeout' );
		$errors = $this->handler->get_errors( 'weather' );

		$this->assertCount( 1, $errors );
		$this->assertSame( 'Connection timeout', $errors[0]['message'] );
	}

	public function test_count_recent_errors(): void {
		$this->handler->log( 'api', 'Error 1' );
		$this->handler->log( 'api', 'Error 2' );

		$this->assertSame( 2, $this->handler->count_recent( 'api' ) );
	}

	public function test_clear_errors(): void {
		$this->handler->log( 'test', 'Error' );
		$this->handler->clear( 'test' );
		$this->assertEmpty( $this->handler->get_errors( 'test' ) );
	}

	public function test_get_errors_empty_for_unknown_source(): void {
		$this->assertEmpty( $this->handler->get_errors( 'unknown' ) );
	}

	public function test_max_errors_enforced(): void {
		for ( $i = 0; $i < 60; $i++ ) {
			$this->handler->log( 'flood', 'Error ' . $i );
		}
		$this->assertCount( 50, $this->handler->get_errors( 'flood' ) );
	}
}
