<?php
/**
 * Media SEO audit service for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run a media SEO audit for a post.
 *
 * @param int   $post_id Post ID.
 * @param array $args    Optional args.
 * @return array|WP_Error
 */
function igp_pro_run_media_audit( int $post_id, array $args = array() ) {
	if ( ! function_exists( 'igp_pro_get_media_inventory' ) ) {
		return new WP_Error( 'igp_pro_media_inventory_missing', __( 'Media inventory service is unavailable.', 'igp-pro' ) );
	}

	$inventory = igp_pro_get_media_inventory( $post_id, $args );
	if ( is_wp_error( $inventory ) ) {
		return $inventory;
	}

	$images = isset( $inventory['images'] ) && is_array( $inventory['images'] ) ? $inventory['images'] : array();
	$checks = array();

	$checks = array_merge( $checks, igp_pro_media_audit_alt_checks( $images ) );
	$checks = array_merge( $checks, igp_pro_media_audit_filename_checks( $images ) );
	$checks = array_merge( $checks, igp_pro_media_audit_dimension_checks( $images ) );
	$checks = array_merge( $checks, igp_pro_media_audit_responsive_checks( $images ) );
	$checks = array_merge( $checks, igp_pro_media_audit_lazy_loading_checks( $images ) );
	$checks = array_merge( $checks, igp_pro_media_audit_og_schema_checks( $images ) );

	$summary = igp_pro_media_audit_summarize( $checks );

	return array(
		'post_id'    => $post_id,
		'generated'  => current_time( 'mysql' ),
		'inventory'  => $inventory,
		'checks'     => $checks,
		'summary'    => $summary,
		'can_update' => function_exists( 'igp_pro_current_user_can' ) ? igp_pro_current_user_can( 'igp_manage_media_optimization' ) : current_user_can( 'manage_options' ),
	);
}

/**
 * Alias for expected naming.
 */
function igp_run_media_audit( int $post_id, array $args = array() ) {
	return igp_pro_run_media_audit( $post_id, $args );
}

/**
 * Build check record.
 */
function igp_pro_media_audit_check( string $code, string $group, string $status, string $message, array $context = array() ): array {
	return array(
		'code'    => sanitize_key( $code ),
		'group'   => sanitize_key( $group ),
		'status'  => in_array( $status, array( 'pass', 'warning', 'fail', 'info' ), true ) ? $status : 'info',
		'message' => sanitize_text_field( $message ),
		'context' => $context,
	);
}

/**
 * Alt text checks.
 *
 * @param array<int,array<string,mixed>> $images Images.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_media_audit_alt_checks( array $images ): array {
	$checks = array();

	if ( empty( $images ) ) {
		return array( igp_pro_media_audit_check( 'no_images_found', 'alt', 'warning', __( 'No auditable images were found for this object.', 'igp-pro' ) ) );
	}

	foreach ( $images as $image ) {
		if ( ! empty( $image['placeholder'] ) ) {
			continue;
		}
		$alt = trim( wp_strip_all_tags( (string) ( $image['alt'] ?? '' ) ) );
		if ( '' === $alt ) {
			$checks[] = igp_pro_media_audit_check( 'missing_alt_text', 'alt', 'fail', __( 'Image is missing alt text.', 'igp-pro' ), $image );
		} elseif ( igp_pro_media_audit_is_weak_alt( $alt ) ) {
			$checks[] = igp_pro_media_audit_check( 'weak_alt_text', 'alt', 'warning', __( 'Image alt text appears weak or generic.', 'igp-pro' ), $image );
		}
	}

	if ( empty( $checks ) ) {
		$checks[] = igp_pro_media_audit_check( 'alt_text_coverage', 'alt', 'pass', __( 'All auditable images have meaningful alt text.', 'igp-pro' ) );
	}

	return $checks;
}

/**
 * Determine weak alt text.
 */
function igp_pro_media_audit_is_weak_alt( string $alt ): bool {
	$normalized = strtolower( trim( preg_replace( '/\s+/', ' ', $alt ) ?: '' ) );
	return in_array( $normalized, array( 'image', 'photo', 'picture', 'img', 'banner', 'hero', 'tour image', 'destination image', 'travel image', 'logo' ), true ) || strlen( $normalized ) < 5;
}

