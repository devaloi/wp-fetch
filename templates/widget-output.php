<?php
/**
 * Dashboard widget output template.
 *
 * @package WP_Fetch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sources       = \WP_Fetch\API_Source::list_all();
$error_handler = new \WP_Fetch\Error_Handler();
$cache         = new \WP_Fetch\Cache();
?>

<?php if ( empty( $sources ) ) : ?>
	<p><?php esc_html_e( 'No API sources configured.', 'wp-fetch' ); ?>
		<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-fetch' ) ); ?>">
			<?php esc_html_e( 'Add one now', 'wp-fetch' ); ?>
		</a>
	</p>
<?php else : ?>
	<table id="wp-fetch-widget-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Source', 'wp-fetch' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wp-fetch' ); ?></th>
				<th><?php esc_html_e( 'Errors (24h)', 'wp-fetch' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wp-fetch' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $sources as $source ) :
				$name        = $source->get_name();
				$cache_key   = 'source_' . $name;
				$has_cache   = null !== $cache->get( $cache_key );
				$has_stale   = null !== $cache->get_stale( $cache_key );
				$error_count = $error_handler->count_recent( $name );

				if ( 0 === $error_count && $has_cache ) {
					$status = 'ok';
					$label  = __( 'OK', 'wp-fetch' );
				} elseif ( $has_stale || $has_cache ) {
					$status = 'stale';
					$label  = __( 'Stale', 'wp-fetch' );
				} else {
					$status = 'error';
					$label  = __( 'Error', 'wp-fetch' );
				}
			?>
				<tr>
					<td><?php echo esc_html( $name ); ?></td>
					<td>
						<span class="wp-fetch-status-indicator <?php echo esc_attr( $status ); ?>"></span>
						<span class="wp-fetch-status-<?php echo esc_attr( $status ); ?>">
							<?php echo esc_html( $label ); ?>
						</span>
					</td>
					<td><?php echo esc_html( $error_count ); ?></td>
					<td class="wp-fetch-widget-actions">
						<button type="button" class="button button-small wp-fetch-widget-refresh"
							data-name="<?php echo esc_attr( $name ); ?>">
							<?php esc_html_e( 'Refresh', 'wp-fetch' ); ?>
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<div id="wp-fetch-widget-message"></div>
<?php endif; ?>
