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

/**
 * Return an attachment's editable media state for snapshots.
 *
 * @return array<string,mixed>
 */
function igp_pro_media_get_attachment_edit_state( int $attachment_id, int $post_id = 0 ): array {
	$attachment_id = absint( $attachment_id );
	$post_id       = absint( $post_id );
	$post          = get_post( $attachment_id );

	return array(
		'attachment_id'       => $attachment_id,
		'post_id'             => $post_id,
		'attached_file'       => (string) get_attached_file( $attachment_id ),
		'attached_file_meta'  => (string) get_post_meta( $attachment_id, '_wp_attached_file', true ),
		'attachment_metadata' => wp_get_attachment_metadata( $attachment_id ),
		'alt'                 => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		'title'               => $post instanceof WP_Post ? (string) $post->post_title : '',
		'slug'                => $post instanceof WP_Post ? (string) $post->post_name : '',
		'caption'             => $post instanceof WP_Post ? (string) $post->post_excerpt : '',
		'description'         => $post instanceof WP_Post ? (string) $post->post_content : '',
		'guid'                => $post instanceof WP_Post ? (string) $post->guid : '',
		'webp_path'           => (string) get_post_meta( $attachment_id, '_igp_webp_path', true ),
		'webp_url'            => (string) get_post_meta( $attachment_id, '_igp_webp_url', true ),
		'loading_overrides'   => $post_id > 0 && function_exists( 'igp_pro_get_media_loading_overrides' ) ? igp_pro_get_media_loading_overrides( $post_id ) : array(),
		'captured_at'         => current_time( 'mysql' ),
	);
}

/**
 * Return aspect ratio presets used by the media dashboard.
 *
 * @return array<string,array{label:string,width:int,height:int}|array{label:string}>
 */
function igp_pro_media_get_aspect_ratio_options(): array {
	return array(
		'keep'   => array( 'label' => __( 'Keep current', 'igp-pro' ) ),
		'16-9'   => array( 'label' => __( '16:9 Landscape', 'igp-pro' ), 'width' => 16, 'height' => 9 ),
		'4-3'    => array( 'label' => __( '4:3 Landscape', 'igp-pro' ), 'width' => 4, 'height' => 3 ),
		'3-2'    => array( 'label' => __( '3:2 Landscape', 'igp-pro' ), 'width' => 3, 'height' => 2 ),
		'1-1'    => array( 'label' => __( '1:1 Square', 'igp-pro' ), 'width' => 1, 'height' => 1 ),
		'2-3'    => array( 'label' => __( '2:3 Portrait', 'igp-pro' ), 'width' => 2, 'height' => 3 ),
		'9-16'   => array( 'label' => __( '9:16 Portrait', 'igp-pro' ), 'width' => 9, 'height' => 16 ),
		'custom' => array( 'label' => __( 'Custom width/height', 'igp-pro' ) ),
	);
}

/**
 * Sanitize an aspect ratio option.
 */
function igp_pro_media_sanitize_aspect_ratio( string $ratio ): string {
	$ratio = sanitize_key( $ratio );
	return array_key_exists( $ratio, igp_pro_media_get_aspect_ratio_options() ) ? $ratio : 'keep';
}

/**
 * Bulk update image SEO and optimization controls from the media dashboard.
 *
 * @param array<int,array<string,mixed>> $controls Attachment ID keyed controls.
 * @return array<string,mixed>
 */
