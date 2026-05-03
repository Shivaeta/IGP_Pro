<?php
/**
 * Lazy-loading policy helpers for IGP Pro media.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register lightweight frontend image attribute filters.
 */
function igp_pro_register_lazy_loading_policy(): void {
	if ( function_exists( 'igp_feature_enabled' ) && ! igp_feature_enabled( 'enable_media_optimizer' ) ) {
		return;
	}

	add_filter( 'wp_get_attachment_image_attributes', 'igp_pro_filter_attachment_image_loading_attributes', 20, 3 );
}

/**
 * Ensure likely LCP/featured image is not lazy-loaded while ordinary images can lazy-load.
 *
 * This does not run audits or conversions on frontend requests.
 *
 * @param array<string,mixed> $attr       Image attributes.
 * @param WP_Post            $attachment Attachment object.
 * @param string|int[]       $size       Size.
 * @return array<string,mixed>
 */
function igp_pro_filter_attachment_image_loading_attributes( array $attr, $attachment, $size ): array {
	if ( is_admin() || ! $attachment instanceof WP_Post || 'attachment' !== $attachment->post_type || ! is_singular() ) {
		return $attr;
	}

	$post_id = get_queried_object_id();
	if ( $post_id <= 0 ) {
		return $attr;
	}

	$attachment_id = (int) $attachment->ID;
	if ( $attachment_id === (int) get_post_thumbnail_id( $post_id ) ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		return $attr;
	}

	if ( empty( $attr['loading'] ) ) {
		$attr['loading'] = 'lazy';
	}

	return $attr;
}
