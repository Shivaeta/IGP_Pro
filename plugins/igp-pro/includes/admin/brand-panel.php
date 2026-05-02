<?php
/**
 * Brand profile admin panel for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register brand admin hooks.
 */
function igp_pro_register_brand_admin(): void {
	if ( function_exists( 'igp_feature_enabled' ) && ! igp_feature_enabled( 'enable_brand_engine' ) ) {
		return;
	}

	add_action( 'admin_menu', 'igp_pro_register_brand_menu' );
	add_action( 'admin_enqueue_scripts', 'igp_pro_enqueue_brand_admin_assets' );
	add_action( 'admin_post_igp_pro_save_brand_profile', 'igp_pro_handle_save_brand_profile' );
}


/**
 * Enqueue brand admin assets.
 *
 * @param string $hook Current admin hook.
 */
function igp_pro_enqueue_brand_admin_assets( string $hook ): void {
	if ( false === strpos( $hook, 'igp-pro-brand' ) ) {
		return;
	}

	$js = 'assets/js/admin-brand.js';
	if ( file_exists( igp_pro_path( $js ) ) ) {
		wp_enqueue_script( 'igp-pro-admin-brand', igp_pro_url( $js ), array(), igp_pro_asset_version( $js ), true );
	}
}

/**
 * Register brand submenu.
 */
function igp_pro_register_brand_menu(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'settings' ) : 'manage_options';

	add_submenu_page(
		'igp-pro-content-editor',
		__( 'IGP Brand Profiles', 'igp-pro' ),
		__( 'Brand', 'igp-pro' ),
		$capability,
		'igp-pro-brand',
		'igp_pro_render_brand_page'
	);
}

/**
 * Flatten token fields for rendering.
 *
 * @param array<string,array<string,string>> $tokens Tokens.
 * @return array<int,array<string,string>>
 */
function igp_pro_get_brand_token_fields( array $tokens ): array {
	$labels = array(
		'colors'     => __( 'Colors', 'igp-pro' ),
		'typography' => __( 'Typography', 'igp-pro' ),
		'spacing'    => __( 'Spacing', 'igp-pro' ),
		'radius'     => __( 'Radius', 'igp-pro' ),
		'shadow'     => __( 'Shadow', 'igp-pro' ),
		'buttons'    => __( 'Buttons', 'igp-pro' ),
		'containers' => __( 'Containers', 'igp-pro' ),
		'surfaces'   => __( 'Surfaces', 'igp-pro' ),
	);
	$fields = array();

	foreach ( $tokens as $category => $values ) {
		if ( ! is_array( $values ) ) {
			continue;
		}
		foreach ( $values as $token => $value ) {
			$fields[] = array(
				'category'       => $category,
				'category_label' => $labels[ $category ] ?? ucwords( str_replace( '_', ' ', $category ) ),
				'token'          => $token,
				'label'          => ucwords( str_replace( '_', ' ', $token ) ),
				'value'          => (string) $value,
			);
		}
	}

	return $fields;
}

/**
 * Render brand admin page.
 */
