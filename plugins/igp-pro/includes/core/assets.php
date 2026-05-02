<?php
/**
 * Asset loading for IGP Pro frontend and editor styles.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

function igp_pro_asset_version( string $relative_path ): string {
	$path = igp_pro_path( $relative_path );
	return file_exists( $path ) ? (string) filemtime( $path ) : IGP_PRO_VERSION;
}

function igp_pro_enqueue_frontend_assets(): void {
	$base_css = 'assets/css/frontend-base.css';
	$css      = 'assets/css/frontend.css';
	$js       = 'assets/js/frontend.js';

	$deps = array();
	if ( function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_brand_engine' ) && file_exists( igp_pro_path( $base_css ) ) ) {
		wp_enqueue_style( 'igp-pro-frontend-base', igp_pro_url( $base_css ), array(), igp_pro_asset_version( $base_css ) );
		$deps[] = 'igp-pro-frontend-base';
	}

	if ( file_exists( igp_pro_path( $css ) ) ) {
		wp_enqueue_style( 'igp-pro-frontend', igp_pro_url( $css ), $deps, igp_pro_asset_version( $css ) );
	}

	if ( function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_brand_engine' ) && function_exists( 'igp_pro_get_generated_brand_css_url' ) ) {
		$generated_url = igp_pro_get_generated_brand_css_url();
		$cache         = get_option( defined( 'IGP_PRO_BRAND_CSS_CACHE_OPTION' ) ? IGP_PRO_BRAND_CSS_CACHE_OPTION : 'igp_pro_brand_css_cache', array() );
		$version       = is_array( $cache ) && ! empty( $cache['hash'] ) ? (string) $cache['hash'] : IGP_PRO_VERSION;
		if ( '' !== $generated_url ) {
			wp_enqueue_style( 'igp-pro-generated-brand', $generated_url, array( 'igp-pro-frontend' ), $version );
		}
	}

	if ( file_exists( igp_pro_path( $js ) ) ) {
		wp_enqueue_script( 'igp-pro-frontend', igp_pro_url( $js ), array(), igp_pro_asset_version( $js ), true );
		if ( function_exists( 'wp_script_add_data' ) ) {
			wp_script_add_data( 'igp-pro-frontend', 'strategy', 'defer' );
		}
	}
}

function igp_pro_enqueue_editor_styles(): void {
	$css = 'assets/css/editor.css';

	if ( file_exists( igp_pro_path( $css ) ) ) {
		wp_enqueue_style( 'igp-pro-editor', igp_pro_url( $css ), array(), igp_pro_asset_version( $css ) );
	}
}
