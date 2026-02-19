<?php
/**
 * Tests for the API_Source class.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch\Tests;

use PHPUnit\Framework\TestCase;
use WP_Fetch\API_Source;

class ApiSourceTest extends TestCase {

	protected function setUp(): void {
		\WP_Test_Store::reset();
	}

	public function test_create_and_retrieve_source(): void {
		$source = new API_Source(
			array(
				'name'      => 'weather',
				'url'       => 'https://api.example.com/weather',
				'method'    => 'GET',
				'auth_type' => 'none',
				'cache_ttl' => 600,
			)
		);

		$this->assertTrue( $source->save() );

		$loaded = API_Source::get( 'weather' );
		$this->assertNotNull( $loaded );
		$this->assertSame( 'weather', $loaded->get_name() );
		$this->assertSame( 'https://api.example.com/weather', $loaded->get_url() );
		$this->assertSame( 600, $loaded->get_cache_ttl() );
	}

	public function test_list_all_returns_all_sources(): void {
		$s1 = new API_Source( array( 'name' => 'alpha', 'url' => 'https://a.com' ) );
		$s2 = new API_Source( array( 'name' => 'beta', 'url' => 'https://b.com' ) );
		$s1->save();
		$s2->save();

		$all = API_Source::list_all();
		$this->assertCount( 2, $all );
	}

	public function test_delete_source(): void {
		$source = new API_Source( array( 'name' => 'temp', 'url' => 'https://t.com' ) );
		$source->save();

		$this->assertTrue( API_Source::delete( 'temp' ) );
		$this->assertNull( API_Source::get( 'temp' ) );
	}

	public function test_delete_nonexistent_returns_false(): void {
		$this->assertFalse( API_Source::delete( 'ghost' ) );
	}

	public function test_save_without_name_fails(): void {
		$source = new API_Source( array( 'url' => 'https://example.com' ) );
		$this->assertFalse( $source->save() );
	}

	public function test_save_without_url_fails(): void {
		$source = new API_Source( array( 'name' => 'nourl' ) );
		$this->assertFalse( $source->save() );
	}

	public function test_method_defaults_to_get(): void {
		$source = new API_Source( array( 'name' => 'test', 'url' => 'https://t.com' ) );
		$this->assertSame( 'GET', $source->get_method() );
	}

	public function test_invalid_method_defaults_to_get(): void {
		$source = new API_Source( array( 'name' => 'test', 'url' => 'https://t.com', 'method' => 'DELETE' ) );
		$this->assertSame( 'GET', $source->get_method() );
	}

	public function test_auth_value_encryption_roundtrip(): void {
		$source = new API_Source(
			array(
				'name'       => 'secure',
				'url'        => 'https://api.example.com',
				'auth_type'  => 'bearer',
				'auth_value' => 'my-secret-token',
			)
		);
		$source->save();

		$loaded = API_Source::get( 'secure' );
		$this->assertSame( 'my-secret-token', $loaded->get_auth_value() );
	}
}
