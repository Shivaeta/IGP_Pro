<?php
/**
 * SEO and Performance admin panel.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Phase 5 admin hooks.
 */
function igp_pro_register_seo_admin(): void {
	add_action( 'admin_menu', 'igp_pro_register_seo_panel_menu' );
	add_action( 'admin_enqueue_scripts', 'igp_pro_enqueue_seo_admin_assets' );
	add_action( 'admin_post_igp_pro_save_seo_settings', 'igp_pro_handle_save_seo_settings' );
	add_action( 'admin_post_igp_pro_purge_performance_cache', 'igp_pro_handle_purge_performance_cache' );
}

/**
 * Register submenu under IGP Pro.
 */
function igp_pro_register_seo_panel_menu(): void {
	add_submenu_page(
		'igp-pro-content-editor',
		__( 'SEO / Performance', 'igp-pro' ),
		__( 'SEO / Performance', 'igp-pro' ),
		function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'seo' ) : 'manage_options',
		'igp-pro-seo-performance',
		'igp_pro_render_seo_panel_page'
	);
}

/**
 * Enqueue admin CSS.
 */
function igp_pro_enqueue_seo_admin_assets( string $hook ): void {
	if ( false === strpos( $hook, 'igp-pro-seo-performance' ) ) {
		return;
	}

	$css = 'assets/css/seo-admin.css';
	if ( file_exists( igp_pro_path( $css ) ) ) {
		wp_enqueue_style( 'igp-pro-seo-admin', igp_pro_url( $css ), array(), function_exists( 'igp_pro_asset_version' ) ? igp_pro_asset_version( $css ) : IGP_PRO_VERSION );
	}
}

/**
 * Render SEO panel.
 */
