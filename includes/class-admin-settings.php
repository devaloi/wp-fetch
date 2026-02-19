<?php
/**
 * Admin settings page for WP Fetch.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch;

/**
 * Registers and renders the admin settings page.
 */
class Admin_Settings {

	private const PAGE_SLUG  = 'wp-fetch';
	private const OPTION_GROUP = 'wp_fetch_settings';

	/**
	 * Register the settings page and AJAX handlers.
	 */
	public function register(): void {
		add_options_page(
			__( 'WP Fetch Settings', 'wp-fetch' ),
			__( 'WP Fetch', 'wp-fetch' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_wp_fetch_save_source', array( $this, 'ajax_save_source' ) );
		add_action( 'wp_ajax_wp_fetch_delete_source', array( $this, 'ajax_delete_source' ) );
		add_action( 'wp_ajax_wp_fetch_test_source', array( $this, 'ajax_test_source' ) );
	}

	/**
	 * Register settings with the Settings API.
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			'wp_fetch_rate_limit',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 30,
			)
		);

		add_settings_section(
			'wp_fetch_general',
			__( 'General Settings', 'wp-fetch' ),
			'__return_empty_string',
			self::PAGE_SLUG
		);

		add_settings_field(
			'wp_fetch_rate_limit',
			__( 'Rate Limit (requests/min)', 'wp-fetch' ),
			array( $this, 'render_rate_limit_field' ),
			self::PAGE_SLUG,
			'wp_fetch_general'
		);
	}

	/**
	 * Render rate limit field.
	 */
	public function render_rate_limit_field(): void {
		$value = get_option( 'wp_fetch_rate_limit', 30 );
		printf(
			'<input type="number" name="wp_fetch_rate_limit" value="%d" min="1" max="1000" class="small-text" />',
			esc_attr( $value )
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include WP_FETCH_PLUGIN_DIR . 'templates/settings-page.php';
	}

	/**
	 * AJAX: Save an API source.
	 */
	public function ajax_save_source(): void {
		check_ajax_referer( 'wp_fetch_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'wp-fetch' ), 403 );
		}

		$data = array(
			'name'       => sanitize_key( wp_unslash( $_POST['name'] ?? '' ) ),
			'url'        => esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ),
			'method'     => sanitize_text_field( wp_unslash( $_POST['method'] ?? 'GET' ) ),
			'headers'    => sanitize_text_field( wp_unslash( $_POST['headers'] ?? '{}' ) ),
			'auth_type'  => sanitize_text_field( wp_unslash( $_POST['auth_type'] ?? 'none' ) ),
			'auth_value' => sanitize_text_field( wp_unslash( $_POST['auth_value'] ?? '' ) ),
			'cache_ttl'  => absint( $_POST['cache_ttl'] ?? 300 ),
			'transform'  => sanitize_text_field( wp_unslash( $_POST['transform'] ?? '' ) ),
			'fallback'   => wp_kses_post( wp_unslash( $_POST['fallback'] ?? '' ) ),
		);

		$source = new API_Source( $data );
		if ( $source->save() ) {
			wp_send_json_success( __( 'Source saved.', 'wp-fetch' ) );
		} else {
			wp_send_json_error( __( 'Failed to save source. Name and URL are required.', 'wp-fetch' ) );
		}
	}

	/**
	 * AJAX: Delete an API source.
	 */
	public function ajax_delete_source(): void {
		check_ajax_referer( 'wp_fetch_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'wp-fetch' ), 403 );
		}

		$name = sanitize_key( wp_unslash( $_POST['name'] ?? '' ) );
		if ( API_Source::delete( $name ) ) {
			wp_send_json_success( __( 'Source deleted.', 'wp-fetch' ) );
		} else {
			wp_send_json_error( __( 'Source not found.', 'wp-fetch' ) );
		}
	}

	/**
	 * AJAX: Test an API source connection.
	 */
	public function ajax_test_source(): void {
		check_ajax_referer( 'wp_fetch_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'wp-fetch' ), 403 );
		}

		$name = sanitize_key( wp_unslash( $_POST['name'] ?? '' ) );
		$source = API_Source::get( $name );

		if ( null === $source ) {
			wp_send_json_error( __( 'Source not found.', 'wp-fetch' ) );
			return;
		}

		$manager = new API_Manager();
		$result  = $manager->fetch( $name, true );

		if ( $result['success'] ) {
			wp_send_json_success(
				array(
					'status_code' => $result['status_code'],
					'message'     => __( 'Connection successful.', 'wp-fetch' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'status_code' => $result['status_code'],
					'message'     => $result['error'],
				)
			);
		}
	}
}
