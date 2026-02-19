<?php
/**
 * Plugin Name: WP Fetch
 * Plugin URI:  https://github.com/devaloi/wp-fetch
 * Description: Fetch and display external API data with transient caching, rate limiting, and shortcodes.
 * Version:     1.0.0
 * Author:      devaloi
 * Author URI:  https://github.com/devaloi
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: wp-fetch
 * Domain Path: /languages
 * Requires PHP: 8.2
 * Requires at least: 6.5
 *
 * @package WP_Fetch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_FETCH_VERSION', '1.0.0' );
define( 'WP_FETCH_PLUGIN_FILE', __FILE__ );
define( 'WP_FETCH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_FETCH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_FETCH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once WP_FETCH_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * Plugin activation hook.
 */
function wp_fetch_activate(): void {
	$defaults = array(
		'wp_fetch_sources'    => array(),
		'wp_fetch_rate_limit' => 30,
	);
	foreach ( $defaults as $key => $value ) {
		if ( false === get_option( $key ) ) {
			add_option( $key, $value );
		}
	}
}
register_activation_hook( __FILE__, 'wp_fetch_activate' );

/**
 * Plugin deactivation hook.
 */
function wp_fetch_deactivate(): void {
	wp_cache_flush();
}
register_deactivation_hook( __FILE__, 'wp_fetch_deactivate' );

/**
 * Plugin uninstall handler.
 */
function wp_fetch_uninstall(): void {
	delete_option( 'wp_fetch_sources' );
	delete_option( 'wp_fetch_rate_limit' );
	delete_option( 'wp_fetch_errors' );

	global $wpdb;
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_wp_fetch_%',
			'_transient_timeout_wp_fetch_%'
		)
	);
}

WP_Fetch\WP_Fetch::get_instance();
