<?php
/**
 * Module loader for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load IGP Pro modules and register WordPress hooks.
 */
function igp_pro_load(): void {
	static $loaded = false;

	if ( $loaded ) {
		return;
	}

	$loaded = true;

	require_once IGP_PRO_PATH . 'includes/core/helpers.php';

	if ( file_exists( IGP_PRO_PATH . 'includes/core/assets.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/core/assets.php';
	}

	require_once IGP_PRO_PATH . 'includes/cpt/taxonomies.php';
	require_once IGP_PRO_PATH . 'includes/cpt/register-cpt.php';
	require_once IGP_PRO_PATH . 'includes/blocks/registry.php';
	require_once IGP_PRO_PATH . 'includes/blocks/resolver.php';
	require_once IGP_PRO_PATH . 'includes/blocks/renderer.php';

	if ( file_exists( IGP_PRO_PATH . 'includes/content/sanitizer.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/content/sanitizer.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/content/validator.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/content/validator.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/content/importer.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/content/importer.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/content/exporter.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/content/exporter.php';
	}

	require_once IGP_PRO_PATH . 'includes/content/content-graph.php';

	if ( file_exists( IGP_PRO_PATH . 'includes/admin/content-editor.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/content-editor.php';
	}

	add_action( 'init', 'igp_pro_register_taxonomies', 0 );
	add_action( 'init', 'igp_pro_register_post_types', 1 );
	add_action( 'init', 'igp_pro_register_core_blocks', 9 );
	add_action( 'init', 'igp_pro_register_wordpress_blocks', 10 );
	add_action( 'enqueue_block_editor_assets', 'igp_pro_enqueue_block_editor_assets' );

	if ( function_exists( 'igp_pro_enqueue_frontend_assets' ) ) {
		add_action( 'wp_enqueue_scripts', 'igp_pro_enqueue_frontend_assets' );
	}

	if ( function_exists( 'igp_pro_enqueue_editor_styles' ) ) {
		add_action( 'enqueue_block_editor_assets', 'igp_pro_enqueue_editor_styles', 20 );
	}

	if ( is_admin() && function_exists( 'igp_pro_register_content_editor_admin' ) ) {
		igp_pro_register_content_editor_admin();
	}
}
