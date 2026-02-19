<?php
/**
 * Uninstall handler for WP Fetch.
 *
 * Removes all plugin options and transients on uninstall.
 *
 * @package WP_Fetch
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

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