function igp_pro_media_bulk_update_image_controls( array $controls, int $post_id = 0 ): array {
	if ( function_exists( 'igp_pro_current_user_can' ) && ! igp_pro_current_user_can( 'igp_manage_media_optimization' ) ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log( array( 'operation' => 'media_bulk_image_update', 'object_type' => 'media', 'object_id' => 0, 'source_module' => 'media', 'status' => 'failed', 'error_code' => 'permission_denied', 'summary' => __( 'Unauthorized media bulk-edit attempt.', 'igp-pro' ) ) );
		}
		return array( 'updated' => 0, 'errors' => array( __( 'Permission denied.', 'igp-pro' ) ) );
	}

	$post_id = absint( $post_id );
	$updated = 0;
	$errors  = array();
	$details = array();

	foreach ( $controls as $attachment_id => $raw_control ) {
		$attachment_id = absint( $attachment_id );
		$attachment    = $attachment_id > 0 ? get_post( $attachment_id ) : null;
		if ( ! $attachment instanceof WP_Post || 'attachment' !== $attachment->post_type ) {
			$errors[] = sprintf( __( 'Attachment %d could not be found.', 'igp-pro' ), $attachment_id );
			continue;
		}
		if ( 0 !== strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
			$errors[] = sprintf( __( 'Attachment %d is not an image.', 'igp-pro' ), $attachment_id );
			continue;
		}

		$control = is_array( $raw_control ) ? $raw_control : array();
		$alt     = array_key_exists( 'alt', $control ) ? sanitize_text_field( (string) $control['alt'] ) : null;
		$name    = isset( $control['filename'] ) ? sanitize_file_name( (string) $control['filename'] ) : '';
		$name    = igp_pro_media_sanitize_filename_base( $name );
		$current_file_base = igp_pro_media_sanitize_filename_base( pathinfo( (string) get_attached_file( $attachment_id ), PATHINFO_FILENAME ) );
		if ( '' !== $name && $name === $current_file_base ) {
			$name = '';
		}
		$width   = isset( $control['width'] ) ? absint( $control['width'] ) : 0;
		$height  = isset( $control['height'] ) ? absint( $control['height'] ) : 0;
		$ratio   = isset( $control['aspect_ratio'] ) ? igp_pro_media_sanitize_aspect_ratio( (string) $control['aspect_ratio'] ) : 'keep';
		$loading = isset( $control['lazy_loading'] ) && function_exists( 'igp_pro_media_sanitize_loading_policy' ) ? igp_pro_media_sanitize_loading_policy( (string) $control['lazy_loading'] ) : 'auto';

		$has_change = false;
		if ( null !== $alt && $alt !== (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
			$has_change = true;
		}
		if ( '' !== $name ) {
			$has_change = true;
		}
		if ( $width > 0 || $height > 0 || 'keep' !== $ratio ) {
			$has_change = true;
		}
		if ( function_exists( 'igp_pro_get_media_loading_policy' ) && $loading !== igp_pro_get_media_loading_policy( $post_id, $attachment_id, 'auto' ) ) {
			$has_change = true;
		}
		if ( ! $has_change ) {
			continue;
		}

		$snapshot_id = '';
		if ( function_exists( 'igp_create_snapshot' ) ) {
			$snapshot = igp_create_snapshot(
				'attachment_media_edit',
				$attachment_id,
				igp_pro_media_get_attachment_edit_state( $attachment_id, $post_id ),
				array(
					'source_module' => 'media',
					'operation'     => 'bulk_image_edit',
					'post_id'       => $post_id,
				)
			);
			if ( is_string( $snapshot ) ) {
				$snapshot_id = $snapshot;
			}
		}

		$attachment_changes = array();

		if ( null !== $alt ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
			$attachment_changes[] = 'alt';
		}

		if ( $width > 0 || $height > 0 || 'keep' !== $ratio ) {
			$result = igp_pro_media_resize_attachment( $attachment_id, $width, $height, $ratio, $name );
			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf( __( 'Attachment %1$d resize failed: %2$s', 'igp-pro' ), $attachment_id, $result->get_error_message() );
			} else {
				$attachment_changes[] = 'dimensions';
				if ( '' !== $name ) {
					$attachment_changes[] = 'filename';
				}
			}
		} elseif ( '' !== $name ) {
			$result = igp_pro_media_rename_attachment_file( $attachment_id, $name );
			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf( __( 'Attachment %1$d rename failed: %2$s', 'igp-pro' ), $attachment_id, $result->get_error_message() );
			} else {
				$attachment_changes[] = 'filename';
			}
		}

		if ( function_exists( 'igp_pro_update_media_loading_override' ) ) {
			igp_pro_update_media_loading_override( $post_id, $attachment_id, $loading );
			$attachment_changes[] = 'lazy_loading';
		}

		if ( ! empty( $attachment_changes ) ) {
			$updated++;
			$details[ $attachment_id ] = array_values( array_unique( $attachment_changes ) );
			delete_post_meta( $attachment_id, '_igp_webp_path' );
			delete_post_meta( $attachment_id, '_igp_webp_url' );
			delete_post_meta( $attachment_id, '_igp_webp_generated_at' );

			if ( function_exists( 'igp_pro_log' ) ) {
				igp_pro_log( array( 'operation' => 'media_bulk_image_update', 'object_type' => 'attachment', 'object_id' => $attachment_id, 'source_module' => 'media', 'status' => 'success', 'summary' => sprintf( __( 'Updated media controls: %s.', 'igp-pro' ), implode( ', ', array_unique( $attachment_changes ) ) ), 'snapshot_id' => $snapshot_id ) );
			}
		}
	}

	if ( $post_id > 0 && function_exists( 'igp_pro_media_inventory_invalidate_post' ) ) {
		igp_pro_media_inventory_invalidate_post( $post_id );
	}

	return array( 'updated' => $updated, 'errors' => $errors, 'details' => $details );
}

