<?php
/**
 * Starter content registry, dry-run, import, and rollback admin panel.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register starter content admin hooks.
 */
function igp_pro_register_starter_content_admin(): void {
	if ( function_exists( 'igp_feature_enabled' ) && ! igp_feature_enabled( 'enable_starter_templates' ) ) {
		return;
	}

	add_action( 'admin_menu', 'igp_pro_register_starter_content_menu' );
	add_action( 'admin_enqueue_scripts', 'igp_pro_enqueue_starter_content_assets' );
	add_action( 'admin_post_igp_pro_import_starter_template', 'igp_pro_handle_import_starter_template' );
	add_action( 'admin_post_igp_pro_rollback_starter_template_import', 'igp_pro_handle_rollback_starter_template_import' );
}

/**
 * Register starter content submenu.
 */
function igp_pro_register_starter_content_menu(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'templates' ) : 'manage_options';

	add_submenu_page(
		'igp-pro-content-editor',
		__( 'IGP Starter Templates', 'igp-pro' ),
		__( 'Starter Templates', 'igp-pro' ),
		$capability,
		'igp-pro-starter-content',
		'igp_pro_render_starter_content_page'
	);
}

/**
 * Enqueue starter content admin assets.
 *
 * @param string $hook Admin hook.
 */
function igp_pro_enqueue_starter_content_assets( string $hook ): void {
	if ( false === strpos( $hook, 'igp-pro-starter-content' ) ) {
		return;
	}

	$js = 'assets/js/admin-starter-content.js';
	if ( file_exists( igp_pro_path( $js ) ) ) {
		wp_enqueue_script( 'igp-pro-admin-starter-content', igp_pro_url( $js ), array(), igp_pro_asset_version( $js ), true );
	}
}

/**
 * Render starter content page.
 */
