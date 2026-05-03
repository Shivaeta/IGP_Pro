<?php
/**
 * Image optimization service for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Generate a WebP derivative for an attachment without destroying the original.
 *
 * @param int   $attachment_id Attachment ID.
 * @param array $args Optional args.
 * @return array|WP_Error
 */
function igp_pro_generate_webp_for_attachment( int $attachment_id, array $args = array() ) {
	$attachment_id = absint( $attachment_id );
	if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
		return new WP_Error( 'igp_pro_webp_invalid_attachment', __( 'A valid attachment ID is required.', 'igp-pro' ) );
	}

	$mime = (string) get_post_mime_type( $attachment_id );
	if ( 0 !== strpos( $mime, 'image/' ) ) {
		return new WP_Error( 'igp_pro_webp_not_image', __( 'Attachment is not an image.', 'igp-pro' ) );
	}

	if ( 'image/webp' === $mime ) {
		return new WP_Error( 'igp_pro_webp_already_webp', __( 'Attachment is already a WebP image.', 'igp-pro' ) );
	}

	if ( function_exists( 'igp_pro_webp_supported' ) && ! igp_pro_webp_supported() ) {
		return new WP_Error( 'igp_pro_webp_unsupported', __( 'This server does not report WebP generation support.', 'igp-pro' ) );
	}

	$source_path = (string) get_attached_file( $attachment_id );
	if ( '' === $source_path || ! file_exists( $source_path ) || ! is_readable( $source_path ) ) {
		return new WP_Error( 'igp_pro_webp_source_missing', __( 'Original image file is missing or unreadable.', 'igp-pro' ) );
	}

	$output_path = function_exists( 'igp_pro_webp_output_path' ) ? igp_pro_webp_output_path( $source_path ) : preg_replace( '/\.[^.]+$/', '.webp', $source_path );
	if ( ! is_string( $output_path ) || '' === $output_path ) {
		return new WP_Error( 'igp_pro_webp_output_path_failed', __( 'Could not determine WebP output path.', 'igp-pro' ) );
	}

	if ( file_exists( $output_path ) && empty( $args['force'] ) ) {
		$url = function_exists( 'igp_pro_upload_path_to_url' ) ? igp_pro_upload_path_to_url( $output_path ) : '';
		return array(
			'attachment_id' => $attachment_id,
			'original_path' => $source_path,
			'webp_path'     => $output_path,
			'webp_url'      => $url,
			'created'       => false,
			'message'       => __( 'Existing WebP file reused.', 'igp-pro' ),
		);
	}

	$editor = wp_get_image_editor( $source_path );
	if ( is_wp_error( $editor ) ) {
		return $editor;
	}

	$quality = isset( $args['quality'] ) ? max( 50, min( 95, absint( $args['quality'] ) ) ) : 82;
	if ( method_exists( $editor, 'set_quality' ) ) {
		$editor->set_quality( $quality );
	}

	$result = $editor->save( $output_path, 'image/webp' );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	if ( ! file_exists( $output_path ) ) {
		return new WP_Error( 'igp_pro_webp_save_failed', __( 'WebP file was not created.', 'igp-pro' ) );
	}

	$url = function_exists( 'igp_pro_upload_path_to_url' ) ? igp_pro_upload_path_to_url( $output_path ) : '';

	update_post_meta( $attachment_id, '_igp_webp_path', wp_normalize_path( $output_path ) );
	update_post_meta( $attachment_id, '_igp_webp_url', esc_url_raw( $url ) );
	update_post_meta( $attachment_id, '_igp_webp_generated_at', current_time( 'mysql' ) );

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'operation'     => 'webp_generate',
				'object_type'   => 'attachment',
				'object_id'     => $attachment_id,
				'source_module' => 'media',
				'status'        => 'success',
				'summary'       => __( 'Generated WebP derivative while preserving original image.', 'igp-pro' ),
			)
		);
	}

	return array(
		'attachment_id' => $attachment_id,
		'original_path' => $source_path,
		'webp_path'     => $output_path,
		'webp_url'      => $url,
		'created'       => true,
		'message'       => __( 'WebP file generated.', 'igp-pro' ),
	);
}

/**
 * Alias for future callers.
 */
function igp_generate_webp_for_attachment( int $attachment_id, array $args = array() ) {
	return igp_pro_generate_webp_for_attachment( $attachment_id, $args );
}