function igp_pro_render_seo_panel_page(): void {
	if ( ! current_user_can( function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'seo' ) : 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage SEO and performance settings.', 'igp-pro' ) );
	}

	$seo_settings  = function_exists( 'igp_pro_get_seo_settings' ) ? igp_pro_get_seo_settings() : array();
	$perf_settings = function_exists( 'igp_pro_get_performance_settings' ) ? igp_pro_get_performance_settings() : array();
	$cwv_settings  = function_exists( 'igp_pro_get_cwv_settings' ) ? igp_pro_get_cwv_settings() : array();
	$selected_id   = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
	$selected_url  = isset( $_GET['cwv_url'] ) ? esc_url_raw( wp_unslash( $_GET['cwv_url'] ) ) : ( $selected_id > 0 ? get_permalink( $selected_id ) : home_url( '/' ) );
	$strategy      = isset( $_GET['strategy'] ) && 'desktop' === sanitize_key( (string) $_GET['strategy'] ) ? 'desktop' : 'mobile';
	$audit         = $selected_id > 0 && function_exists( 'igp_pro_run_seo_audit' ) ? igp_pro_run_seo_audit( $selected_id ) : null;
	$cwv_report    = null;
	if ( isset( $_GET['igp_fetch_cwv'] ) && check_admin_referer( 'igp_pro_fetch_cwv' ) && function_exists( 'igp_pro_cwv_get_report' ) ) {
		$cwv_report = igp_pro_cwv_get_report( $selected_url, $strategy, isset( $_GET['force'] ) );
	}
	?>
	<div class="wrap igp-seo-admin-wrap">
		<h1><?php esc_html_e( 'IGP Pro SEO / Performance', 'igp-pro' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Phase 5 controls for structured SEO, JSON-LD, Core Web Vitals, and cache discipline.', 'igp-pro' ); ?></p>

		<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Settings saved.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['cache-purged'] ) ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'IGP Pro cache invalidated.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>

		<div class="igp-seo-grid">
			<section class="igp-seo-card">
				<h2><?php esc_html_e( 'SEO Output Settings', 'igp-pro' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'igp_pro_save_seo_settings' ); ?>
					<input type="hidden" name="action" value="igp_pro_save_seo_settings">
					<label><input type="checkbox" name="enable_meta" value="yes" <?php checked( $seo_settings['enable_meta'] ?? 'yes', 'yes' ); ?>> <?php esc_html_e( 'Output meta description', 'igp-pro' ); ?></label>
					<label><input type="checkbox" name="enable_open_graph" value="yes" <?php checked( $seo_settings['enable_open_graph'] ?? 'yes', 'yes' ); ?>> <?php esc_html_e( 'Output Open Graph / Twitter tags', 'igp-pro' ); ?></label>
					<label><input type="checkbox" name="enable_json_ld" value="yes" <?php checked( $seo_settings['enable_json_ld'] ?? 'yes', 'yes' ); ?>> <?php esc_html_e( 'Output JSON-LD structured data', 'igp-pro' ); ?></label>
					<label><?php esc_html_e( 'Organization name', 'igp-pro' ); ?><input type="text" name="organization_name" value="<?php echo esc_attr( (string) ( $seo_settings['organization_name'] ?? get_bloginfo( 'name' ) ) ); ?>"></label>
					<label><?php esc_html_e( 'Organization logo URL', 'igp-pro' ); ?><input type="url" name="organization_logo" value="<?php echo esc_attr( (string) ( $seo_settings['organization_logo'] ?? '' ) ); ?>"></label>
					<hr>
					<label><input type="checkbox" name="enable_block_cache" value="yes" <?php checked( $perf_settings['enable_block_cache'] ?? 'yes', 'yes' ); ?>> <?php esc_html_e( 'Enable SSR block output cache', 'igp-pro' ); ?></label>
					<label><input type="checkbox" name="enable_page_cache" value="yes" <?php checked( $perf_settings['enable_page_cache'] ?? 'yes', 'yes' ); ?>> <?php esc_html_e( 'Enable safe anonymous page cache', 'igp-pro' ); ?></label>
					<label><?php esc_html_e( 'Block cache TTL seconds', 'igp-pro' ); ?><input type="number" min="60" step="60" name="block_cache_ttl" value="<?php echo esc_attr( (string) ( $perf_settings['block_cache_ttl'] ?? 3600 ) ); ?>"></label>
					<label><?php esc_html_e( 'Page cache TTL seconds', 'igp-pro' ); ?><input type="number" min="60" step="60" name="page_cache_ttl" value="<?php echo esc_attr( (string) ( $perf_settings['page_cache_ttl'] ?? 900 ) ); ?>"></label>
					<label><?php esc_html_e( 'CWV cache TTL seconds', 'igp-pro' ); ?><input type="number" min="300" step="300" name="cwv_cache_ttl" value="<?php echo esc_attr( (string) ( $perf_settings['cwv_cache_ttl'] ?? 43200 ) ); ?>"></label>
					<hr>
					<label><?php esc_html_e( 'PageSpeed API key', 'igp-pro' ); ?><input type="password" name="cwv_api_key" value="<?php echo esc_attr( (string) ( $cwv_settings['api_key'] ?? '' ) ); ?>" autocomplete="off"></label>
					<label><?php esc_html_e( 'Default CWV strategy', 'igp-pro' ); ?>
						<select name="cwv_default_strategy">
							<option value="mobile" <?php selected( $cwv_settings['default_strategy'] ?? 'mobile', 'mobile' ); ?>><?php esc_html_e( 'Mobile', 'igp-pro' ); ?></option>
							<option value="desktop" <?php selected( $cwv_settings['default_strategy'] ?? 'mobile', 'desktop' ); ?>><?php esc_html_e( 'Desktop', 'igp-pro' ); ?></option>
						</select>
					</label>
					<?php submit_button( __( 'Save SEO / Performance Settings', 'igp-pro' ) ); ?>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="igp-seo-inline-form">
					<?php wp_nonce_field( 'igp_pro_purge_performance_cache' ); ?>
					<input type="hidden" name="action" value="igp_pro_purge_performance_cache">
					<?php submit_button( __( 'Purge IGP Cache', 'igp-pro' ), 'secondary', 'submit', false ); ?>
				</form>
			</section>

			<section class="igp-seo-card igp-seo-card--wide">
				<h2><?php esc_html_e( 'SEO Health Dashboard', 'igp-pro' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Phase 8.4 structured checks for H1, heading hierarchy, metadata, schema, image alt text, internal links, orphan risk, and CWV/cache status.', 'igp-pro' ); ?></p>
				<form method="get" class="igp-seo-audit-form">
					<input type="hidden" name="page" value="igp-pro-seo-performance">
					<label><?php esc_html_e( 'Post/Page/Tour/Destination ID', 'igp-pro' ); ?><input type="number" min="1" name="post_id" value="<?php echo esc_attr( (string) $selected_id ); ?>"></label>
					<?php submit_button( __( 'Run SEO Health Audit', 'igp-pro' ), 'secondary', 'submit', false ); ?>
				</form>

				<?php igp_pro_render_seo_audit_results( $audit, $selected_id ); ?>
			</section>

			<section class="igp-seo-card igp-seo-card--wide">
				<h2><?php esc_html_e( 'Core Web Vitals / PageSpeed', 'igp-pro' ); ?></h2>
				<form method="get">
					<input type="hidden" name="page" value="igp-pro-seo-performance">
					<input type="hidden" name="igp_fetch_cwv" value="1">
					<?php wp_nonce_field( 'igp_pro_fetch_cwv' ); ?>
					<label><?php esc_html_e( 'URL', 'igp-pro' ); ?><input type="url" name="cwv_url" value="<?php echo esc_attr( (string) $selected_url ); ?>"></label>
					<label><?php esc_html_e( 'Strategy', 'igp-pro' ); ?>
						<select name="strategy">
							<option value="mobile" <?php selected( $strategy, 'mobile' ); ?>><?php esc_html_e( 'Mobile', 'igp-pro' ); ?></option>
							<option value="desktop" <?php selected( $strategy, 'desktop' ); ?>><?php esc_html_e( 'Desktop', 'igp-pro' ); ?></option>
						</select>
					</label>
					<label class="igp-seo-checkbox"><input type="checkbox" name="force" value="1"> <?php esc_html_e( 'Force refresh', 'igp-pro' ); ?></label>
					<?php submit_button( __( 'Fetch CWV Data', 'igp-pro' ), 'secondary', 'submit', false ); ?>
				</form>

				<?php igp_pro_render_cwv_report_card( $cwv_report ); ?>
			</section>
		</div>
	</div>
	<?php
}


/**
 * Render the Phase 8.4 SEO audit dashboard results.
 *
 * @param mixed $audit Audit result.
 * @param int   $selected_id Selected post ID.
 */
function igp_pro_render_seo_audit_results( $audit, int $selected_id ): void {
	if ( null === $audit ) {
		return;
	}

	if ( ! is_array( $audit ) ) {
		printf( '<div class="notice notice-warning inline"><p>%s</p></div>', esc_html__( 'SEO audit did not return a readable result.', 'igp-pro' ) );
		return;
	}

	$score = isset( $audit['score'] ) ? absint( $audit['score'] ) : 0;
	$source = isset( $audit['frontend_html']['source'] ) ? (string) $audit['frontend_html']['source'] : '';
	$error  = isset( $audit['frontend_html']['error'] ) ? (string) $audit['frontend_html']['error'] : '';
	?>
	<div class="igp-seo-audit-summary">
		<div class="igp-seo-audit-score">
			<strong><?php echo esc_html( (string) $score ); ?></strong>
			<span><?php esc_html_e( 'Health score', 'igp-pro' ); ?></span>
		</div>
		<div>
			<h3><?php echo esc_html( get_the_title( $selected_id ) ); ?></h3>
			<?php if ( ! empty( $audit['permalink'] ) ) : ?>
				<p><a href="<?php echo esc_url( (string) $audit['permalink'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) $audit['permalink'] ); ?></a></p>
			<?php endif; ?>
			<p><strong><?php esc_html_e( 'HTML source checked:', 'igp-pro' ); ?></strong> <?php echo esc_html( '' !== $source ? $source : 'not available' ); ?></p>
			<?php if ( '' !== $error ) : ?>
				<p class="igp-seo-warning"><?php echo esc_html( sprintf( __( 'Frontend fetch fallback reason: %s', 'igp-pro' ), $error ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! empty( $audit['groups'] ) && is_array( $audit['groups'] ) ) : ?>
		<div class="igp-seo-audit-groups">
			<?php foreach ( $audit['groups'] as $group ) : ?>
				<?php if ( empty( $group['checks'] ) || ! is_array( $group['checks'] ) ) { continue; } ?>
				<section class="igp-seo-audit-group">
					<h3><?php echo esc_html( (string) ( $group['label'] ?? '' ) ); ?></h3>
					<ul class="igp-seo-checks">
						<?php foreach ( $group['checks'] as $check ) : ?>
							<li class="is-<?php echo esc_attr( (string) ( $check['status'] ?? 'info' ) ); ?>">
								<strong><?php echo esc_html( (string) ( $check['label'] ?? '' ) ); ?></strong>
								<?php if ( ! empty( $check['detail'] ) ) : ?>
									<span><?php echo esc_html( (string) $check['detail'] ); ?></span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
		</div>
	<?php elseif ( ! empty( $audit['checks'] ) && is_array( $audit['checks'] ) ) : ?>
		<ul class="igp-seo-checks">
			<?php foreach ( $audit['checks'] as $check ) : ?>
				<li class="is-<?php echo esc_attr( (string) ( $check['status'] ?? 'info' ) ); ?>"><?php echo esc_html( (string) ( $check['label'] ?? '' ) ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<h3><?php esc_html_e( 'Internal linking hints', 'igp-pro' ); ?></h3>
	<?php if ( ! empty( $audit['hints'] ) ) : ?>
		<ul class="igp-seo-link-hints">
			<?php foreach ( $audit['hints'] as $hint ) : ?>
				<li><a href="<?php echo esc_url( (string) $hint['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) $hint['title'] ); ?></a> <span><?php echo esc_html( (string) $hint['type'] ); ?></span></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p><?php esc_html_e( 'No obvious internal-link hints found.', 'igp-pro' ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * Render CWV report card.
 */
function igp_pro_render_cwv_report_card( $report ): void {
	if ( null === $report ) {
		return;
	}

	if ( is_wp_error( $report ) ) {
		printf( '<div class="notice notice-warning inline"><p>%s</p></div>', esc_html( $report->get_error_message() ) );
		return;
	}

	if ( ! is_array( $report ) ) {
		return;
	}
	?>
	<div class="igp-cwv-report">
		<div class="igp-cwv-score">
			<strong><?php echo esc_html( isset( $report['performance'] ) && null !== $report['performance'] ? (string) $report['performance'] : '—' ); ?></strong>
			<span><?php esc_html_e( 'Performance', 'igp-pro' ); ?></span>
		</div>
		<div>
			<p><strong><?php esc_html_e( 'URL:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) ( $report['url'] ?? '' ) ); ?></p>
			<p><strong><?php esc_html_e( 'Strategy:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) ( $report['strategy'] ?? '' ) ); ?> · <strong><?php esc_html_e( 'Cache:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) ( $report['cache_status'] ?? '' ) ); ?></p>
			<?php if ( ! empty( $report['warning'] ) ) : ?><p class="igp-seo-warning"><?php echo esc_html( (string) $report['warning'] ); ?></p><?php endif; ?>
		</div>
	</div>
	<?php if ( ! empty( $report['metrics'] ) && is_array( $report['metrics'] ) ) : ?>
		<table class="widefat striped igp-cwv-table">
			<thead><tr><th><?php esc_html_e( 'Metric', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Value', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Score', 'igp-pro' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $report['metrics'] as $metric ) : ?>
					<tr><td><?php echo esc_html( (string) ( $metric['label'] ?? '' ) ); ?></td><td><?php echo esc_html( (string) ( $metric['displayValue'] ?? '' ) ); ?></td><td><?php echo esc_html( isset( $metric['score'] ) && null !== $metric['score'] ? (string) $metric['score'] : '—' ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<?php
}

/**
 * Save settings.
 */
function igp_pro_handle_save_seo_settings(): void {
	if ( ! current_user_can( function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'seo' ) : 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'igp-pro' ) );
	}
	check_admin_referer( 'igp_pro_save_seo_settings' );

	$seo_settings = array(
		'enable_meta'       => isset( $_POST['enable_meta'] ) ? 'yes' : 'no',
		'enable_open_graph' => isset( $_POST['enable_open_graph'] ) ? 'yes' : 'no',
		'enable_json_ld'    => isset( $_POST['enable_json_ld'] ) ? 'yes' : 'no',
		'organization_name' => isset( $_POST['organization_name'] ) ? sanitize_text_field( wp_unslash( $_POST['organization_name'] ) ) : get_bloginfo( 'name' ),
		'organization_logo' => isset( $_POST['organization_logo'] ) ? esc_url_raw( wp_unslash( $_POST['organization_logo'] ) ) : '',
	);
	update_option( 'igp_pro_seo_settings', $seo_settings, false );

	$performance_settings = array(
		'enable_block_cache' => isset( $_POST['enable_block_cache'] ) ? 'yes' : 'no',
		'enable_page_cache'  => isset( $_POST['enable_page_cache'] ) ? 'yes' : 'no',
		'block_cache_ttl'    => isset( $_POST['block_cache_ttl'] ) ? max( 60, absint( $_POST['block_cache_ttl'] ) ) : 3600,
		'page_cache_ttl'     => isset( $_POST['page_cache_ttl'] ) ? max( 60, absint( $_POST['page_cache_ttl'] ) ) : 900,
		'query_cache_ttl'    => 900,
		'cwv_cache_ttl'      => isset( $_POST['cwv_cache_ttl'] ) ? max( 300, absint( $_POST['cwv_cache_ttl'] ) ) : 43200,
	);
	update_option( 'igp_pro_performance_settings', $performance_settings, false );

	$cwv_settings = array(
		'api_key'          => isset( $_POST['cwv_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cwv_api_key'] ) ) : '',
		'default_strategy' => isset( $_POST['cwv_default_strategy'] ) && 'desktop' === sanitize_key( (string) $_POST['cwv_default_strategy'] ) ? 'desktop' : 'mobile',
		'auto_fetch'       => 'no',
	);
	update_option( 'igp_pro_cwv_settings', $cwv_settings, false );

	if ( function_exists( 'igp_pro_cache_invalidate' ) ) {
		igp_pro_cache_invalidate( 'settings' );
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-seo-performance', 'settings-updated' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Purge cache.
 */
function igp_pro_handle_purge_performance_cache(): void {
	if ( ! current_user_can( function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'seo' ) : 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'igp-pro' ) );
	}
	check_admin_referer( 'igp_pro_purge_performance_cache' );

	if ( function_exists( 'igp_pro_cache_invalidate' ) ) {
		igp_pro_cache_invalidate( 'manual' );
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-seo-performance', 'cache-purged' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}