/**
 * Filename checks.
 *
 * @param array<int,array<string,mixed>> $images Images.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_media_audit_filename_checks( array $images ): array {
	$checks = array();

	foreach ( $images as $image ) {
		$filename = strtolower( (string) ( $image['filename'] ?? '' ) );
		if ( '' === $filename || ! empty( $image['placeholder'] ) ) {
			continue;
		}
		$name = pathinfo( $filename, PATHINFO_FILENAME );
		if ( preg_match( '/^(img|dsc|photo|image|screenshot|whatsapp|pxl)[-_ ]?[0-9a-z_-]*$/i', $name ) || false === strpos( $name, '-' ) && strlen( $name ) > 18 ) {
			$checks[] = igp_pro_media_audit_check( 'generic_filename', 'filename', 'warning', __( 'Image filename appears generic or weak for SEO.', 'igp-pro' ), $image );
		}
	}

	if ( empty( $checks ) ) {
		$checks[] = igp_pro_media_audit_check( 'filename_quality', 'filename', 'pass', __( 'Image filenames do not show obvious generic patterns.', 'igp-pro' ) );
	}

	return $checks;
}

/**
 * Dimension and size checks.
 *
 * @param array<int,array<string,mixed>> $images Images.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_media_audit_dimension_checks( array $images ): array {
	$checks = array();

	foreach ( $images as $image ) {
		if ( ! empty( $image['placeholder'] ) ) {
			continue;
		}

		if ( ! empty( $image['missing'] ) ) {
			$checks[] = igp_pro_media_audit_check( 'missing_attachment', 'dimensions', 'fail', __( 'Referenced attachment is missing or deleted.', 'igp-pro' ), $image );
			continue;
		}

		$width  = absint( $image['width'] ?? 0 );
		$height = absint( $image['height'] ?? 0 );
		if ( ! empty( $image['attachment_id'] ) && ( $width <= 0 || $height <= 0 ) ) {
			$checks[] = igp_pro_media_audit_check( 'missing_dimensions', 'dimensions', 'warning', __( 'Attachment metadata is missing image dimensions.', 'igp-pro' ), $image );
			continue;
		}

		if ( $width > 2560 || $height > 2560 || absint( $image['filesize'] ?? 0 ) > 1200 * 1024 ) {
			$checks[] = igp_pro_media_audit_check( 'oversized_image', 'dimensions', 'warning', __( 'Image appears oversized for ordinary frontend use.', 'igp-pro' ), $image );
		}
	}

	if ( empty( $checks ) ) {
		$checks[] = igp_pro_media_audit_check( 'dimension_quality', 'dimensions', 'pass', __( 'No obvious dimension or oversized-image issues found.', 'igp-pro' ) );
	}

	return $checks;
}

/**
 * Responsive image checks.
 *
 * @param array<int,array<string,mixed>> $images Images.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_media_audit_responsive_checks( array $images ): array {
	$checks = array();

	foreach ( $images as $image ) {
		if ( ! empty( $image['placeholder'] ) || empty( $image['attachment_id'] ) ) {
			continue;
		}
		if ( empty( $image['has_sizes'] ) ) {
			$checks[] = igp_pro_media_audit_check( 'missing_responsive_sizes', 'responsive', 'warning', __( 'Attachment does not appear to have generated responsive sizes.', 'igp-pro' ), $image );
		}
	}

	if ( empty( $checks ) ) {
		$checks[] = igp_pro_media_audit_check( 'responsive_sizes_present', 'responsive', 'pass', __( 'Auditable attachments have responsive image size metadata.', 'igp-pro' ) );
	}

	return $checks;
}

/**
 * Lazy loading policy checks.
 *
 * @param array<int,array<string,mixed>> $images Images.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_media_audit_lazy_loading_checks( array $images ): array {
	$checks        = array();
	$lcp_candidate = null;

	foreach ( $images as $image ) {
		if ( ! empty( $image['is_lcp'] ) ) {
			$lcp_candidate = $image;
			break;
		}
	}

	if ( null === $lcp_candidate ) {
		return array( igp_pro_media_audit_check( 'lcp_image_missing', 'lazy_loading', 'warning', __( 'No likely LCP image could be identified.', 'igp-pro' ) ) );
	}

	$policy = (string) ( $lcp_candidate['loading_policy'] ?? '' );
	if ( in_array( $policy, array( 'eager', 'eager_candidate' ), true ) ) {
		$checks[] = igp_pro_media_audit_check( 'lcp_image_eager', 'lazy_loading', 'pass', __( 'Likely LCP image is marked for eager loading.', 'igp-pro' ), $lcp_candidate );
	} else {
		$checks[] = igp_pro_media_audit_check( 'lcp_image_lazy_risk', 'lazy_loading', 'warning', __( 'Likely LCP image is not clearly marked for eager loading.', 'igp-pro' ), $lcp_candidate );
	}

	$lazy_count = 0;
	foreach ( $images as $image ) {
		if ( empty( $image['is_lcp'] ) && 'lazy' === ( $image['loading_policy'] ?? '' ) ) {
			$lazy_count++;
		}
	}

	$checks[] = igp_pro_media_audit_check( 'below_fold_lazy_policy', 'lazy_loading', 'info', sprintf( __( '%d below-fold image(s) are identified for lazy loading.', 'igp-pro' ), $lazy_count ) );

	return $checks;
}

/**
 * OG/schema image checks.
 *
 * @param array<int,array<string,mixed>> $images Images.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_media_audit_og_schema_checks( array $images ): array {
	$has_og     = false;
	$has_schema = false;

	foreach ( $images as $image ) {
		$source = (string) ( $image['source'] ?? '' );
		if ( 'og_image' === $source ) {
			$has_og = true;
		}
		if ( in_array( $source, array( 'schema_image', 'schema_primary_image' ), true ) ) {
			$has_schema = true;
		}
	}

	$checks = array();
	$checks[] = $has_og
		? igp_pro_media_audit_check( 'og_image_present', 'seo_images', 'pass', __( 'Open Graph image is present.', 'igp-pro' ) )
		: igp_pro_media_audit_check( 'missing_og_image', 'seo_images', 'warning', __( 'No explicit Open Graph image is set.', 'igp-pro' ) );

	$checks[] = $has_schema
		? igp_pro_media_audit_check( 'schema_image_present', 'seo_images', 'pass', __( 'Schema image source is available.', 'igp-pro' ) )
		: igp_pro_media_audit_check( 'missing_schema_image', 'seo_images', 'warning', __( 'No schema image source could be identified.', 'igp-pro' ) );

	return $checks;
}

/**
 * Summarize audit checks.
 *
 * @param array<int,array<string,mixed>> $checks Checks.
 * @return array<string,int>
 */
