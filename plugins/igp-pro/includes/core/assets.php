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
	$css = 'assets/css/frontend.css';
	$js  = 'assets/js/frontend.js';

	if ( file_exists( igp_pro_path( $css ) ) ) {
		wp_enqueue_style( 'igp-pro-frontend', igp_pro_url( $css ), array(), igp_pro_asset_version( $css ) );
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