function igp_pro_render_brand_page(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'settings' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to manage IGP brand profiles.', 'igp-pro' ) );
	}

	$profiles          = function_exists( 'igp_pro_get_brand_profiles' ) ? igp_pro_get_brand_profiles() : array();
	$active_profile_id = function_exists( 'igp_pro_get_active_brand_profile_id' ) ? igp_pro_get_active_brand_profile_id() : '';
	$editing_id        = isset( $_GET['profile_id'] ) ? sanitize_key( wp_unslash( $_GET['profile_id'] ) ) : $active_profile_id;
	$editing_profile   = $editing_id && isset( $profiles[ $editing_id ] ) ? $profiles[ $editing_id ] : null;
	$tokens            = $editing_profile['tokens'] ?? igp_pro_get_default_design_tokens();
	$name              = $editing_profile['name'] ?? __( 'New Brand Profile', 'igp-pro' );
	$profile_id        = $editing_profile['profile_id'] ?? sanitize_key( $name );
	$cache             = get_option( IGP_PRO_BRAND_CSS_CACHE_OPTION, array() );
	?>
	<div class="wrap igp-pro-admin-wrap igp-pro-brand-admin">
		<h1><?php esc_html_e( 'IGP Brand Profiles', 'igp-pro' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Phase 9 brand profiles are controlled token sets. Raw CSS imports are intentionally not supported.', 'igp-pro' ); ?></p>

		<?php if ( isset( $_GET['brand-updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Brand profile saved and CSS cache invalidated/generated.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['brand-error'] ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['brand-error'] ) ) ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Profiles', 'igp-pro' ); ?></h2>
		<table class="widefat striped" style="max-width: 960px;">
			<thead><tr><th><?php esc_html_e( 'Profile', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Status', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Updated', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Action', 'igp-pro' ); ?></th></tr></thead>
			<tbody>
			<?php if ( empty( $profiles ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No brand profiles saved yet. Create one below.', 'igp-pro' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $profiles as $id => $profile ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $profile['name'] ); ?></strong><br><code><?php echo esc_html( $id ); ?></code></td>
						<td><?php echo $id === $active_profile_id ? esc_html__( 'Active', 'igp-pro' ) : esc_html__( 'Inactive', 'igp-pro' ); ?></td>
						<td><?php echo esc_html( $profile['updated_at'] ?? '' ); ?></td>
						<td><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'igp-pro-brand', 'profile_id' => $id ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'igp-pro' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Create / Update Profile', 'igp-pro' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="igp_pro_save_brand_profile">
			<?php wp_nonce_field( 'igp_pro_save_brand_profile' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="igp_brand_profile_name"><?php esc_html_e( 'Profile Name', 'igp-pro' ); ?></label></th>
					<td><input class="regular-text" id="igp_brand_profile_name" name="igp_brand_profile[name]" value="<?php echo esc_attr( $name ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="igp_brand_profile_id"><?php esc_html_e( 'Profile ID', 'igp-pro' ); ?></label></th>
					<td><input class="regular-text" id="igp_brand_profile_id" name="igp_brand_profile[profile_id]" value="<?php echo esc_attr( $profile_id ); ?>"><p class="description"><?php esc_html_e( 'Stable slug used for cache files and imports.', 'igp-pro' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Set Active', 'igp-pro' ); ?></th>
					<td><label><input type="checkbox" name="igp_brand_make_active" value="1" <?php checked( $profile_id === $active_profile_id || empty( $active_profile_id ) ); ?>> <?php esc_html_e( 'Use this profile on the frontend', 'igp-pro' ); ?></label></td>
				</tr>
			</table>

			<?php $current_category = ''; ?>
			<?php foreach ( igp_pro_get_brand_token_fields( $tokens ) as $field ) : ?>
				<?php if ( $current_category !== $field['category'] ) : ?>
					<?php if ( '' !== $current_category ) : ?></tbody></table><?php endif; ?>
					<h3><?php echo esc_html( $field['category_label'] ); ?></h3>
					<table class="form-table" role="presentation"><tbody>
					<?php $current_category = $field['category']; ?>
				<?php endif; ?>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( 'igp_token_' . $field['category'] . '_' . $field['token'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
					<td>
						<input class="regular-text igp-brand-token-field" id="<?php echo esc_attr( 'igp_token_' . $field['category'] . '_' . $field['token'] ); ?>" name="igp_brand_profile[tokens][<?php echo esc_attr( $field['category'] ); ?>][<?php echo esc_attr( $field['token'] ); ?>]" value="<?php echo esc_attr( $field['value'] ); ?>">
					</td>
				</tr>
			<?php endforeach; ?>
			<?php if ( '' !== $current_category ) : ?></tbody></table><?php endif; ?>

			<?php submit_button( __( 'Save brand profile', 'igp-pro' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'Generated CSS Cache', 'igp-pro' ); ?></h2>
		<p><strong><?php esc_html_e( 'Active profile:', 'igp-pro' ); ?></strong> <code><?php echo esc_html( $active_profile_id ?: 'none' ); ?></code></p>
		<p><strong><?php esc_html_e( 'CSS file:', 'igp-pro' ); ?></strong> <code><?php echo esc_html( is_array( $cache ) ? ( $cache['relative_path'] ?? 'not generated' ) : 'not generated' ); ?></code></p>
		<p><strong><?php esc_html_e( 'Generated at:', 'igp-pro' ); ?></strong> <code><?php echo esc_html( is_array( $cache ) ? ( $cache['generated_at'] ?? 'not generated' ) : 'not generated' ); ?></code></p>
	</div>
	<?php
}

/**
 * Handle brand profile save.
 */
function igp_pro_handle_save_brand_profile(): void {
	check_admin_referer( 'igp_pro_save_brand_profile' );

	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'settings' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to manage IGP brand profiles.', 'igp-pro' ) );
	}

	$raw_profile = isset( $_POST['igp_brand_profile'] ) && is_array( $_POST['igp_brand_profile'] ) ? wp_unslash( $_POST['igp_brand_profile'] ) : array();

	$before = array(
		'profiles' => function_exists( 'igp_pro_get_brand_profiles' ) ? igp_pro_get_brand_profiles() : array(),
		'active'   => function_exists( 'igp_pro_get_active_brand_profile_id' ) ? igp_pro_get_active_brand_profile_id() : '',
	);
	$snapshot_id = '';
	if ( function_exists( 'igp_create_snapshot' ) ) {
		$snapshot = igp_create_snapshot( 'settings', 0, $before, array( 'source_module' => 'brand_engine', 'reason' => 'brand_profile_update' ) );
		if ( is_string( $snapshot ) ) {
			$snapshot_id = $snapshot;
		}
	}

	$result = igp_pro_save_brand_profile( $raw_profile );
	if ( is_wp_error( $result ) ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-brand', 'brand-error' => rawurlencode( $result->get_error_message() ) ), admin_url( 'admin.php' ) ) );
		exit;
	}

	if ( ! empty( $_POST['igp_brand_make_active'] ) ) {
		$active_result = igp_pro_set_active_brand_profile( $result );
		if ( is_wp_error( $active_result ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-brand', 'brand-error' => rawurlencode( $active_result->get_error_message() ) ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	igp_pro_ensure_brand_css();

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log( array(
			'actor_type'    => 'human',
			'operation'     => 'brand_profile_saved',
			'object_type'   => 'brand_profile',
			'object_id'     => 0,
			'source_module' => 'brand_engine',
			'status'        => 'success',
			'summary'       => 'Brand profile saved.',
			'snapshot_id'   => $snapshot_id,
		) );
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-brand', 'profile_id' => $result, 'brand-updated' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}