function igp_pro_media_audit_summarize( array $checks ): array {
	$summary = array(
		'pass'    => 0,
		'warning' => 0,
		'fail'    => 0,
		'info'    => 0,
	);

	foreach ( $checks as $check ) {
		$status = (string) ( $check['status'] ?? 'info' );
		if ( isset( $summary[ $status ] ) ) {
			$summary[ $status ]++;
		}
	}

	return $summary;
}

/**
 * Bulk update attachment alt text, with snapshots and logging.
 *
 * @param array<int,string> $alt_updates Attachment ID => alt text.
 * @return array<string,mixed>
 */
function igp_pro_media_bulk_update_alt_text( array $alt_updates ): array {
	if ( function_exists( 'igp_pro_current_user_can' ) && ! igp_pro_current_user_can( 'igp_manage_media_optimization' ) ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log( array( 'operation' => 'media_alt_bulk_update', 'object_type' => 'media', 'object_id' => 0, 'source_module' => 'media', 'status' => 'failed', 'error_code' => 'permission_denied', 'summary' => __( 'Unauthorized media alt update attempt.', 'igp-pro' ) ) );
		}
		return array( 'updated' => 0, 'errors' => array( __( 'Permission denied.', 'igp-pro' ) ) );
	}

	$updated = 0;
	$errors  = array();

	foreach ( $alt_updates as $attachment_id => $alt_text ) {
		$attachment_id = absint( $attachment_id );
		$attachment    = $attachment_id > 0 ? get_post( $attachment_id ) : null;
		if ( ! $attachment instanceof WP_Post || 'attachment' !== $attachment->post_type ) {
			$errors[] = sprintf( __( 'Attachment %d could not be found.', 'igp-pro' ), $attachment_id );
			continue;
		}

		$before = array(
			'_wp_attachment_image_alt' => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		);

		$snapshot_id = '';
		if ( function_exists( 'igp_create_snapshot' ) ) {
			$snapshot = igp_create_snapshot(
				'attachment_media_seo',
				$attachment_id,
				$before,
				array(
					'source_module' => 'media',
					'operation'     => 'bulk_alt_update',
				)
			);
			if ( is_string( $snapshot ) ) {
				$snapshot_id = $snapshot;
			}
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( (string) $alt_text ) );
		$updated++;

		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log( array( 'operation' => 'media_alt_update', 'object_type' => 'attachment', 'object_id' => $attachment_id, 'source_module' => 'media', 'status' => 'success', 'summary' => __( 'Updated attachment alt text.', 'igp-pro' ), 'snapshot_id' => $snapshot_id ) );
		}
	}

	return array( 'updated' => $updated, 'errors' => $errors );
}