/**
 * Calculate target dimensions from requested width/height/aspect ratio.
 *
 * @return array{0:int,1:int,2:bool}|WP_Error Width, height, crop.
 */
function igp_pro_media_calculate_target_dimensions( int $attachment_id, int $width, int $height, string $ratio ) {
	$source_path = (string) get_attached_file( $attachment_id );
	if ( '' === $source_path || ! file_exists( $source_path ) ) {
		return new WP_Error( 'igp_pro_media_file_missing', __( 'Original image file is missing.', 'igp-pro' ) );
	}

	$size = wp_getimagesize( $source_path );
	if ( ! is_array( $size ) || empty( $size[0] ) || empty( $size[1] ) ) {
		return new WP_Error( 'igp_pro_media_size_unreadable', __( 'Image dimensions could not be read.', 'igp-pro' ) );
	}

	$current_width  = absint( $size[0] );
	$current_height = absint( $size[1] );
	$ratio          = igp_pro_media_sanitize_aspect_ratio( $ratio );
	$options        = igp_pro_media_get_aspect_ratio_options();
	$crop           = false;

	if ( 'keep' === $ratio || 'custom' === $ratio ) {
		$target_width  = $width > 0 ? $width : $current_width;
		$target_height = $height > 0 ? $height : $current_height;
		$crop          = 'custom' === $ratio && $width > 0 && $height > 0;
	} else {
		$ratio_width  = absint( $options[ $ratio ]['width'] ?? 0 );
		$ratio_height = absint( $options[ $ratio ]['height'] ?? 0 );
		if ( $ratio_width <= 0 || $ratio_height <= 0 ) {
			return new WP_Error( 'igp_pro_media_ratio_invalid', __( 'Invalid aspect ratio.', 'igp-pro' ) );
		}
		$decimal = $ratio_width / $ratio_height;
		if ( $width > 0 ) {
			$target_width  = $width;
			$target_height = max( 1, (int) round( $width / $decimal ) );
		} elseif ( $height > 0 ) {
			$target_height = $height;
			$target_width  = max( 1, (int) round( $height * $decimal ) );
		} else {
			$current_ratio = $current_width / $current_height;
			if ( $current_ratio > $decimal ) {
				$target_height = $current_height;
				$target_width  = max( 1, (int) round( $current_height * $decimal ) );
			} else {
				$target_width  = $current_width;
				$target_height = max( 1, (int) round( $current_width / $decimal ) );
			}
		}
		$crop = true;
	}

	$target_width  = max( 1, min( 6000, absint( $target_width ) ) );
	$target_height = max( 1, min( 6000, absint( $target_height ) ) );

	return array( $target_width, $target_height, $crop );
}

/**
 * Resize/crop an attachment into a new file and point the attachment to it.
 */
function igp_pro_media_resize_attachment( int $attachment_id, int $width, int $height, string $ratio = 'keep', string $filename_base = '' ) {
	$attachment_id = absint( $attachment_id );
	$source_path   = (string) get_attached_file( $attachment_id );
	if ( '' === $source_path || ! file_exists( $source_path ) || ! is_readable( $source_path ) ) {
		return new WP_Error( 'igp_pro_media_source_missing', __( 'Original image file is missing or unreadable.', 'igp-pro' ) );
	}

	$dimensions = igp_pro_media_calculate_target_dimensions( $attachment_id, $width, $height, $ratio );
	if ( is_wp_error( $dimensions ) ) {
		return $dimensions;
	}
	list( $target_width, $target_height, $crop ) = $dimensions;

	$editor = wp_get_image_editor( $source_path );
	if ( is_wp_error( $editor ) ) {
		return $editor;
	}

	$resized = $editor->resize( $target_width, $target_height, $crop );
	if ( is_wp_error( $resized ) ) {
		return $resized;
	}

	$output_path = igp_pro_media_build_edited_output_path( $source_path, $filename_base, $target_width, $target_height );
	$result      = $editor->save( $output_path );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	if ( ! file_exists( $output_path ) ) {
		return new WP_Error( 'igp_pro_media_resize_save_failed', __( 'Edited image file was not created.', 'igp-pro' ) );
	}

	return igp_pro_media_update_attachment_file_record( $attachment_id, $output_path, '' !== $filename_base ? $filename_base : pathinfo( $output_path, PATHINFO_FILENAME ) );
}

