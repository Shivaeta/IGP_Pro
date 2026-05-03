<?php
/**
 * Integrations panel for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register integrations admin panel.
 */
function igp_pro_register_integrations_admin(): void {
	add_action( 'admin_menu', 'igp_pro_register_integrations_menu' );
}

/**
 * Register integrations submenu.
 */
function igp_pro_register_integrations_menu(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'integrations' ) : 'manage_options';

	add_submenu_page(
		'igp-pro-content-editor',
		__( 'IGP Integrations', 'igp-pro' ),
		__( 'Integrations', 'igp-pro' ),
		$capability,
		'igp-pro-integrations',
		'igp_pro_render_integrations_page'
	);
}

/**
 * Render integrations page.
 */
function igp_pro_render_integrations_page(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'integrations' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to manage IGP integrations.', 'igp-pro' ) );
	}

	?>
	<div class="wrap igp-pro-admin-wrap igp-pro-integrations-wrap">
		<h1><?php esc_html_e( 'IGP Pro Integrations', 'igp-pro' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Optional integration bridges are adapter-driven and must degrade safely when external plugins are inactive.', 'igp-pro' ); ?></p>

		<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Integration settings saved.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['synced'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'IGP SEO data synced into Rank Math post meta for the requested post.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['sync-error'] ) ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['sync-error'] ) ) ); ?></p></div>
		<?php endif; ?>

		<?php if ( function_exists( 'igp_pro_rank_math_bridge_get_settings' ) ) : ?>
			<?php igp_pro_render_rank_math_bridge_card(); ?>
		<?php endif; ?>

		<?php if ( function_exists( 'igp_pro_link_whisper_bridge_get_settings' ) ) : ?>
			<?php igp_pro_render_link_whisper_bridge_card(); ?>
		<?php endif; ?>

		<?php if ( ! function_exists( 'igp_pro_rank_math_bridge_get_settings' ) && ! function_exists( 'igp_pro_link_whisper_bridge_get_settings' ) ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'No integration bridge feature flag is currently enabled.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render Rank Math card.
 */
function igp_pro_render_rank_math_bridge_card(): void {
	$settings       = function_exists( 'igp_pro_rank_math_bridge_get_settings' ) ? igp_pro_rank_math_bridge_get_settings() : array();
	$rank_math_on   = function_exists( 'igp_pro_rank_math_is_active' ) && igp_pro_rank_math_is_active();
	$bridge_enabled = function_exists( 'igp_pro_rank_math_bridge_enabled' ) && igp_pro_rank_math_bridge_enabled();
	$owns_output    = function_exists( 'igp_pro_rank_math_bridge_owns_frontend_output' ) && igp_pro_rank_math_bridge_owns_frontend_output();
	?>
	<div class="igp-pro-admin-card" style="max-width: 960px; background:#fff; border:1px solid #ccd0d4; padding:16px 20px; margin-top:16px;">
		<h2><?php esc_html_e( 'Rank Math Bridge', 'igp-pro' ); ?></h2>
		<table class="widefat striped" style="max-width:720px; margin-bottom:18px;">
			<tbody>
				<tr><th scope="row"><?php esc_html_e( 'Feature flag', 'igp-pro' ); ?></th><td><?php echo $bridge_enabled ? '<span style="color:#008a20">' . esc_html__( 'Enabled', 'igp-pro' ) . '</span>' : '<span style="color:#b32d2e">' . esc_html__( 'Disabled', 'igp-pro' ) . '</span>'; ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Rank Math detected', 'igp-pro' ); ?></th><td><?php echo $rank_math_on ? '<span style="color:#008a20">' . esc_html__( 'Yes', 'igp-pro' ) . '</span>' : '<span style="color:#666">' . esc_html__( 'No — IGP fallback SEO remains active.', 'igp-pro' ) . '</span>'; ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Frontend SEO ownership', 'igp-pro' ); ?></th><td><?php echo $owns_output ? esc_html__( 'Rank Math owns frontend meta/schema output; IGP suppresses duplicate direct output.', 'igp-pro' ) : esc_html__( 'IGP fallback direct SEO output remains active.', 'igp-pro' ); ?></td></tr>
			</tbody>
		</table>

		<?php if ( ! $bridge_enabled ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Enable the Rank Math Bridge feature flag in IGP Pro → Settings before configuring runtime behavior.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="igp_pro_save_rank_math_bridge">
			<?php wp_nonce_field( 'igp_pro_save_rank_math_bridge' ); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Mode', 'igp-pro' ); ?></th>
						<td>
							<label><input type="radio" name="igp_pro_rank_math_bridge[mode]" value="runtime" <?php checked( $settings['mode'] ?? 'runtime', 'runtime' ); ?>> <?php esc_html_e( 'Runtime bridge', 'igp-pro' ); ?></label><br>
							<label><input type="radio" name="igp_pro_rank_math_bridge[mode]" value="sync" <?php checked( $settings['mode'] ?? 'runtime', 'sync' ); ?>> <?php esc_html_e( 'Optional sync mode', 'igp-pro' ); ?></label>
							<p class="description"><?php esc_html_e( 'Runtime bridge is the default and does not write Rank Math post meta. Sync mode only writes post meta through explicit admin action.', 'igp-pro' ); ?></p>
						</td>
					</tr>
					<?php foreach ( array(
						'provide_meta'        => __( 'Provide title, description, canonical, and robots', 'igp-pro' ),
						'provide_open_graph'  => __( 'Provide Open Graph fields', 'igp-pro' ),
						'provide_schema'      => __( 'Provide schema graph data', 'igp-pro' ),
						'provide_breadcrumbs' => __( 'Provide breadcrumb data where supported', 'igp-pro' ),
						'provide_analysis'    => __( 'Provide Content Graph projection for Rank Math analysis', 'igp-pro' ),
					) as $key => $label ) : ?>
						<tr><th scope="row"><?php echo esc_html( $label ); ?></th><td><label><input type="checkbox" name="igp_pro_rank_math_bridge[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?>> <?php esc_html_e( 'Enabled', 'igp-pro' ); ?></label></td></tr>
					<?php endforeach; ?>
					<tr><th scope="row"><?php esc_html_e( 'Allow explicit sync writes', 'igp-pro' ); ?></th><td><label><input type="checkbox" name="igp_pro_rank_math_bridge[sync_enabled]" value="1" <?php checked( ! empty( $settings['sync_enabled'] ) ); ?>> <?php esc_html_e( 'Allow explicit admin-triggered Rank Math post meta sync', 'igp-pro' ); ?></label><p class="description"><?php esc_html_e( 'Disabled by default. Runtime mode remains non-writing.', 'igp-pro' ); ?></p></td></tr>
				</tbody>
			</table>
			<?php submit_button( __( 'Save Rank Math Bridge Settings', 'igp-pro' ) ); ?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px; border-top:1px solid #dcdcde; padding-top:16px;">
			<input type="hidden" name="action" value="igp_pro_rank_math_sync_post">
			<?php wp_nonce_field( 'igp_pro_rank_math_sync_post' ); ?>
			<h3><?php esc_html_e( 'Explicit Sync Test', 'igp-pro' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Only works when mode is Sync and explicit sync writes are enabled.', 'igp-pro' ); ?></p>
			<label for="igp-pro-rank-math-sync-post-id"><?php esc_html_e( 'Post ID', 'igp-pro' ); ?></label>
			<input id="igp-pro-rank-math-sync-post-id" type="number" min="1" name="post_id" value="" class="small-text">
			<?php submit_button( __( 'Sync This Post', 'igp-pro' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

/**
 * Render Link Whisper card.
 */
function igp_pro_render_link_whisper_bridge_card(): void {
	$settings = function_exists( 'igp_pro_link_whisper_bridge_get_settings' ) ? igp_pro_link_whisper_bridge_get_settings() : array();
	$active   = function_exists( 'igp_pro_link_whisper_is_active' ) && igp_pro_link_whisper_is_active();
	$enabled  = function_exists( 'igp_pro_link_whisper_bridge_enabled' ) && igp_pro_link_whisper_bridge_enabled();
	?>
	<div class="igp-pro-admin-card" style="max-width: 960px; background:#fff; border:1px solid #ccd0d4; padding:16px 20px; margin-top:16px;">
		<h2><?php esc_html_e( 'Link Whisper Companion Bridge', 'igp-pro' ); ?></h2>
		<table class="widefat striped" style="max-width:720px; margin-bottom:18px;">
			<tbody>
				<tr><th scope="row"><?php esc_html_e( 'Feature flag', 'igp-pro' ); ?></th><td><?php echo $enabled ? '<span style="color:#008a20">' . esc_html__( 'Enabled', 'igp-pro' ) . '</span>' : '<span style="color:#b32d2e">' . esc_html__( 'Disabled', 'igp-pro' ) . '</span>'; ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Link Whisper detected', 'igp-pro' ); ?></th><td><?php echo $active ? '<span style="color:#008a20">' . esc_html__( 'Yes', 'igp-pro' ) . '</span>' : '<span style="color:#666">' . esc_html__( 'No — IGP native internal link intelligence remains available.', 'igp-pro' ) . '</span>'; ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Write behavior', 'igp-pro' ); ?></th><td><?php esc_html_e( 'Suggestions are mapped to reviewable opportunities. Links are never inserted blindly and approved links are stored in the Content Graph.', 'igp-pro' ); ?></td></tr>
			</tbody>
		</table>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="igp_pro_save_link_whisper_bridge">
			<?php wp_nonce_field( 'igp_pro_save_link_whisper_bridge' ); ?>
			<table class="form-table" role="presentation"><tbody>
				<?php foreach ( array(
					'provide_projection'      => __( 'Expose Content Graph projection for link analysis', 'igp-pro' ),
					'map_suggestions'         => __( 'Map companion suggestions to IGP reviewable opportunities', 'igp-pro' ),
					'store_approved_in_graph' => __( 'Store approved links in the Content Graph', 'igp-pro' ),
					'expose_analysis_filters' => __( 'Expose safe filter hooks for companion workflows', 'igp-pro' ),
				) as $key => $label ) : ?>
					<tr><th scope="row"><?php echo esc_html( $label ); ?></th><td><label><input type="checkbox" name="igp_pro_link_whisper_bridge[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?>> <?php esc_html_e( 'Enabled', 'igp-pro' ); ?></label></td></tr>
				<?php endforeach; ?>
				<tr><th scope="row"><?php esc_html_e( 'Auto-insertion', 'igp-pro' ); ?></th><td><strong><?php esc_html_e( 'Disabled', 'igp-pro' ); ?></strong><p class="description"><?php esc_html_e( 'Blind auto-link insertion is intentionally not supported. Use SEO / Performance → Internal Link Intelligence to approve links.', 'igp-pro' ); ?></p></td></tr>
			</tbody></table>
			<?php submit_button( __( 'Save Link Whisper Bridge Settings', 'igp-pro' ) ); ?>
		</form>
	</div>
	<?php
}
