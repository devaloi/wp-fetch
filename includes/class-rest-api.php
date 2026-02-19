<?php
/**
 * WP REST API endpoints for WP Fetch.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch;

/**
 * Registers and handles REST API routes under wp-fetch/v1.
 */
class Rest_API {

	private const NAMESPACE = 'wp-fetch/v1';

	/**
	 * Register all REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/sources',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_sources' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/data/(?P<source>[a-z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_data' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'source' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/refresh/(?P<source>[a-z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'refresh_data' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => array(
					'source' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);
	}

	/**
	 * Permission check for admin-only endpoints.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /sources — list all configured sources.
	 */
	public function get_sources(): \WP_REST_Response {
		$sources = API_Source::list_all();
		$data    = array();

		foreach ( $sources as $source ) {
			$data[] = array(
				'name'      => $source->get_name(),
				'url'       => $source->get_url(),
				'method'    => $source->get_method(),
				'auth_type' => $source->get_auth_type(),
				'cache_ttl' => $source->get_cache_ttl(),
			);
		}

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * GET /data/{source} — fetch data from a source (cached).
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public function get_data( \WP_REST_Request $request ): \WP_REST_Response {
		$source_name = $request->get_param( 'source' );
		$manager     = new API_Manager();
		$result      = $manager->fetch( $source_name );

		if ( ! $result['success'] && empty( $result['data'] ) ) {
			return new \WP_REST_Response(
				array( 'error' => $result['error'] ),
				$result['status_code'] ?: 502
			);
		}

		return new \WP_REST_Response(
			array(
				'source' => $source_name,
				'data'   => $result['data'],
				'cached' => $result['cached'] ?? false,
			),
			200
		);
	}

	/**
	 * POST /refresh/{source} — force refresh a source.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public function refresh_data( \WP_REST_Request $request ): \WP_REST_Response {
		$source_name = $request->get_param( 'source' );
		$manager     = new API_Manager();
		$result      = $manager->fetch( $source_name, true );

		$status = $result['success'] ? 200 : ( $result['status_code'] ?: 502 );

		return new \WP_REST_Response(
			array(
				'source'  => $source_name,
				'success' => $result['success'],
				'data'    => $result['data'],
				'error'   => $result['error'],
			),
			$status
		);
	}

	/**
	 * GET /status — health status of all sources.
	 */
	public function get_status(): \WP_REST_Response {
		$sources       = API_Source::list_all();
		$error_handler = new Error_Handler();
		$cache         = new Cache();
		$statuses      = array();

		foreach ( $sources as $source ) {
			$name        = $source->get_name();
			$cache_key   = 'source_' . $name;
			$has_cache   = null !== $cache->get( $cache_key );
			$has_stale   = null !== $cache->get_stale( $cache_key );
			$error_count = $error_handler->count_recent( $name );

			if ( 0 === $error_count && $has_cache ) {
				$status = 'ok';
			} elseif ( $has_stale ) {
				$status = 'stale';
			} else {
				$status = 'error';
			}

			$statuses[] = array(
				'name'        => $name,
				'status'      => $status,
				'cached'      => $has_cache,
				'error_count' => $error_count,
			);
		}

		return new \WP_REST_Response( $statuses, 200 );
	}
}