/**
 * Rename the full-size attachment file in place.
 */
function igp_pro_media_rename_attachment_file( int $attachment_id, string $filename_base ) {
	$attachment_id = absint( $attachment_id );
	$source_path   = (string) get_attached_file( $attachment_id );
	if ( '' === $source_path || ! file_exists( $source_path ) || ! is_writable( dirname( $source_path ) ) ) {
		return new WP_Error( 'igp_pro_media_source_not_writable', __( 'Original image file is missing or the folder is not writable.', 'igp-pro' ) );
	}

	$filename_base = igp_pro_media_sanitize_filename_base( $filename_base );
	if ( '' === $filename_base ) {
		return new WP_Error( 'igp_pro_media_filename_invalid', __( 'A valid filename is required.', 'igp-pro' ) );
	}

	$extension  = pathinfo( $source_path, PATHINFO_EXTENSION );
	$target_dir = dirname( $source_path );
	$new_name   = wp_unique_filename( $target_dir, $filename_base . ( $extension ? '.' . $extension : '' ) );
	$new_path   = trailingslashit( $target_dir ) . $new_name;

	if ( wp_normalize_path( $new_path ) === wp_normalize_path( $source_path ) ) {
		return array( 'attachment_id' => $attachment_id, 'path' => $source_path, 'renamed' => false );
	}

	if ( ! @rename( $source_path, $new_path ) ) {
		return new WP_Error( 'igp_pro_media_rename_failed', __( 'The image file could not be renamed.', 'igp-pro' ) );
	}

	return igp_pro_media_update_attachment_file_record( $attachment_id, $new_path, $filename_base );
}

/**
 * Build a unique edited output path beside the source file.
 */
function igp_pro_media_build_edited_output_path( string $source_path, string $filename_base, int $width, int $height ): string {
	$dir           = dirname( $source_path );
	$extension     = pathinfo( $source_path, PATHINFO_EXTENSION );
	$filename_base = igp_pro_media_sanitize_filename_base( $filename_base );
	if ( '' === $filename_base ) {
		$filename_base = pathinfo( $source_path, PATHINFO_FILENAME ) . '-igp-' . $width . 'x' . $height;
	}

	$filename = wp_unique_filename( $dir, $filename_base . ( $extension ? '.' . $extension : '' ) );
	return trailingslashit( $dir ) . $filename;
}

/**
 * Sanitize a filename base without an extension.
 */
function igp_pro_media_sanitize_filename_base( string $filename_base ): string {
	$filename_base = trim( $filename_base );
	if ( '' === $filename_base ) {
		return '';
	}
	$filename_base = pathinfo( sanitize_file_name( $filename_base ), PATHINFO_FILENAME );
	return sanitize_title( $filename_base );
}

/**
 * Point a WordPress attachment at a new full-size file and regenerate metadata.
 */
function igp_pro_media_update_attachment_file_record( int $attachment_id, string $new_path, string $filename_base = '' ) {
	if ( ! file_exists( $new_path ) ) {
		return new WP_Error( 'igp_pro_media_new_file_missing', __( 'Edited image file could not be found.', 'igp-pro' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';

	update_attached_file( $attachment_id, $new_path );
	$metadata = wp_generate_attachment_metadata( $attachment_id, $new_path );
	if ( is_wp_error( $metadata ) ) {
		return $metadata;
	}
	if ( is_array( $metadata ) ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	$base = igp_pro_media_sanitize_filename_base( $filename_base );
	if ( '' !== $base ) {
		wp_update_post(
			array(
				'ID'         => $attachment_id,
				'post_title' => str_replace( '-', ' ', $base ),
				'post_name'  => $base,
			)
		);
	}

	return array(
		'attachment_id' => $attachment_id,
		'path'          => $new_path,
		'url'           => function_exists( 'igp_pro_upload_path_to_url' ) ? igp_pro_upload_path_to_url( $new_path ) : wp_get_attachment_url( $attachment_id ),
		'metadata'      => is_array( $metadata ) ? $metadata : array(),
	);
}
