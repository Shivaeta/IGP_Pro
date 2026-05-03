<?php
/**
 * Lazy-loading policy helpers for IGP Pro media.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_MEDIA_LOADING_OVERRIDES_META_KEY' ) ) {
	define( 'IGP_PRO_MEDIA_LOADING_OVERRIDES_META_KEY', '_igp_media_loading_overrides' );
}

if ( ! defined( 'IGP_PRO_ATTACHMENT_DEFAULT_LOADING_META_KEY' ) ) {
	define( 'IGP_PRO_ATTACHMENT_DEFAULT_LOADING_META_KEY', '_igp_default_loading_policy' );
}

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
 * Return supported loading policies for admin controls.
 *
 * @return array<string,string>
 */
function igp_pro_media_get_loading_policy_options(): array {
	return array(
		'auto'  => __( 'Auto / IGP policy', 'igp-pro' ),
		'eager' => __( 'Eager / LCP priority', 'igp-pro' ),
		'lazy'  => __( 'Lazy / below fold', 'igp-pro' ),
	);
}

/**
 * Sanitize a loading policy value.
 */
function igp_pro_media_sanitize_loading_policy( string $policy ): string {
	$policy = sanitize_key( $policy );
	return array_key_exists( $policy, igp_pro_media_get_loading_policy_options() ) ? $policy : 'auto';
}

/**
 * Return all page-level media loading overrides.
 *
 * @return array<int,string>
 */
function igp_pro_get_media_loading_overrides( int $post_id ): array {
	$post_id = absint( $post_id );
	if ( $post_id <= 0 ) {
		return array();
	}

	$stored = get_post_meta( $post_id, IGP_PRO_MEDIA_LOADING_OVERRIDES_META_KEY, true );
	if ( is_string( $stored ) ) {
		$decoded = json_decode( $stored, true );
		$stored  = is_array( $decoded ) ? $decoded : array();
	}
	if ( ! is_array( $stored ) ) {
		return array();
	}

	$overrides = array();
	foreach ( $stored as $attachment_id => $policy ) {
		$attachment_id = absint( $attachment_id );
		$policy        = igp_pro_media_sanitize_loading_policy( (string) $policy );
		if ( $attachment_id > 0 && 'auto' !== $policy ) {
			$overrides[ $attachment_id ] = $policy;
		}
	}

	return $overrides;
}

/**
 * Return the effective loading policy for an attachment in a post context.
 */
function igp_pro_get_media_loading_policy( int $post_id, int $attachment_id, string $fallback = 'auto' ): string {
	$post_id       = absint( $post_id );
	$attachment_id = absint( $attachment_id );
	$fallback      = igp_pro_media_sanitize_loading_policy( $fallback );

	if ( $post_id > 0 && $attachment_id > 0 ) {
		$overrides = igp_pro_get_media_loading_overrides( $post_id );
		if ( isset( $overrides[ $attachment_id ] ) ) {
			return $overrides[ $attachment_id ];
		}
	}

	if ( $attachment_id > 0 ) {
		$default = igp_pro_media_sanitize_loading_policy( (string) get_post_meta( $attachment_id, IGP_PRO_ATTACHMENT_DEFAULT_LOADING_META_KEY, true ) );
		if ( 'auto' !== $default ) {
			return $default;
		}
	}

	return $fallback;
}

/**
 * Update or clear a page-level loading override.
 */
function igp_pro_update_media_loading_override( int $post_id, int $attachment_id, string $policy ): bool {
	$post_id       = absint( $post_id );
	$attachment_id = absint( $attachment_id );
	$policy        = igp_pro_media_sanitize_loading_policy( $policy );

	if ( $post_id <= 0 || $attachment_id <= 0 ) {
		return false;
	}

	$overrides = igp_pro_get_media_loading_overrides( $post_id );
	if ( 'auto' === $policy ) {
		unset( $overrides[ $attachment_id ] );
	} else {
		$overrides[ $attachment_id ] = $policy;
	}

	if ( empty( $overrides ) ) {
		delete_post_meta( $post_id, IGP_PRO_MEDIA_LOADING_OVERRIDES_META_KEY );
	} else {
		update_post_meta( $post_id, IGP_PRO_MEDIA_LOADING_OVERRIDES_META_KEY, $overrides );
	}

	if ( function_exists( 'igp_pro_media_inventory_invalidate_post' ) ) {
		igp_pro_media_inventory_invalidate_post( $post_id );
	}

	return true;
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
	$policy        = igp_pro_get_media_loading_policy( (int) $post_id, $attachment_id, 'auto' );

	if ( 'eager' === $policy ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		return $attr;
	}

	if ( 'lazy' === $policy ) {
		$attr['loading'] = 'lazy';
		unset( $attr['fetchpriority'] );
		return $attr;
	}

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
