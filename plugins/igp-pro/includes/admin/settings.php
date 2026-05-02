<?php
/**
 * IGP Pro V2 settings screen.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the V2 settings admin hooks.
 */
function igp_pro_register_settings_admin(): void {
	add_action( 'admin_menu', 'igp_pro_register_settings_menu' );
	add_action( 'admin_post_igp_pro_save_feature_flags', 'igp_pro_handle_save_feature_flags' );
}

/**
 * Register settings submenu under IGP Pro.
 */
function igp_pro_register_settings_menu(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'settings' ) : 'manage_options';

	add_submenu_page(
		'igp-pro-content-editor',
		__( 'IGP Pro Settings', 'igp-pro' ),
		__( 'Settings', 'igp-pro' ),
		$capability,
		'igp-pro-settings',
		'igp_pro_render_settings_page'
	);
}

/**
 * Render the V2 settings screen.
 */
function igp_pro_render_settings_page(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'settings' ) : 'manage_options';

	if ( ! current_user_can( $capability ) ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log(
				array(
					'actor_type'    => 'human',
					'operation'     => 'settings_permission_denied',
					'object_type'   => 'admin_page',
					'object_id'     => 0,
					'source_module' => 'settings',
					'status'        => 'failure',
					'error_code'    => 'igp_pro_missing_capability',
					'summary'       => 'User attempted to access settings without capability.',
				)
			);
		}
		wp_die( esc_html__( 'You do not have permission to manage IGP Pro settings.', 'igp-pro' ) );
	}

	$definitions      = function_exists( 'igp_pro_get_feature_flag_definitions' ) ? igp_pro_get_feature_flag_definitions() : array();
	$flags            = function_exists( 'igp_get_feature_flags' ) ? igp_get_feature_flags() : array();
	$cap_definitions  = function_exists( 'igp_pro_get_capability_definitions' ) ? igp_pro_get_capability_definitions() : array();
	$managed_roles    = function_exists( 'igp_pro_get_managed_capability_roles' ) ? igp_pro_get_managed_capability_roles() : array();
	$role_grant_caps  = function_exists( 'igp_pro_get_restricted_role_grantable_capabilities' ) ? igp_pro_get_restricted_role_grantable_capabilities() : array();
	$role_grants      = function_exists( 'igp_pro_get_role_capability_grants' ) ? igp_pro_get_role_capability_grants() : array();
	?>
	<div class="wrap igp-pro-admin-wrap">
		<h1><?php esc_html_e( 'IGP Pro Settings', 'igp-pro' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Phase 6 controls for safe V2 feature flags and explicit restricted-role capability grants. Later V2 modules remain inactive until their implementation phase and gate pass.', 'igp-pro' ); ?>
		</p>

		<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'IGP Pro settings saved.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>

		<?php if ( empty( $definitions ) ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'Feature flag registry is unavailable.', 'igp-pro' ); ?></p></div>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="igp_pro_save_feature_flags">
				<?php wp_nonce_field( 'igp_pro_save_feature_flags' ); ?>

				<h2><?php esc_html_e( 'V2 Feature Flags', 'igp-pro' ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
						<?php foreach ( $definitions as $flag => $definition ) : ?>
							<tr>
								<th scope="row">
									<label for="<?php echo esc_attr( 'igp_pro_flag_' . $flag ); ?>">
										<?php echo esc_html( $definition['label'] ?? $flag ); ?>
									</label>
								</th>
								<td>
									<label>
										<input
											id="<?php echo esc_attr( 'igp_pro_flag_' . $flag ); ?>"
											type="checkbox"
											name="igp_pro_feature_flags[<?php echo esc_attr( $flag ); ?>]"
											value="1"
											<?php checked( ! empty( $flags[ $flag ] ) ); ?>
										>
										<?php esc_html_e( 'Enabled', 'igp-pro' ); ?>
									</label>
									<?php if ( ! empty( $definition['description'] ) ) : ?>
										<p class="description"><?php echo esc_html( $definition['description'] ); ?></p>
									<?php endif; ?>
									<code><?php echo esc_html( $flag ); ?></code>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Restricted Role Access', 'igp-pro' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Administrators receive all IGP capabilities. Content editors receive IGP access only when explicitly granted here.', 'igp-pro' ); ?></p>
				<?php if ( ! empty( $managed_roles ) && ! empty( $role_grant_caps ) ) : ?>
					<table class="widefat striped" style="max-width: 900px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Role', 'igp-pro' ); ?></th>
								<?php foreach ( $role_grant_caps as $cap_slug ) : ?>
									<th><?php echo esc_html( $cap_definitions[ $cap_slug ]['label'] ?? $cap_slug ); ?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $managed_roles as $role_slug => $role_label ) : ?>
								<tr>
									<th scope="row"><?php echo esc_html( $role_label ); ?> <code><?php echo esc_html( $role_slug ); ?></code></th>
									<?php foreach ( $role_grant_caps as $cap_slug ) : ?>
										<td>
											<label>
												<input
													type="checkbox"
													name="igp_pro_role_capabilities[<?php echo esc_attr( $role_slug ); ?>][<?php echo esc_attr( $cap_slug ); ?>]"
													value="1"
													<?php checked( in_array( $cap_slug, $role_grants[ $role_slug ] ?? array(), true ) ); ?>
												>
												<?php echo esc_html( $cap_definitions[ $cap_slug ]['description'] ?? $cap_slug ); ?>
											</label>
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<?php submit_button( __( 'Save IGP Pro settings', 'igp-pro' ) ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Save controlled V2 feature flags and explicit role grants.
 */
function igp_pro_handle_save_feature_flags(): void {
	check_admin_referer( 'igp_pro_save_feature_flags' );

	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'settings' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log(
				array(
					'actor_type'    => is_user_logged_in() ? 'human' : 'anonymous',
					'operation'     => 'settings_update_permission_denied',
					'object_type'   => 'settings',
					'object_id'     => 0,
					'source_module' => 'settings',
					'status'        => 'failure',
					'error_code'    => 'igp_pro_missing_capability',
					'summary'       => 'Settings update denied.',
				)
			);
		}
		wp_die( esc_html__( 'You do not have permission to manage IGP Pro settings.', 'igp-pro' ) );
	}

	$before = array(
		'feature_flags'     => function_exists( 'igp_get_feature_flags' ) ? igp_get_feature_flags() : array(),
		'role_capabilities' => function_exists( 'igp_pro_get_role_capability_grants' ) ? igp_pro_get_role_capability_grants() : array(),
	);
	$snapshot_id = '';
	if ( function_exists( 'igp_create_snapshot' ) ) {
		$snapshot = igp_create_snapshot(
			'settings',
			0,
			$before,
			array(
				'source_module' => 'settings',
				'actor_type'    => 'human',
				'reason'        => 'settings_update',
			)
		);
		if ( is_string( $snapshot ) ) {
			$snapshot_id = $snapshot;
		}
	}

	$raw_flags = isset( $_POST['igp_pro_feature_flags'] ) && is_array( $_POST['igp_pro_feature_flags'] ) ? wp_unslash( $_POST['igp_pro_feature_flags'] ) : array();
	if ( function_exists( 'igp_pro_update_feature_flags' ) ) {
		igp_pro_update_feature_flags( $raw_flags );
	}

	$raw_role_caps = isset( $_POST['igp_pro_role_capabilities'] ) && is_array( $_POST['igp_pro_role_capabilities'] ) ? wp_unslash( $_POST['igp_pro_role_capabilities'] ) : array();
	if ( function_exists( 'igp_pro_update_role_capability_grants' ) ) {
		igp_pro_update_role_capability_grants( $raw_role_caps );
	}

	$after = array(
		'feature_flags'     => function_exists( 'igp_get_feature_flags' ) ? igp_get_feature_flags() : array(),
		'role_capabilities' => function_exists( 'igp_pro_get_role_capability_grants' ) ? igp_pro_get_role_capability_grants() : array(),
	);
	if ( '' !== $snapshot_id && function_exists( 'igp_pro_update_snapshot_after_data' ) ) {
		igp_pro_update_snapshot_after_data( $snapshot_id, $after );
	}

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => 'human',
				'operation'     => 'settings_updated',
				'object_type'   => 'settings',
				'object_id'     => 0,
				'source_module' => 'settings',
				'status'        => 'success',
				'summary'       => 'IGP Pro settings updated.',
				'snapshot_id'   => $snapshot_id,
			)
		);
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-settings', 'settings-updated' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}
