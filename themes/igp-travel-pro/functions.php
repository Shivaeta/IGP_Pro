<?php
/**
 * IGP Travel Pro theme bootstrap.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;

define( 'IGP_TRAVEL_PRO_VERSION', '1.1.2-booking-freeze-checkout-patch' );
define( 'IGP_TRAVEL_PRO_DIR', trailingslashit( get_template_directory() ) );
define( 'IGP_TRAVEL_PRO_URI', trailingslashit( get_template_directory_uri() ) );

require_once IGP_TRAVEL_PRO_DIR . 'inc/tokens.php';
require_once IGP_TRAVEL_PRO_DIR . 'inc/clone-adapter.php';
require_once IGP_TRAVEL_PRO_DIR . 'inc/graph-renderer.php';
require_once IGP_TRAVEL_PRO_DIR . 'inc/block-variants.php';
require_once IGP_TRAVEL_PRO_DIR . 'inc/template-tags.php';
require_once IGP_TRAVEL_PRO_DIR . 'inc/tour-layout.php';

add_action( 'after_setup_theme', 'igp_travel_pro_setup' );
function igp_travel_pro_setup(): void {
	load_theme_textdomain( 'igp-travel-pro', IGP_TRAVEL_PRO_DIR . 'languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 260, 'flex-height' => true, 'flex-width' => true ) );
	register_nav_menus( array( 'primary' => __( 'Primary Menu', 'igp-travel-pro' ), 'footer' => __( 'Footer Menu', 'igp-travel-pro' ) ) );
}

add_action( 'wp_enqueue_scripts', 'igp_travel_pro_enqueue_assets', 20 );
function igp_travel_pro_enqueue_assets(): void {
	wp_enqueue_style( 'igp-travel-pro-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap', array(), null );
	wp_enqueue_style( 'igp-travel-pro', IGP_TRAVEL_PRO_URI . 'assets/css/igp-travel-pro.css', array( 'igp-travel-pro-fonts' ), IGP_TRAVEL_PRO_VERSION );
	wp_add_inline_style( 'igp-travel-pro', igp_travel_pro_get_token_css() );
	wp_enqueue_script( 'igp-travel-pro-tour-layout', IGP_TRAVEL_PRO_URI . 'assets/js/tour-layout.js', array(), IGP_TRAVEL_PRO_VERSION, true );
}



/**
 * Normalize escaped currency fragments that may arrive from stored booking meta
 * or JSON payloads (for example "u20b9" instead of the rupee glyph).
 */
function igp_travel_pro_normalize_currency_symbol( $currency ): string {
	$currency = trim( (string) $currency );
	if ( '' === $currency ) {
		return '₹';
	}
	$map = array(
		'u20b9'       => '₹',
		'\\u20b9'     => '₹',
		'\\\\u20b9' => '₹',
		'&#8377;'     => '₹',
		'&#x20b9;'    => '₹',
	);
	$lower = strtolower( $currency );
	return $map[ $lower ] ?? str_replace( array( '\\u20b9', '\\\\u20b9', 'u20b9' ), '₹', $currency );
}

/**
 * Public checkout pages are rendered by the plugin. The theme only normalizes
 * display output for currency strings that were stored as escaped unicode.
 */
add_action( 'template_redirect', 'igp_travel_pro_checkout_output_normalizer', 1 );
function igp_travel_pro_checkout_output_normalizer(): void {
	$is_checkout = isset( $_GET['igp_pro_checkout'] ) || isset( $_GET['igp_pro_confirmation'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $is_checkout || is_admin() || wp_doing_ajax() ) {
		return;
	}
	ob_start(
		static function ( string $html ): string {
			return str_replace( array( '\\\\u20b9', '\\u20b9', 'u20b9' ), '₹', $html );
		}
	);
}

add_action( 'admin_enqueue_scripts', 'igp_travel_pro_admin_assets' );
function igp_travel_pro_admin_assets( string $hook ): void {
	if ( false === strpos( $hook, 'igp-travel-pro-tokens' ) ) {
		return;
	}
	wp_enqueue_script( 'igp-travel-pro-token-panel', IGP_TRAVEL_PRO_URI . 'assets/js/token-panel.js', array(), IGP_TRAVEL_PRO_VERSION, true );
}

add_filter( 'body_class', 'igp_travel_pro_body_classes' );
function igp_travel_pro_body_classes( array $classes ): array {
	$classes[] = 'igp-travel-pro';
	$classes[] = 'igp-travel-pro--tokenized';
	if ( function_exists( 'igp_pro_render_content_graph' ) ) {
		$classes[] = 'igp-travel-pro--igp-pro-active';
	}
	return $classes;
}

add_action( 'widgets_init', 'igp_travel_pro_widgets' );
function igp_travel_pro_widgets(): void {
	register_sidebar(
		array(
			'name'          => __( 'Footer', 'igp-travel-pro' ),
			'id'            => 'footer',
			'before_widget' => '<section class="igp-footer-widget">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="igp-footer-widget__title">',
			'after_title'   => '</h2>',
		)
	);
}