function igp_pro_render_starter_content_page(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'templates' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to manage IGP starter templates.', 'igp-pro' ) );
	}

	$templates            = function_exists( 'igp_pro_discover_starter_templates' ) ? igp_pro_discover_starter_templates( true ) : array();
	$selected_template_id = isset( $_GET['template_id'] ) ? sanitize_key( wp_unslash( $_GET['template_id'] ) ) : '';
	$dry_run              = null;
	if ( '' !== $selected_template_id && function_exists( 'igp_pro_dry_run_starter_template' ) ) {
		$dry_run = igp_pro_dry_run_starter_template( $selected_template_id );
	}
	$recent_batches = function_exists( 'igp_pro_get_starter_template_import_batches' ) ? igp_pro_get_starter_template_import_batches() : array();
	?>
	<div class="wrap igp-pro-admin-wrap igp-pro-starter-content-admin">
		<h1><?php esc_html_e( 'IGP Starter Templates', 'igp-pro' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Phase 11 imports validated starter templates, tracks batches, and rolls back created or modified objects safely.', 'igp-pro' ); ?></p>

		<?php igp_pro_render_starter_content_notices(); ?>

		<?php if ( function_exists( 'igp_feature_enabled' ) && ! igp_feature_enabled( 'enable_starter_templates' ) ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Starter Templates feature flag is disabled.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Template Registry', 'igp-pro' ); ?></h2>
		<table class="widefat striped" style="max-width: 1180px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Template', 'igp-pro' ); ?></th>
					<th><?php esc_html_e( 'Version', 'igp-pro' ); ?></th>
					<th><?php esc_html_e( 'Industry', 'igp-pro' ); ?></th>
					<th><?php esc_html_e( 'Status', 'igp-pro' ); ?></th>
					<th><?php esc_html_e( 'Declared Content', 'igp-pro' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'igp-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $templates ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No starter templates found.', 'igp-pro' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $templates as $template ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $template['name'] ?? $template['template_id'] ?? '' ); ?></strong><br><code><?php echo esc_html( $template['template_id'] ?? '' ); ?></code></td>
						<td><?php echo esc_html( $template['version'] ?? '-' ); ?></td>
						<td><?php echo esc_html( $template['industry'] ?? '-' ); ?></td>
						<td>
							<?php if ( ! empty( $template['valid'] ) ) : ?>
								<span style="color:#008a20;font-weight:600;"><?php esc_html_e( 'Valid', 'igp-pro' ); ?></span>
							<?php else : ?>
								<span style="color:#b32d2e;font-weight:600;"><?php esc_html_e( 'Invalid', 'igp-pro' ); ?></span><br>
								<small><?php echo esc_html( $template['error'] ?? '' ); ?></small>
							<?php endif; ?>
						</td>
						<td>
							<?php
							printf(
								/* translators: 1: pages count, 2: tours count, 3: destinations count. */
								esc_html__( '%1$d pages, %2$d tours, %3$d destinations', 'igp-pro' ),
								(int) count( $template['pages'] ?? array() ),
								(int) count( $template['tours'] ?? array() ),
								(int) count( $template['destinations'] ?? array() )
							);
							?>
							<br><small><?php echo esc_html( sprintf( __( '%d media placeholders', 'igp-pro' ), (int) count( $template['media_placeholders'] ?? array() ) ) ); ?></small>
						</td>
						<td>
							<?php if ( ! empty( $template['valid'] ) ) : ?>
								<p><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'igp-pro-starter-content', 'template_id' => $template['template_id'] ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Preview / Dry Run', 'igp-pro' ); ?></a></p>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:4px;">
									<input type="hidden" name="action" value="igp_pro_import_starter_template">
									<input type="hidden" name="template_id" value="<?php echo esc_attr( (string) $template['template_id'] ); ?>">
									<input type="hidden" name="mode" value="create_new">
									<?php wp_nonce_field( 'igp_pro_import_starter_template_' . (string) $template['template_id'] ); ?>
									<?php submit_button( __( 'Import new/missing', 'igp-pro' ), 'secondary', 'submit', false ); ?>
								</form>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
									<input type="hidden" name="action" value="igp_pro_import_starter_template">
									<input type="hidden" name="template_id" value="<?php echo esc_attr( (string) $template['template_id'] ); ?>">
									<input type="hidden" name="mode" value="merge_existing">
									<?php wp_nonce_field( 'igp_pro_import_starter_template_' . (string) $template['template_id'] ); ?>
									<?php submit_button( __( 'Merge existing', 'igp-pro' ), 'secondary', 'submit', false ); ?>
								</form>
							<?php else : ?>
								<span class="description"><?php esc_html_e( 'Not importable', 'igp-pro' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>

		<?php if ( $dry_run ) : ?>
			<?php igp_pro_render_starter_template_dry_run_result( $dry_run ); ?>
		<?php endif; ?>

		<?php igp_pro_render_starter_template_import_batches( $recent_batches ); ?>
	</div>
	<?php
}

/**
 * Render admin notices from redirects.
 */
function igp_pro_render_starter_content_notices(): void {
	if ( isset( $_GET['template-imported'] ) ) {
		$batch_id = sanitize_key( wp_unslash( $_GET['template-imported'] ) );
		printf( '<div class="notice notice-success is-dismissible"><p>%s <code>%s</code></p></div>', esc_html__( 'Starter template imported. Batch:', 'igp-pro' ), esc_html( $batch_id ) );
	}
	if ( isset( $_GET['template-rollback'] ) ) {
		$batch_id = sanitize_key( wp_unslash( $_GET['template-rollback'] ) );
		printf( '<div class="notice notice-success is-dismissible"><p>%s <code>%s</code></p></div>', esc_html__( 'Starter template rollback completed. Batch:', 'igp-pro' ), esc_html( $batch_id ) );
	}
	if ( isset( $_GET['template-rollback-conflict'] ) ) {
		$batch_id = sanitize_key( wp_unslash( $_GET['template-rollback-conflict'] ) );
		printf( '<div class="notice notice-warning is-dismissible"><p>%s <code>%s</code></p></div>', esc_html__( 'Rollback found conflicts. Review before forcing rollback. Batch:', 'igp-pro' ), esc_html( $batch_id ) );
	}
}

/**
 * Render dry-run result.
 *
 * @param array|WP_Error $dry_run Dry-run result.
 */
function igp_pro_render_starter_template_dry_run_result( $dry_run ): void {
	?>
	<hr>
	<h2><?php esc_html_e( 'Preview / Dry Run Result', 'igp-pro' ); ?></h2>
	<?php if ( is_wp_error( $dry_run ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $dry_run->get_error_message() ); ?></p></div>
		<?php return; ?>
	<?php endif; ?>

	<div class="notice notice-info"><p><?php esc_html_e( 'Dry run completed without writing posts, post meta, options, media, or relationships.', 'igp-pro' ); ?></p></div>

	<?php if ( ! empty( $dry_run['valid'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Dry run is valid. This template is importable.', 'igp-pro' ); ?></p></div>
	<?php else : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Dry run found blocking issues. This template must not be imported.', 'igp-pro' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! empty( $dry_run['errors'] ) ) : ?>
		<h3><?php esc_html_e( 'Errors', 'igp-pro' ); ?></h3>
		<ul><?php foreach ( $dry_run['errors'] as $error ) : ?><li><?php echo esc_html( $error ); ?></li><?php endforeach; ?></ul>
	<?php endif; ?>

	<?php if ( ! empty( $dry_run['warnings'] ) ) : ?>
		<h3><?php esc_html_e( 'Warnings', 'igp-pro' ); ?></h3>
		<ul><?php foreach ( $dry_run['warnings'] as $warning ) : ?><li><?php echo esc_html( $warning ); ?></li><?php endforeach; ?></ul>
	<?php endif; ?>

	<h3><?php esc_html_e( 'Summary', 'igp-pro' ); ?></h3>
	<table class="widefat striped" style="max-width: 760px;"><tbody>
		<?php foreach ( $dry_run['counts'] ?? array() as $key => $value ) : ?>
			<tr><th><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></th><td><?php echo esc_html( (string) $value ); ?></td></tr>
		<?php endforeach; ?>
	</tbody></table>

	<?php foreach ( array( 'pages' => __( 'Pages', 'igp-pro' ), 'tours' => __( 'Tours', 'igp-pro' ), 'destinations' => __( 'Destinations', 'igp-pro' ) ) as $bucket => $label ) : ?>
		<h3><?php echo esc_html( $label ); ?></h3>
		<table class="widefat striped" style="max-width: 1180px;">
			<thead><tr><th><?php esc_html_e( 'Action', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Title', 'igp-pro' ); ?></th><th><?php esc_html_e( 'UUID', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Sections', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Blocks', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Relationships', 'igp-pro' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $dry_run['objects'][ $bucket ] ?? array() as $object ) : ?>
				<tr>
					<td><code><?php echo esc_html( $object['action'] ?? '' ); ?></code></td>
					<td><?php echo esc_html( $object['title'] ?? '' ); ?><br><small><?php echo esc_html( $object['slug'] ?? '' ); ?></small></td>
					<td><code><?php echo esc_html( $object['template_uuid'] ?? '' ); ?></code></td>
					<td><?php echo esc_html( (string) ( $object['section_count'] ?? 0 ) ); ?></td>
					<td><?php echo esc_html( implode( ', ', $object['block_ids'] ?? array() ) ); ?></td>
					<td><?php echo esc_html( empty( $object['relationships'] ) ? '-' : wp_json_encode( $object['relationships'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ( empty( $dry_run['objects'][ $bucket ] ) ) : ?><tr><td colspan="6"><?php esc_html_e( 'None declared.', 'igp-pro' ); ?></td></tr><?php endif; ?>
			</tbody>
		</table>
	<?php endforeach; ?>

	<h3><?php esc_html_e( 'Raw Dry Run Payload', 'igp-pro' ); ?></h3>
	<textarea readonly rows="12" style="width:100%;max-width:1180px;font-family:monospace;"><?php echo esc_textarea( wp_json_encode( $dry_run, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></textarea>
	<?php
}

/**
 * Render recent import batches.
 *
 * @param array<int,array<string,mixed>> $batches Batches.
 */
function igp_pro_render_starter_template_import_batches( array $batches ): void {
	?>
	<hr>
	<h2><?php esc_html_e( 'Recent Template Import Batches', 'igp-pro' ); ?></h2>
	<?php if ( empty( $batches ) ) : ?>
		<p><?php esc_html_e( 'No starter template imports yet.', 'igp-pro' ); ?></p>
		<?php return; ?>
	<?php endif; ?>
	<table class="widefat striped" style="max-width: 1180px;">
		<thead><tr><th><?php esc_html_e( 'Created', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Batch', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Template', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Mode', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Status', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Objects', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Rollback', 'igp-pro' ); ?></th></tr></thead>
		<tbody>
		<?php foreach ( array_slice( $batches, 0, 10 ) as $batch ) : ?>
			<?php $status = sanitize_key( (string) ( $batch['status'] ?? '' ) ); ?>
			<tr>
				<td><code><?php echo esc_html( (string) ( $batch['created_at'] ?? '' ) ); ?></code></td>
				<td><code><?php echo esc_html( (string) ( $batch['batch_id'] ?? '' ) ); ?></code></td>
				<td><?php echo esc_html( (string) ( $batch['template_id'] ?? '' ) ); ?></td>
				<td><?php echo esc_html( (string) ( $batch['mode'] ?? '' ) ); ?></td>
				<td><?php echo esc_html( $status ); ?></td>
				<td><?php echo esc_html( (string) count( $batch['objects'] ?? array() ) ); ?></td>
				<td>
					<?php if ( in_array( $status, array( 'completed', 'partial' ), true ) ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:4px;">
							<input type="hidden" name="action" value="igp_pro_rollback_starter_template_import">
							<input type="hidden" name="batch_id" value="<?php echo esc_attr( (string) ( $batch['batch_id'] ?? '' ) ); ?>">
							<input type="hidden" name="mode" value="safe">
							<?php wp_nonce_field( 'igp_pro_rollback_starter_template_import_' . (string) ( $batch['batch_id'] ?? '' ) ); ?>
							<?php submit_button( __( 'Rollback', 'igp-pro' ), 'secondary', 'submit', false ); ?>
						</form>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
							<input type="hidden" name="action" value="igp_pro_rollback_starter_template_import">
							<input type="hidden" name="batch_id" value="<?php echo esc_attr( (string) ( $batch['batch_id'] ?? '' ) ); ?>">
							<input type="hidden" name="mode" value="force">
							<?php wp_nonce_field( 'igp_pro_rollback_starter_template_import_' . (string) ( $batch['batch_id'] ?? '' ) ); ?>
							<?php submit_button( __( 'Force rollback', 'igp-pro' ), 'delete', 'submit', false ); ?>
						</form>
					<?php else : ?>
						<span class="description"><?php esc_html_e( 'No rollback action available.', 'igp-pro' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Permission guard for starter template writes.
 */
function igp_pro_starter_template_action_permission_check(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'templates' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log(
				array(
					'actor_type'    => is_user_logged_in() ? 'human' : 'anonymous',
					'operation'     => 'starter_template_permission_denied',
					'object_type'   => 'admin_post',
					'object_id'     => 0,
					'source_module' => 'starter-content-panel',
					'status'        => 'failure',
					'error_code'    => 'igp_pro_missing_capability',
					'summary'       => 'Starter template action denied.',
				)
			);
		}
		wp_die( esc_html__( 'You do not have permission to manage starter templates.', 'igp-pro' ) );
	}
}

/**
 * Handle import action.
 */
function igp_pro_handle_import_starter_template(): void {
	$template_id = isset( $_POST['template_id'] ) ? sanitize_key( wp_unslash( $_POST['template_id'] ) ) : '';
	check_admin_referer( 'igp_pro_import_starter_template_' . $template_id );
	igp_pro_starter_template_action_permission_check();

	$mode   = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'create_new';
	$result = function_exists( 'igp_pro_import_starter_template' ) ? igp_pro_import_starter_template( $template_id, $mode ) : new WP_Error( 'igp_pro_template_importer_missing', __( 'Template importer is unavailable.', 'igp-pro' ) );
	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ) );
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-starter-content', 'template-imported' => (string) ( $result['batch_id'] ?? '' ) ), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Handle rollback action.
 */
function igp_pro_handle_rollback_starter_template_import(): void {
	$batch_id = isset( $_POST['batch_id'] ) ? sanitize_key( wp_unslash( $_POST['batch_id'] ) ) : '';
	check_admin_referer( 'igp_pro_rollback_starter_template_import_' . $batch_id );
	igp_pro_starter_template_action_permission_check();

	$mode   = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'safe';
	$result = function_exists( 'igp_pro_rollback_starter_template_import' ) ? igp_pro_rollback_starter_template_import( $batch_id, $mode ) : new WP_Error( 'igp_pro_template_rollback_missing', __( 'Template rollback is unavailable.', 'igp-pro' ) );
	if ( is_wp_error( $result ) ) {
		$data = $result->get_error_data();
		if ( is_array( $data ) && ! empty( $data['conflict_detected'] ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-starter-content', 'template-rollback-conflict' => $batch_id ), admin_url( 'admin.php' ) ) );
			exit;
		}
		wp_die( esc_html( $result->get_error_message() ) );
	}

	$arg = empty( $result['conflicts'] ) ? 'template-rollback' : 'template-rollback-conflict';
	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-starter-content', $arg => $batch_id ), admin_url( 'admin.php' ) ) );
	exit;
}
