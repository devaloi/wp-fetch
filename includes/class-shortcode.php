<?php
/**
 * Shortcode handler for [api_data].
 *
 * @package WP_Fetch
 */

namespace WP_Fetch;

/**
 * Registers and renders the [api_data] shortcode.
 */
class Shortcode {

	/**
	 * Register the shortcode.
	 */
	public function register(): void {
		add_shortcode( 'api_data', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function render( array|string $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'source'   => '',
				'template' => '',
				'field'    => '',
				'limit'    => 0,
			),
			$atts,
			'api_data'
		);

		$source_name = sanitize_key( $atts['source'] );
		if ( empty( $source_name ) ) {
			return $this->render_error( __( 'No source specified.', 'wp-fetch' ) );
		}

		$manager = new API_Manager();
		$result  = $manager->fetch( $source_name );

		if ( ! $result['success'] && empty( $result['data'] ) ) {
			$source = API_Source::get( $source_name );
			if ( null !== $source && ! empty( $source->get_fallback() ) ) {
				return wp_kses_post( $source->get_fallback() );
			}
			return $this->render_error( $result['error'] );
		}

		$data = $result['data'];

		if ( ! empty( $atts['field'] ) && is_array( $data ) ) {
			$data = API_Manager::apply_transform( $data, sanitize_text_field( $atts['field'] ) );
		}

		$limit = absint( $atts['limit'] );
		if ( $limit > 0 && is_array( $data ) ) {
			$data = array_slice( $data, 0, $limit );
		}

		$template_name = sanitize_file_name( $atts['template'] );
		$cached        = $result['cached'] ?? false;

		return $this->render_template( $data, $source_name, $cached, $template_name );
	}

	/**
	 * Render data using a template file.
	 *
	 * @param mixed  $data          Fetched data.
	 * @param string $source_name   Source slug.
	 * @param bool   $cached        Whether data came from cache.
	 * @param string $template_name Custom template name.
	 */
	private function render_template( mixed $data, string $source_name, bool $cached, string $template_name ): string {
		$template_path = $this->locate_template( $template_name );

		ob_start();

		if ( $template_path && file_exists( $template_path ) ) {
			include $template_path;
		} else {
			include WP_FETCH_PLUGIN_DIR . 'templates/shortcode-output.php';
		}

		return ob_get_clean();
	}

	/**
	 * Locate a custom template in theme directory.
	 *
	 * @param string $template_name Template name without .php.
	 * @return string|null Path to template or null.
	 */
	private function locate_template( string $template_name ): ?string {
		if ( empty( $template_name ) ) {
			return null;
		}

		$theme_template = get_stylesheet_directory() . '/wp-fetch/' . $template_name . '.php';
		if ( file_exists( $theme_template ) ) {
			return $theme_template;
		}

		$parent_template = get_template_directory() . '/wp-fetch/' . $template_name . '.php';
		if ( file_exists( $parent_template ) ) {
			return $parent_template;
		}

		return null;
	}

	/**
	 * Render an error message.
	 *
	 * @param string $message Error message.
	 */
	private function render_error( string $message ): string {
		return '<div class="wp-fetch-error">' . esc_html( $message ) . '</div>';
	}
}
