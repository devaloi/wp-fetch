<?php
/**
 * Admin settings page template.
 *
 * @package WP_Fetch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sources = \WP_Fetch\API_Source::list_all();
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'wp_fetch_settings' );
		do_settings_sections( 'wp-fetch' );
		submit_button( __( 'Save Settings', 'wp-fetch' ) );
		?>
	</form>

	<hr />

	<h2><?php esc_html_e( 'API Sources', 'wp-fetch' ); ?></h2>

	<?php if ( ! empty( $sources ) ) : ?>
		<table class="wp-list-table widefat fixed striped" id="wp-fetch-sources-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'wp-fetch' ); ?></th>
					<th><?php esc_html_e( 'URL', 'wp-fetch' ); ?></th>
					<th><?php esc_html_e( 'Method', 'wp-fetch' ); ?></th>
					<th><?php esc_html_e( 'Auth', 'wp-fetch' ); ?></th>
					<th><?php esc_html_e( 'TTL', 'wp-fetch' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wp-fetch' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $sources as $source ) : ?>
					<tr data-source="<?php echo esc_attr( $source->get_name() ); ?>">
						<td><?php echo esc_html( $source->get_name() ); ?></td>
						<td><?php echo esc_url( $source->get_url() ); ?></td>
						<td><?php echo esc_html( $source->get_method() ); ?></td>
						<td><?php echo esc_html( $source->get_auth_type() ); ?></td>
						<td><?php echo esc_html( $source->get_cache_ttl() ); ?>s</td>
						<td>
							<button type="button" class="button wp-fetch-edit-source"
								data-name="<?php echo esc_attr( $source->get_name() ); ?>"
								data-url="<?php echo esc_attr( $source->get_url() ); ?>"
								data-method="<?php echo esc_attr( $source->get_method() ); ?>"
								data-auth-type="<?php echo esc_attr( $source->get_auth_type() ); ?>"
								data-cache-ttl="<?php echo esc_attr( $source->get_cache_ttl() ); ?>"
								data-transform="<?php echo esc_attr( $source->get_transform() ); ?>"
								data-fallback="<?php echo esc_attr( $source->get_fallback() ); ?>"
								data-headers="<?php echo esc_attr( wp_json_encode( $source->get_headers() ) ); ?>">
								<?php esc_html_e( 'Edit', 'wp-fetch' ); ?>
							</button>
							<button type="button" class="button wp-fetch-test-source"
								data-name="<?php echo esc_attr( $source->get_name() ); ?>">
								<?php esc_html_e( 'Test', 'wp-fetch' ); ?>
							</button>
							<button type="button" class="button wp-fetch-delete-source"
								data-name="<?php echo esc_attr( $source->get_name() ); ?>">
								<?php esc_html_e( 'Delete', 'wp-fetch' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No API sources configured yet.', 'wp-fetch' ); ?></p>
	<?php endif; ?>

	<hr />

	<h3 id="wp-fetch-form-title"><?php esc_html_e( 'Add New Source', 'wp-fetch' ); ?></h3>

	<form id="wp-fetch-source-form" class="wp-fetch-source-form">
		<table class="form-table">
			<tr>
				<th><label for="wp-fetch-name"><?php esc_html_e( 'Name (slug)', 'wp-fetch' ); ?></label></th>
				<td><input type="text" id="wp-fetch-name" name="name" class="regular-text" required /></td>
			</tr>
			<tr>
				<th><label for="wp-fetch-url"><?php esc_html_e( 'URL', 'wp-fetch' ); ?></label></th>
				<td><input type="url" id="wp-fetch-url" name="url" class="regular-text" required /></td>
			</tr>
			<tr>
				<th><label for="wp-fetch-method"><?php esc_html_e( 'Method', 'wp-fetch' ); ?></label></th>
				<td>
					<select id="wp-fetch-method" name="method">
						<option value="GET">GET</option>
						<option value="POST">POST</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="wp-fetch-headers"><?php esc_html_e( 'Headers (JSON)', 'wp-fetch' ); ?></label></th>
				<td><textarea id="wp-fetch-headers" name="headers" class="large-text" rows="3">{}</textarea></td>
			</tr>
			<tr>
				<th><label for="wp-fetch-auth-type"><?php esc_html_e( 'Auth Type', 'wp-fetch' ); ?></label></th>
				<td>
					<select id="wp-fetch-auth-type" name="auth_type">
						<option value="none"><?php esc_html_e( 'None', 'wp-fetch' ); ?></option>
						<option value="api_key"><?php esc_html_e( 'API Key', 'wp-fetch' ); ?></option>
						<option value="bearer"><?php esc_html_e( 'Bearer Token', 'wp-fetch' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="wp-fetch-auth-value"><?php esc_html_e( 'Auth Value', 'wp-fetch' ); ?></label></th>
				<td><input type="password" id="wp-fetch-auth-value" name="auth_value" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="wp-fetch-cache-ttl"><?php esc_html_e( 'Cache TTL (seconds)', 'wp-fetch' ); ?></label></th>
				<td><input type="number" id="wp-fetch-cache-ttl" name="cache_ttl" value="300" min="0" class="small-text" /></td>
			</tr>
			<tr>
				<th><label for="wp-fetch-transform"><?php esc_html_e( 'Transform (dot path)', 'wp-fetch' ); ?></label></th>
				<td><input type="text" id="wp-fetch-transform" name="transform" class="regular-text" placeholder="data.results" /></td>
			</tr>
			<tr>
				<th><label for="wp-fetch-fallback"><?php esc_html_e( 'Fallback HTML', 'wp-fetch' ); ?></label></th>
				<td><textarea id="wp-fetch-fallback" name="fallback" class="large-text" rows="3"></textarea></td>
			</tr>
		</table>

		<?php wp_nonce_field( 'wp_fetch_admin', 'wp_fetch_nonce' ); ?>
		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Source', 'wp-fetch' ); ?></button>
			<button type="button" class="button wp-fetch-cancel-edit" style="display:none;"><?php esc_html_e( 'Cancel', 'wp-fetch' ); ?></button>
		</p>
		<div id="wp-fetch-form-message"></div>
	</form>
</div>
