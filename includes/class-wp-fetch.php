<?php
/**
 * Main plugin class.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch;

/**
 * Singleton orchestrator that registers all hooks.
 */
final class WP_Fetch {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — register hooks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Load plugin text domain.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'wp-fetch', false, dirname( WP_FETCH_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Register shortcodes.
	 */
	public function register_shortcodes(): void {
		$shortcode = new Shortcode();
		$shortcode->register();
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes(): void {
		$rest = new Rest_API();
		$rest->register_routes();
	}

	/**
	 * Register admin settings page.
	 */
	public function register_admin_menu(): void {
		$settings = new Admin_Settings();
		$settings->register();
	}

	/**
	 * Register dashboard widget.
	 */
	public function register_dashboard_widget(): void {
		$widget = new Admin_Widget();
		$widget->register();
	}

	/**
	 * Enqueue admin assets on relevant pages.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'settings_page_wp-fetch' === $hook_suffix ) {
			wp_enqueue_style(
				'wp-fetch-admin',
				WP_FETCH_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				WP_FETCH_VERSION
			);
			wp_enqueue_script(
				'wp-fetch-admin',
				WP_FETCH_PLUGIN_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				WP_FETCH_VERSION,
				true
			);
			wp_localize_script(
				'wp-fetch-admin',
				'wpFetchAdmin',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wp_fetch_admin' ),
				)
			);
		}

		if ( 'index.php' === $hook_suffix ) {
			wp_enqueue_style(
				'wp-fetch-widget',
				WP_FETCH_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				WP_FETCH_VERSION
			);
			wp_enqueue_script(
				'wp-fetch-widget',
				WP_FETCH_PLUGIN_URL . 'assets/js/widget.js',
				array( 'jquery' ),
				WP_FETCH_VERSION,
				true
			);
			wp_localize_script(
				'wp-fetch-widget',
				'wpFetchWidget',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wp_fetch_widget' ),
				)
			);
		}
	}
}
