<?php
/**
 * WebP adapter for IGP Pro media optimization.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Determine if the current server can attempt WebP generation through WP_Image_Editor.
 */
function igp_pro_webp_supported(): bool {
	if ( ! function_exists( 'wp_get_image_editor' ) || ! function_exists( 'wp_image_editor_supports' ) ) {
		return false;
	}

	return (bool) wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
}

/**
 * Build a WebP output path for an existing image path.
 */
function igp_pro_webp_output_path( string $source_path ): string {
	$info = pathinfo( $source_path );
	$dir  = isset( $info['dirname'] ) ? $info['dirname'] : '';
	$name = isset( $info['filename'] ) ? $info['filename'] : wp_basename( $source_path );

	return trailingslashit( $dir ) . $name . '.webp';
}

/**
 * Convert absolute upload path to URL.
 */
function igp_pro_upload_path_to_url( string $path ): string {
	$uploads = wp_get_upload_dir();
	$basedir = isset( $uploads['basedir'] ) ? wp_normalize_path( (string) $uploads['basedir'] ) : '';
	$baseurl = isset( $uploads['baseurl'] ) ? (string) $uploads['baseurl'] : '';
	$path    = wp_normalize_path( $path );

	if ( '' !== $basedir && 0 === strpos( $path, $basedir ) ) {
		$relative = ltrim( substr( $path, strlen( $basedir ) ), '/\\' );
		return esc_url_raw( trailingslashit( $baseurl ) . str_replace( '\\', '/', $relative ) );
	}

	return '';
}
