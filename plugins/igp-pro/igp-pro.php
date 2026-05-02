<?php
/**
 * Plugin Name: IGP Pro
 * Plugin URI: https://github.com/Shivaeta/IGP_Pro
 * Description: Schema-driven travel website engine for WordPress.
 * Version: 0.1.0
 * Author: Shivaeta
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Text Domain: igp-pro
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

define( 'IGP_PRO_VERSION', '0.1.0' );
define( 'IGP_PRO_FILE', __FILE__ );
define( 'IGP_PRO_PATH', plugin_dir_path( __FILE__ ) );
define( 'IGP_PRO_URL', plugin_dir_url( __FILE__ ) );
define( 'IGP_PRO_BASENAME', plugin_basename( __FILE__ ) );
define( 'IGP_PRO_CONTENT_GRAPH_META_KEY', '_igp_pro_content_graph' );

require_once IGP_PRO_PATH . 'includes/core/loader.php';

/**
 * Activate the plugin safely and flush rewrite rules after CPT registration.
 */
function igp_pro_activate(): void {
	igp_pro_load();

	if ( function_exists( 'igp_pro_register_taxonomies' ) ) {
		igp_pro_register_taxonomies();
	}

	if ( function_exists( 'igp_pro_register_post_types' ) ) {
		igp_pro_register_post_types();
	}

	if ( function_exists( 'igp_pro_register_default_feature_flags' ) ) {
		igp_pro_register_default_feature_flags();
	}

	if ( function_exists( 'igp_pro_register_capabilities' ) ) {
		igp_pro_register_capabilities();
	}

	if ( function_exists( 'igp_pro_ensure_log_storage' ) ) {
		igp_pro_ensure_log_storage();
	}

	if ( function_exists( 'igp_pro_ensure_snapshot_storage' ) ) {
		igp_pro_ensure_snapshot_storage();
	}

	if ( function_exists( 'igp_pro_ensure_generated_css_storage' ) ) {
		igp_pro_ensure_generated_css_storage();
	}

	flush_rewrite_rules();
}

/**
 * Flush rewrite rules on deactivation.
 */
function igp_pro_deactivate(): void {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'igp_pro_activate' );
register_deactivation_hook( __FILE__, 'igp_pro_deactivate' );

igp_pro_load();
