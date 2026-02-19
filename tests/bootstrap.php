<?php
/**
 * Test bootstrap — provides WP function stubs for unit testing outside WordPress.
 *
 * @package WP_Fetch
 */

define( 'ABSPATH', '/tmp/wordpress/' );
define( 'WP_FETCH_VERSION', '1.0.0' );
define( 'WP_FETCH_PLUGIN_FILE', dirname( __DIR__ ) . '/wp-fetch.php' );
define( 'WP_FETCH_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'WP_FETCH_PLUGIN_URL', 'https://example.com/wp-content/plugins/wp-fetch/' );
define( 'WP_FETCH_PLUGIN_BASENAME', 'wp-fetch/wp-fetch.php' );
define( 'DAY_IN_SECONDS', 86400 );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * In-memory stores for WordPress function stubs.
 */
class WP_Test_Store {
	public static array $options    = array();
	public static array $transients = array();

	public static function reset(): void {
		self::$options    = array();
		self::$transients = array();
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $key, mixed $default = false ): mixed {
		return WP_Test_Store::$options[ $key ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $key, mixed $value ): bool {
		WP_Test_Store::$options[ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	function add_option( string $key, mixed $value ): bool {
		if ( ! isset( WP_Test_Store::$options[ $key ] ) ) {
			WP_Test_Store::$options[ $key ] = $value;
		}
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $key ): bool {
		unset( WP_Test_Store::$options[ $key ] );
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( string $key ): mixed {
		$entry = WP_Test_Store::$transients[ $key ] ?? null;
		if ( null === $entry ) {
			return false;
		}
		if ( $entry['expiry'] > 0 && $entry['expiry'] < time() ) {
			unset( WP_Test_Store::$transients[ $key ] );
			return false;
		}
		return $entry['value'];
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( string $key, mixed $value, int $expiry = 0 ): bool {
		WP_Test_Store::$transients[ $key ] = array(
			'value'  => $value,
			'expiry' => $expiry > 0 ? time() + $expiry : 0,
		);
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( string $key ): bool {
		unset( WP_Test_Store::$transients[ $key ] );
		return true;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( string $key ): string {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $str ): string {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'sanitize_file_name' ) ) {
	function sanitize_file_name( string $name ): string {
		return preg_replace( '/[^a-zA-Z0-9._-]/', '', $name );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string {
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( string $data ): string {
		return $data;
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( mixed $value ): int {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'wp_salt' ) ) {
	function wp_salt( string $scheme = 'auth' ): string {
		return 'test-salt-key-' . $scheme;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( mixed $data ): string|false {
		return json_encode( $data );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = 'default' ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( string $type ): string {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'wp_is_numeric_array' ) ) {
	function wp_is_numeric_array( array $data ): bool {
		return array_is_list( $data );
	}
}

if ( ! function_exists( 'shortcode_atts' ) ) {
	function shortcode_atts( array $defaults, array|string $atts, string $shortcode = '' ): array {
		$atts = (array) $atts;
		$out  = array();
		foreach ( $defaults as $name => $default ) {
			$out[ $name ] = $atts[ $name ] ?? $default;
		}
		return $out;
	}
}
