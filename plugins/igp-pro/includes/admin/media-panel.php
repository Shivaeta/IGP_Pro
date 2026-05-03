<?php
/**
 * Media optimization admin panel for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register media admin hooks.
 */
function igp_pro_register_media_admin(): void {
	if ( function_exists( 'igp_feature_enabled' ) && ! igp_feature_enabled( 'enable_media_optimizer' ) ) {
		return;
	}

	add_action( 'admin_menu', 'igp_pro_register_media_menu' );
	add_action( 'admin_enqueue_scripts', 'igp_pro_enqueue_media_admin_assets' );
	add_action( 'admin_post_igp_pro_media_bulk_alt_update', 'igp_pro_handle_media_bulk_alt_update' );
	add_action( 'admin_post_igp_pro_generate_webp', 'igp_pro_handle_generate_webp' );
}

/**
 * Register media submenu.
 */
function igp_pro_register_media_menu(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'media' ) : 'manage_options';

	add_submenu_page(
		'igp-pro-content-editor',
		__( 'IGP Media', 'igp-pro' ),
		__( 'Media', 'igp-pro' ),
		$capability,
		'igp-pro-media',
		'igp_pro_render_media_panel_page'
	);
}

/**
 * Enqueue media admin assets.
 */
function igp_pro_enqueue_media_admin_assets( string $hook ): void {
	if ( false === strpos( $hook, 'igp-pro-media' ) ) {
		return;
	}

	$js = 'assets/js/admin-media.js';
	if ( file_exists( igp_pro_path( $js ) ) ) {
		wp_enqueue_script( 'igp-pro-admin-media', igp_pro_url( $js ), array(), function_exists( 'igp_pro_asset_version' ) ? igp_pro_asset_version( $js ) : IGP_PRO_VERSION, true );
	}
}

/**
 * Render media panel page.
 */
function igp_pro_render_media_panel_page(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'media' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to manage IGP media optimization.', 'igp-pro' ) );
	}

	$post_id   = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
	$inventory = null;
	$audit     = null;

	if ( $post_id > 0 && function_exists( 'igp_pro_get_media_inventory' ) ) {
		$inventory = igp_pro_get_media_inventory( $post_id, array( 'force_refresh' => isset( $_GET['force_refresh'] ) ) );
	}
	if ( $post_id > 0 && function_exists( 'igp_pro_run_media_audit' ) ) {
		$audit = igp_pro_run_media_audit( $post_id, array( 'force_refresh' => isset( $_GET['force_refresh'] ) ) );
	}
	?>
	<div class="wrap igp-pro-admin-wrap igp-pro-media-admin">
		<h1><?php esc_html_e( 'IGP Media SEO / Optimization', 'igp-pro' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Phase 12 tools for page-level media inventory, accessibility/SEO audits, lazy-loading policy checks, and manual WebP generation.', 'igp-pro' ); ?></p>

		<?php igp_pro_render_media_notices(); ?>

		<?php if ( function_exists( 'igp_feature_enabled' ) && ! igp_feature_enabled( 'enable_media_optimizer' ) ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Media optimizer feature flag is disabled.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>

		<form method="get" style="margin: 16px 0; padding: 16px; background: #fff; border: 1px solid #ccd0d4; max-width: 900px;">
			<input type="hidden" name="page" value="igp-pro-media">
			<label for="igp-media-post-id"><strong><?php esc_html_e( 'Page/Tour/Destination ID', 'igp-pro' ); ?></strong></label>
			<input id="igp-media-post-id" type="number" min="1" name="post_id" value="<?php echo esc_attr( (string) $post_id ); ?>">
			<label><input type="checkbox" name="force_refresh" value="1"> <?php esc_html_e( 'Force inventory refresh', 'igp-pro' ); ?></label>
			<?php submit_button( __( 'Run Media Inventory / Audit', 'igp-pro' ), 'secondary', 'submit', false ); ?>
		</form>

		<?php if ( $post_id > 0 ) : ?>
			<h2><?php echo esc_html( sprintf( __( 'Media report for #%1$d — %2$s', 'igp-pro' ), $post_id, get_the_title( $post_id ) ) ); ?></h2>
			<?php igp_pro_render_media_inventory( $inventory ); ?>
			<?php igp_pro_render_media_audit( $audit, $post_id ); ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render admin notices.
 */
function igp_pro_render_media_notices(): void {
	if ( isset( $_GET['igp_media_alt_updated'] ) ) {
		printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( sprintf( __( 'Updated alt text for %d attachment(s).', 'igp-pro' ), absint( $_GET['igp_media_alt_updated'] ) ) ) );
	}
	if ( isset( $_GET['igp_media_webp'] ) ) {
		printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html__( 'WebP generation completed.', 'igp-pro' ) );
	}
	if ( isset( $_GET['igp_media_error'] ) ) {
		printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( sanitize_text_field( wp_unslash( $_GET['igp_media_error'] ) ) ) );
	}
}

