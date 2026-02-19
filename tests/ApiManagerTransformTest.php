<?php
/**
 * Tests for the API_Manager transform logic.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch\Tests;

use PHPUnit\Framework\TestCase;
use WP_Fetch\API_Manager;

class ApiManagerTransformTest extends TestCase {

	public function test_apply_transform_extracts_nested_data(): void {
		$data = array(
			'data' => array(
				'results' => array( 'a', 'b', 'c' ),
			),
		);

		$result = API_Manager::apply_transform( $data, 'data.results' );
		$this->assertSame( array( 'a', 'b', 'c' ), $result );
	}

	public function test_apply_transform_returns_original_on_invalid_path(): void {
		$data = array( 'foo' => 'bar' );
		$result = API_Manager::apply_transform( $data, 'invalid.path' );
		$this->assertSame( $data, $result );
	}

	public function test_apply_transform_single_level(): void {
		$data = array( 'items' => array( 1, 2, 3 ) );
		$result = API_Manager::apply_transform( $data, 'items' );
		$this->assertSame( array( 1, 2, 3 ), $result );
	}

	public function test_apply_transform_returns_scalar(): void {
		$data = array( 'meta' => array( 'count' => 42 ) );
		$result = API_Manager::apply_transform( $data, 'meta.count' );
		$this->assertSame( 42, $result );
	}
}
