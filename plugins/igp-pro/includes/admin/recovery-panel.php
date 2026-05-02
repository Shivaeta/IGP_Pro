<?php
/**
 * Recovery panel for IGP Pro V2 snapshots.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register recovery admin hooks.
 */
function igp_pro_register_recovery_admin(): void {
	add_action( 'admin_menu', 'igp_pro_register_recovery_menu' );
	add_action( 'admin_post_igp_pro_create_content_graph_snapshot', 'igp_pro_handle_create_content_graph_snapshot' );
	add_action( 'admin_post_igp_pro_restore_snapshot', 'igp_pro_handle_restore_snapshot' );
}

/**
 * Register recovery submenu.
 */
function igp_pro_register_recovery_menu(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'recovery' ) : 'manage_options';

	add_submenu_page(
		'igp-pro-content-editor',
		__( 'IGP Pro Recovery', 'igp-pro' ),
		__( 'Recovery', 'igp-pro' ),
		$capability,
		'igp-pro-recovery',
		'igp_pro_render_recovery_page'
	);
}

/**
 * Render recovery page.
 */
function igp_pro_render_recovery_page(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'recovery' ) : 'manage_options';

	if ( ! current_user_can( $capability ) ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log(
				array(
					'actor_type'    => 'human',
					'operation'     => 'recovery_permission_denied',
					'object_type'   => 'admin_page',
					'object_id'     => 0,
					'source_module' => 'recovery-panel',
					'status'        => 'failure',
					'error_code'    => 'igp_pro_missing_capability',
					'summary'       => 'User attempted to access recovery without capability.',
				)
			);
		}
		wp_die( esc_html__( 'You do not have permission to manage IGP Pro recovery.', 'igp-pro' ) );
	}

	$snapshots = function_exists( 'igp_list_snapshots' ) ? igp_list_snapshots( array( 'limit' => 50 ) ) : array();
	?>
	<div class="wrap igp-pro-admin-wrap">
		<h1><?php esc_html_e( 'IGP Pro Recovery', 'igp-pro' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Create and restore controlled snapshots for Phase 6 gate testing and future destructive operations.', 'igp-pro' ); ?></p>

		<?php if ( isset( $_GET['snapshot-created'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Snapshot created.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['snapshot-restored'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Snapshot restored.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['snapshot-conflict'] ) ) : ?>
			<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'Snapshot conflict detected. Review current content before forcing a restore.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>

		<div class="igp-pro-admin-card">
			<h2><?php esc_html_e( 'Create Content Graph Snapshot', 'igp-pro' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="igp_pro_create_content_graph_snapshot">
				<?php wp_nonce_field( 'igp_pro_create_content_graph_snapshot' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="igp_pro_snapshot_post_id"><?php esc_html_e( 'Post ID', 'igp-pro' ); ?></label></th>
						<td>
							<input id="igp_pro_snapshot_post_id" type="number" min="1" name="post_id" class="small-text" required>
							<p class="description"><?php esc_html_e( 'Use a page, tour, or destination ID with a saved Content Graph.', 'igp-pro' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Create snapshot', 'igp-pro' ) ); ?>
			</form>
		</div>

		<h2><?php esc_html_e( 'Recent Snapshots', 'igp-pro' ); ?></h2>
		<?php if ( empty( $snapshots ) ) : ?>
			<p><?php esc_html_e( 'No snapshots found.', 'igp-pro' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Created', 'igp-pro' ); ?></th>
						<th><?php esc_html_e( 'Snapshot ID', 'igp-pro' ); ?></th>
						<th><?php esc_html_e( 'Object', 'igp-pro' ); ?></th>
						<th><?php esc_html_e( 'Source', 'igp-pro' ); ?></th>
						<th><?php esc_html_e( 'Status', 'igp-pro' ); ?></th>
						<th><?php esc_html_e( 'Conflict', 'igp-pro' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'igp-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $snapshots as $snapshot ) : ?>
						<?php $preview = function_exists( 'igp_restore_snapshot' ) ? igp_restore_snapshot( (string) $snapshot['snapshot_id'], 'preview' ) : null; ?>
						<?php $has_conflict = is_array( $preview ) && ! empty( $preview['conflict_detected'] ); ?>
						<tr>
							<td><code><?php echo esc_html( (string) ( $snapshot['created_at'] ?? '' ) ); ?></code></td>
							<td><code><?php echo esc_html( (string) ( $snapshot['snapshot_id'] ?? '' ) ); ?></code></td>
							<td><?php echo esc_html( (string) ( $snapshot['object_type'] ?? '' ) . ':' . (string) ( $snapshot['object_id'] ?? 0 ) ); ?></td>
							<td><?php echo esc_html( (string) ( $snapshot['source_module'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $snapshot['rollback_status'] ?? '' ) ); ?></td>
							<td><?php echo $has_conflict ? esc_html__( 'Review required', 'igp-pro' ) : esc_html__( 'None detected', 'igp-pro' ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
									<input type="hidden" name="action" value="igp_pro_restore_snapshot">
									<input type="hidden" name="snapshot_id" value="<?php echo esc_attr( (string) ( $snapshot['snapshot_id'] ?? '' ) ); ?>">
									<input type="hidden" name="mode" value="<?php echo $has_conflict ? 'force' : 'safe'; ?>">
									<?php wp_nonce_field( 'igp_pro_restore_snapshot_' . (string) ( $snapshot['snapshot_id'] ?? '' ) ); ?>
									<?php submit_button( $has_conflict ? __( 'Force restore', 'igp-pro' ) : __( 'Restore', 'igp-pro' ), $has_conflict ? 'delete' : 'secondary', 'submit', false ); ?>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( function_exists( 'igp_pro_get_starter_template_import_batches' ) ) : ?>
			<hr>
			<h2><?php esc_html_e( 'Starter Template Import Recovery', 'igp-pro' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Rollback imported starter template batches. Created objects are moved to Trash when safe; merged objects are restored from their pre-merge snapshots.', 'igp-pro' ); ?></p>
			<?php if ( function_exists( 'igp_pro_render_starter_template_import_batches' ) ) : ?>
				<?php igp_pro_render_starter_template_import_batches( igp_pro_get_starter_template_import_batches() ); ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Guard recovery actions.
 */
function igp_pro_recovery_action_permission_check(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'recovery' ) : 'manage_options';

	if ( ! current_user_can( $capability ) ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log(
				array(
					'actor_type'    => is_user_logged_in() ? 'human' : 'anonymous',
					'operation'     => 'recovery_action_permission_denied',
					'object_type'   => 'admin_post',
					'object_id'     => 0,
					'source_module' => 'recovery-panel',
					'status'        => 'failure',
					'error_code'    => 'igp_pro_missing_capability',
					'summary'       => 'Recovery action denied.',
				)
			);
		}
		wp_die( esc_html__( 'You do not have permission to manage IGP Pro recovery.', 'igp-pro' ) );
	}
}

/**
 * Handle snapshot creation.
 */
function igp_pro_handle_create_content_graph_snapshot(): void {
	check_admin_referer( 'igp_pro_create_content_graph_snapshot' );
	igp_pro_recovery_action_permission_check();

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'A valid editable post ID is required.', 'igp-pro' ) );
	}

	$graph = function_exists( 'igp_pro_load_content_graph' ) ? igp_pro_load_content_graph( $post_id ) : null;
	if ( is_wp_error( $graph ) || ! is_array( $graph ) ) {
		wp_die( esc_html__( 'Content Graph could not be loaded for snapshot.', 'igp-pro' ) );
	}

	$snapshot_id = function_exists( 'igp_create_snapshot' ) ? igp_create_snapshot(
		'content_graph',
		$post_id,
		$graph,
		array(
			'source_module' => 'recovery-panel',
			'actor_type'    => 'human',
			'reason'        => 'manual_gate_test',
		)
	) : null;

	if ( is_wp_error( $snapshot_id ) || ! is_string( $snapshot_id ) ) {
		wp_die( esc_html__( 'Snapshot could not be created.', 'igp-pro' ) );
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-recovery', 'snapshot-created' => $snapshot_id ), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Handle restore.
 */
function igp_pro_handle_restore_snapshot(): void {
	$snapshot_id = isset( $_POST['snapshot_id'] ) ? sanitize_key( wp_unslash( $_POST['snapshot_id'] ) ) : '';
	check_admin_referer( 'igp_pro_restore_snapshot_' . $snapshot_id );
	igp_pro_recovery_action_permission_check();

	$mode   = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'safe';
	$result = function_exists( 'igp_restore_snapshot' ) ? igp_restore_snapshot( $snapshot_id, $mode ) : new WP_Error( 'igp_pro_rollback_missing', __( 'Rollback service is unavailable.', 'igp-pro' ) );

	if ( is_wp_error( $result ) ) {
		$data = $result->get_error_data();
		if ( is_array( $data ) && ! empty( $data['conflict_detected'] ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-recovery', 'snapshot-conflict' => $snapshot_id ), admin_url( 'admin.php' ) ) );
			exit;
		}
		wp_die( esc_html( $result->get_error_message() ) );
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-recovery', 'snapshot-restored' => $snapshot_id ), admin_url( 'admin.php' ) ) );
	exit;
}