/**
 * Render inventory table.
 *
 * @param mixed $inventory Inventory.
 */
function igp_pro_render_media_inventory( $inventory ): void {
	if ( is_wp_error( $inventory ) ) {
		printf( '<div class="notice notice-error inline"><p>%s</p></div>', esc_html( $inventory->get_error_message() ) );
		return;
	}
	if ( ! is_array( $inventory ) ) {
		return;
	}

	$images = isset( $inventory['images'] ) && is_array( $inventory['images'] ) ? $inventory['images'] : array();
	$counts = isset( $inventory['counts'] ) && is_array( $inventory['counts'] ) ? $inventory['counts'] : array();
	?>
	<section style="background:#fff;border:1px solid #ccd0d4;padding:16px;margin:16px 0;max-width:1280px;">
		<h2><?php esc_html_e( 'Media Inventory', 'igp-pro' ); ?></h2>
		<p>
			<strong><?php esc_html_e( 'Total:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) ( $counts['total'] ?? count( $images ) ) ); ?>
			&nbsp; <strong><?php esc_html_e( 'Attachment-backed:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) ( $counts['with_attachment_id'] ?? 0 ) ); ?>
			&nbsp; <strong><?php esc_html_e( 'Missing/deleted:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) ( $counts['missing'] ?? 0 ) ); ?>
		</p>
		<?php if ( ! empty( $inventory['lcp_candidate'] ) && is_array( $inventory['lcp_candidate'] ) ) : ?>
			<p><strong><?php esc_html_e( 'Likely LCP image:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) ( $inventory['lcp_candidate']['context'] ?? $inventory['lcp_candidate']['source'] ?? '' ) ); ?></p>
		<?php endif; ?>

		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Source', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Attachment', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Image', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Alt', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Dimensions', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Policy', 'igp-pro' ); ?></th><th><?php esc_html_e( 'WebP', 'igp-pro' ); ?></th></tr></thead>
			<tbody>
			<?php if ( empty( $images ) ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No images detected.', 'igp-pro' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $images as $image ) : ?>
					<tr>
						<td><strong><?php echo esc_html( (string) ( $image['source'] ?? '' ) ); ?></strong><br><small><?php echo esc_html( (string) ( $image['context'] ?? $image['path'] ?? '' ) ); ?></small><?php if ( ! empty( $image['is_lcp'] ) ) : ?><br><mark><?php esc_html_e( 'LCP candidate', 'igp-pro' ); ?></mark><?php endif; ?></td>
						<td><?php echo ! empty( $image['attachment_id'] ) ? esc_html( '#' . (string) $image['attachment_id'] ) : ( ! empty( $image['placeholder'] ) ? esc_html__( 'Placeholder', 'igp-pro' ) : esc_html__( 'External/URL', 'igp-pro' ) ); ?></td>
						<td><?php if ( ! empty( $image['url'] ) ) : ?><a href="<?php echo esc_url( (string) $image['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wp_basename( (string) $image['url'] ) ); ?></a><?php else : ?>—<?php endif; ?></td>
						<td><?php echo '' !== (string) ( $image['alt'] ?? '' ) ? esc_html( (string) $image['alt'] ) : '<span style="color:#b32d2e;">' . esc_html__( 'Missing', 'igp-pro' ) . '</span>'; ?></td>
						<td><?php echo absint( $image['width'] ?? 0 ) && absint( $image['height'] ?? 0 ) ? esc_html( absint( $image['width'] ) . ' × ' . absint( $image['height'] ) ) : '—'; ?></td>
						<td><?php echo esc_html( (string) ( $image['loading_policy'] ?? '' ) ); ?></td>
						<td><?php igp_pro_render_media_webp_action( $image ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</section>
	<?php
}

/**
 * Render WebP form action for a single image.
 */
function igp_pro_render_media_webp_action( array $image ): void {
	$attachment_id = absint( $image['attachment_id'] ?? 0 );
	if ( $attachment_id <= 0 ) {
		echo '—';
		return;
	}

	$webp_url = (string) get_post_meta( $attachment_id, '_igp_webp_url', true );
	if ( '' !== $webp_url ) {
		printf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a><br>', esc_url( $webp_url ), esc_html__( 'WebP exists', 'igp-pro' ) );
	}
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0;">
		<?php wp_nonce_field( 'igp_pro_generate_webp_' . $attachment_id ); ?>
		<input type="hidden" name="action" value="igp_pro_generate_webp">
		<input type="hidden" name="attachment_id" value="<?php echo esc_attr( (string) $attachment_id ); ?>">
		<input type="hidden" name="post_id" value="<?php echo isset( $_GET['post_id'] ) ? esc_attr( (string) absint( $_GET['post_id'] ) ) : '0'; ?>">
		<?php submit_button( __( 'Generate WebP', 'igp-pro' ), 'small', 'submit', false ); ?>
	</form>
	<?php
}

/**
 * Render media audit.
 *
 * @param mixed $audit Audit.
 */
function igp_pro_render_media_audit( $audit, int $post_id ): void {
	if ( is_wp_error( $audit ) ) {
		printf( '<div class="notice notice-error inline"><p>%s</p></div>', esc_html( $audit->get_error_message() ) );
		return;
	}
	if ( ! is_array( $audit ) ) {
		return;
	}

	$checks  = isset( $audit['checks'] ) && is_array( $audit['checks'] ) ? $audit['checks'] : array();
	$summary = isset( $audit['summary'] ) && is_array( $audit['summary'] ) ? $audit['summary'] : array();
	?>
	<section style="background:#fff;border:1px solid #ccd0d4;padding:16px;margin:16px 0;max-width:1280px;">
		<h2><?php esc_html_e( 'Media SEO Audit', 'igp-pro' ); ?></h2>
		<p><strong><?php esc_html_e( 'Pass:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) ( $summary['pass'] ?? 0 ) ); ?> &nbsp; <strong><?php esc_html_e( 'Warnings:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) ( $summary['warning'] ?? 0 ) ); ?> &nbsp; <strong><?php esc_html_e( 'Failures:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) ( $summary['fail'] ?? 0 ) ); ?></p>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Status', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Group', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Issue', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Context', 'igp-pro' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $checks as $check ) : ?>
				<tr>
					<td><strong><?php echo esc_html( strtoupper( (string) ( $check['status'] ?? 'info' ) ) ); ?></strong></td>
					<td><?php echo esc_html( (string) ( $check['group'] ?? '' ) ); ?></td>
					<td><?php echo esc_html( (string) ( $check['message'] ?? '' ) ); ?><br><small><?php echo esc_html( (string) ( $check['code'] ?? '' ) ); ?></small></td>
					<td><?php $context = isset( $check['context'] ) && is_array( $check['context'] ) ? $check['context'] : array(); echo esc_html( (string) ( $context['context'] ?? $context['path'] ?? '' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php igp_pro_render_media_alt_update_form( $audit, $post_id ); ?>
	</section>
	<?php
}

/**
 * Render bulk alt update form for attachment-backed images.
 */
function igp_pro_render_media_alt_update_form( array $audit, int $post_id ): void {
	$inventory = isset( $audit['inventory'] ) && is_array( $audit['inventory'] ) ? $audit['inventory'] : array();
	$images    = isset( $inventory['images'] ) && is_array( $inventory['images'] ) ? $inventory['images'] : array();
	$rows      = array();

	foreach ( $images as $image ) {
		$attachment_id = absint( $image['attachment_id'] ?? 0 );
		if ( $attachment_id <= 0 ) {
			continue;
		}
		$alt = (string) ( $image['alt'] ?? '' );
		if ( '' === trim( $alt ) || ( function_exists( 'igp_pro_media_audit_is_weak_alt' ) && igp_pro_media_audit_is_weak_alt( $alt ) ) ) {
			$rows[ $attachment_id ] = $image;
		}
	}

	if ( empty( $rows ) ) {
		return;
	}
	?>
	<h3><?php esc_html_e( 'Bulk Alt Text Update', 'igp-pro' ); ?></h3>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'igp_pro_media_bulk_alt_update' ); ?>
		<input type="hidden" name="action" value="igp_pro_media_bulk_alt_update">
		<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) $post_id ); ?>">
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Attachment', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Current context', 'igp-pro' ); ?></th><th><?php esc_html_e( 'New alt text', 'igp-pro' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $rows as $attachment_id => $image ) : ?>
				<tr>
					<td><?php echo esc_html( '#' . (string) $attachment_id . ' ' . ( $image['filename'] ?? '' ) ); ?></td>
					<td><?php echo esc_html( (string) ( $image['context'] ?? $image['path'] ?? '' ) ); ?></td>
					<td><input type="text" class="regular-text" name="alt[<?php echo esc_attr( (string) $attachment_id ); ?>]" value="<?php echo esc_attr( (string) ( $image['alt'] ?? '' ) ); ?>"></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php submit_button( __( 'Update Attachment Alt Text', 'igp-pro' ), 'primary' ); ?>
	</form>
	<?php
}

/**
 * Handle bulk alt text update.
 */
function igp_pro_handle_media_bulk_alt_update(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'media' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to update media alt text.', 'igp-pro' ) );
	}
	check_admin_referer( 'igp_pro_media_bulk_alt_update' );

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$raw     = isset( $_POST['alt'] ) && is_array( $_POST['alt'] ) ? wp_unslash( $_POST['alt'] ) : array();
	$updates = array();
	foreach ( $raw as $attachment_id => $alt ) {
		$alt = sanitize_text_field( (string) $alt );
		if ( '' !== $alt ) {
			$updates[ absint( $attachment_id ) ] = $alt;
		}
	}

	$result  = function_exists( 'igp_pro_media_bulk_update_alt_text' ) ? igp_pro_media_bulk_update_alt_text( $updates ) : array( 'updated' => 0 );
	$updated = isset( $result['updated'] ) ? absint( $result['updated'] ) : 0;

	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-media', 'post_id' => $post_id, 'igp_media_alt_updated' => $updated ), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Handle manual WebP generation.
 */
function igp_pro_handle_generate_webp(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'media' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to optimize media.', 'igp-pro' ) );
	}

	$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
	$post_id       = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	check_admin_referer( 'igp_pro_generate_webp_' . $attachment_id );

	$result = function_exists( 'igp_pro_generate_webp_for_attachment' ) ? igp_pro_generate_webp_for_attachment( $attachment_id ) : new WP_Error( 'igp_pro_webp_unavailable', __( 'WebP service unavailable.', 'igp-pro' ) );
	$args   = array( 'page' => 'igp-pro-media', 'post_id' => $post_id );
	if ( is_wp_error( $result ) ) {
		$args['igp_media_error'] = rawurlencode( $result->get_error_message() );
	} else {
		$args['igp_media_webp'] = 1;
	}

	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}
