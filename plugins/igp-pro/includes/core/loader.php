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

	// Phase 5 cache primitives must be available before block rendering starts.
	if ( file_exists( IGP_PRO_PATH . 'includes/performance/cache.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/performance/cache.php';
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

	if ( file_exists( IGP_PRO_PATH . 'includes/booking/pricing-engine.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/booking/pricing-engine.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/booking/payment-adapters/adapter-interface.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/booking/payment-adapters/adapter-interface.php';
	}

	foreach ( array( 'mock', 'razorpay', 'stripe', 'paypal' ) as $igp_pro_adapter ) {
		$igp_pro_adapter_file = IGP_PRO_PATH . 'includes/booking/payment-adapters/' . $igp_pro_adapter . '.php';
		if ( file_exists( $igp_pro_adapter_file ) ) {
			require_once $igp_pro_adapter_file;
		}
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/booking/booking-engine.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/booking/booking-engine.php';
	}

	// Phase 5 SEO and performance modules.
	if ( file_exists( IGP_PRO_PATH . 'includes/seo/schema-generator.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/seo/schema-generator.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/seo/seo-engine.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/seo/seo-engine.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/performance/cwv.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/performance/cwv.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/admin/content-editor.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/content-editor.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/admin/booking-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/booking-panel.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/admin/payment-settings.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/payment-settings.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/admin/seo-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/seo-panel.php';
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

	if ( function_exists( 'igp_pro_register_cache_module' ) ) {
		igp_pro_register_cache_module();
	}

	if ( function_exists( 'igp_pro_register_seo_module' ) ) {
		igp_pro_register_seo_module();
	}

	if ( function_exists( 'igp_pro_register_booking_module' ) ) {
		igp_pro_register_booking_module();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_content_editor_admin' ) ) {
		igp_pro_register_content_editor_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_booking_admin' ) ) {
		igp_pro_register_booking_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_payment_settings_admin' ) ) {
		igp_pro_register_payment_settings_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_seo_admin' ) ) {
		igp_pro_register_seo_admin();
	}
}
