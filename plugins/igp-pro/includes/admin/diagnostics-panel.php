<?php
/**
 * Diagnostics panel for IGP Pro V2 logging visibility.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register diagnostics admin hooks.
 */
function igp_pro_register_diagnostics_admin(): void {
	add_action( 'admin_menu', 'igp_pro_register_diagnostics_menu' );
	add_action( 'admin_post_igp_pro_diagnostics_trigger_success', 'igp_pro_handle_diagnostics_trigger_success' );
	add_action( 'admin_post_igp_pro_diagnostics_trigger_validation_failure', 'igp_pro_handle_diagnostics_trigger_validation_failure' );
}

/**
 * Register diagnostics submenu.
 */
function igp_pro_register_diagnostics_menu(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'diagnostics' ) : 'manage_options';

	add_submenu_page(
		'igp-pro-content-editor',
		__( 'IGP Pro Diagnostics', 'igp-pro' ),
		__( 'Diagnostics', 'igp-pro' ),
		$capability,
		'igp-pro-diagnostics',
		'igp_pro_render_diagnostics_page'
	);
}

/**
 * Render diagnostics panel.
 */
function igp_pro_render_diagnostics_page(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'diagnostics' ) : 'manage_options';

	if ( ! current_user_can( $capability ) ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log(
				array(
					'actor_type'    => 'human',
					'operation'     => 'diagnostics_permission_denied',
					'object_type'   => 'admin_page',
					'object_id'     => 0,
					'source_module' => 'diagnostics-panel',
					'status'        => 'failure',
					'error_code'    => 'igp_pro_missing_capability',
					'summary'       => 'User attempted to access diagnostics without capability.',
				)
			);
		}
		wp_die( esc_html__( 'You do not have permission to view IGP Pro diagnostics.', 'igp-pro' ) );
	}

	$logs = function_exists( 'igp_pro_get_recent_logs' ) ? igp_pro_get_recent_logs( 50 ) : array();
	?>
	<div class="wrap igp-pro-admin-wrap">
		<h1><?php esc_html_e( 'IGP Pro Diagnostics', 'igp-pro' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Phase 6 diagnostics show recent structured safety logs. Logs are intended for debugging and gate validation only.', 'igp-pro' ); ?></p>

		<?php if ( isset( $_GET['diagnostic-log'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Diagnostic log entry created.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>

		<div class="igp-pro-admin-card">
			<h2><?php esc_html_e( 'Gate 21 Test Actions', 'igp-pro' ); ?></h2>
			<p><?php esc_html_e( 'Use these actions in LocalWP to create controlled success and validation-failure log entries.', 'igp-pro' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:1em;">
				<input type="hidden" name="action" value="igp_pro_diagnostics_trigger_success">
				<?php wp_nonce_field( 'igp_pro_diagnostics_trigger_success' ); ?>
				<?php submit_button( __( 'Log success test', 'igp-pro' ), 'secondary', 'submit', false ); ?>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
				<input type="hidden" name="action" value="igp_pro_diagnostics_trigger_validation_failure">
				<?php wp_nonce_field( 'igp_pro_diagnostics_trigger_validation_failure' ); ?>
				<?php submit_button( __( 'Log validation failure test', 'igp-pro' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>

		<h2><?php esc_html_e( 'Recent Logs', 'igp-pro' ); ?></h2>
		<?php if ( empty( $logs ) ) : ?>
			<p><?php esc_html_e( 'No logs found yet.', 'igp-pro' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'igp-pro' ); ?></th>
						<th><?php esc_html_e( 'Status', 'igp-pro' ); ?></th>
						<th><?php esc_html_e( 'Operation', 'igp-pro' ); ?></th>
						<th><?php esc_html_e( 'Object', 'igp-pro' ); ?></th>
						<th><?php esc_html_e( 'Module', 'igp-pro' ); ?></th>
						<th><?php esc_html_e( 'Summary', 'igp-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td><code><?php echo esc_html( (string) ( $log['timestamp'] ?? '' ) ); ?></code></td>
							<td><?php echo esc_html( (string) ( $log['status'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $log['operation'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $log['object_type'] ?? '' ) . ':' . (string) ( $log['object_id'] ?? 0 ) ); ?></td>
							<td><?php echo esc_html( (string) ( $log['source_module'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $log['summary'] ?? '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Permission guard for diagnostics test actions.
 *
 * @return bool
 */
function igp_pro_diagnostics_action_allowed(): bool {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'diagnostics' ) : 'manage_options';

	if ( current_user_can( $capability ) ) {
		return true;
	}

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => is_user_logged_in() ? 'human' : 'anonymous',
				'operation'     => 'diagnostics_action_permission_denied',
				'object_type'   => 'admin_post',
				'object_id'     => 0,
				'source_module' => 'diagnostics-panel',
				'status'        => 'failure',
				'error_code'    => 'igp_pro_missing_capability',
				'summary'       => 'Diagnostics test action denied.',
			)
		);
	}

	return false;
}

/**
 * Handle success log test.
 */
function igp_pro_handle_diagnostics_trigger_success(): void {
	check_admin_referer( 'igp_pro_diagnostics_trigger_success' );

	if ( ! igp_pro_diagnostics_action_allowed() ) {
		wp_die( esc_html__( 'You do not have permission to create diagnostic logs.', 'igp-pro' ) );
	}

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => 'human',
				'operation'     => 'diagnostics_success_test',
				'object_type'   => 'diagnostics',
				'object_id'     => 0,
				'source_module' => 'diagnostics-panel',
				'status'        => 'success',
				'summary'       => 'Manual Gate 21 success log test.',
			)
		);
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-diagnostics', 'diagnostic-log' => 'success' ), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Handle validation failure log test.
 */
function igp_pro_handle_diagnostics_trigger_validation_failure(): void {
	check_admin_referer( 'igp_pro_diagnostics_trigger_validation_failure' );

	if ( ! igp_pro_diagnostics_action_allowed() ) {
		wp_die( esc_html__( 'You do not have permission to create diagnostic logs.', 'igp-pro' ) );
	}

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => 'human',
				'operation'     => 'diagnostics_validation_failure_test',
				'object_type'   => 'diagnostics',
				'object_id'     => 0,
				'source_module' => 'diagnostics-panel',
				'status'        => 'failure',
				'error_code'    => 'igp_pro_manual_validation_failure',
				'summary'       => 'Manual Gate 21 validation failure log test.',
			)
		);
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-diagnostics', 'diagnostic-log' => 'validation-failure' ), admin_url( 'admin.php' ) ) );
	exit;
}
