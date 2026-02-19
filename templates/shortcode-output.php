<?php
/**
 * Default shortcode output template.
 *
 * Available variables:
 *   $data        — Fetched data (array or scalar).
 *   $source_name — Source slug.
 *   $cached      — Whether data came from cache (bool).
 *
 * @package WP_Fetch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wp-fetch-data" data-source="<?php echo esc_attr( $source_name ); ?>">
	<?php if ( is_array( $data ) ) : ?>
		<?php if ( wp_is_numeric_array( $data ) ) : ?>
			<ul class="wp-fetch-list">
				<?php foreach ( $data as $item ) : ?>
					<li>
						<?php if ( is_array( $item ) ) : ?>
							<dl class="wp-fetch-item">
								<?php foreach ( $item as $key => $value ) : ?>
									<dt><?php echo esc_html( $key ); ?></dt>
									<dd><?php echo esc_html( is_scalar( $value ) ? $value : wp_json_encode( $value ) ); ?></dd>
								<?php endforeach; ?>
							</dl>
						<?php else : ?>
							<?php echo esc_html( is_scalar( $item ) ? $item : wp_json_encode( $item ) ); ?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<dl class="wp-fetch-fields">
				<?php foreach ( $data as $key => $value ) : ?>
					<dt><?php echo esc_html( $key ); ?></dt>
					<dd><?php echo esc_html( is_scalar( $value ) ? $value : wp_json_encode( $value ) ); ?></dd>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	<?php else : ?>
		<p class="wp-fetch-value"><?php echo esc_html( (string) $data ); ?></p>
	<?php endif; ?>
</div>
