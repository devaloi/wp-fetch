<?php
/**
 * Dashboard widget showing API source status.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch;

/**
 * Registers and renders the WP Fetch dashboard widget.
 */
class Admin_Widget {

	/**
	 * Register the dashboard widget and AJAX handler.
	 */
	public function register(): void {
		wp_add_dashboard_widget(
			'wp_fetch_status',
			__( 'WP Fetch — API Status', 'wp-fetch' ),
			array( $this, 'render' )
		);

		add_action( 'wp_ajax_wp_fetch_widget_refresh', array( $this, 'ajax_refresh' ) );
	}

	/**
	 * Render the dashboard widget.
	 */
	public function render(): void {
		include WP_FETCH_PLUGIN_DIR . 'templates/widget-output.php';
	}

	/**
	 * AJAX: Refresh a source from the dashboard widget.
	 */
	public function ajax_refresh(): void {
		check_ajax_referer( 'wp_fetch_widget', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'wp-fetch' ), 403 );
		}

		$name    = sanitize_key( wp_unslash( $_POST['name'] ?? '' ) );
		$manager = new API_Manager();
		$result  = $manager->fetch( $name, true );

		if ( $result['success'] ) {
			wp_send_json_success( __( 'Refreshed successfully.', 'wp-fetch' ) );
		} else {
			wp_send_json_error( $result['error'] );
		}
	}
}
