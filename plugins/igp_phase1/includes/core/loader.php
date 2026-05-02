<?php
/**
 * Module loader for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load Phase 1 modules and register WordPress hooks.
 */
function igp_pro_load(): void {
	static $loaded = false;

	if ( $loaded ) {
		return;
	}

	$loaded = true;

	require_once IGP_PRO_PATH . 'includes/core/helpers.php';
	require_once IGP_PRO_PATH . 'includes/cpt/taxonomies.php';
	require_once IGP_PRO_PATH . 'includes/cpt/register-cpt.php';
	require_once IGP_PRO_PATH . 'includes/blocks/registry.php';
	require_once IGP_PRO_PATH . 'includes/blocks/resolver.php';
	require_once IGP_PRO_PATH . 'includes/blocks/renderer.php';
	require_once IGP_PRO_PATH . 'includes/content/content-graph.php';

	add_action( 'init', 'igp_pro_register_taxonomies', 0 );
	add_action( 'init', 'igp_pro_register_post_types', 1 );
	add_action( 'init', 'igp_pro_register_core_blocks', 9 );
	add_action( 'init', 'igp_pro_register_wordpress_blocks', 10 );
}
