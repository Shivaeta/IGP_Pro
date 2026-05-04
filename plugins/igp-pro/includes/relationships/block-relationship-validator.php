<?php
/**
 * Block relationship field validation for Content Graph sections.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return post type expectations for relationship-like block fields.
 *
 * @return array<string,array<string,string>>
 */
function igp_pro_get_block_relationship_field_post_type_map(): array {
	return array(
		'tour_cards'           => array(
			'items'       => 'tour',
			'destination' => 'destination',
		),
		'featured_listings'    => array( 'items' => 'any' ),
		'related_tours'        => array( 'items' => 'tour', 'related_tours' => 'tour' ),
		'destination_cards'    => array( 'items' => 'destination' ),
		'related_destinations' => array( 'items' => 'destination', 'related_destinations' => 'destination' ),
		'nearby_attractions'   => array( 'items' => 'destination', 'attractions' => 'destination' ),
		'route_timeline'       => array( 'route_stop_ids' => 'destination', 'items' => 'destination' ),
		'map'                  => array( 'destination_ids' => 'destination', 'items' => 'destination' ),
	);
}

/**
 * Return supported CPT aliases.
 *
 * @param string $post_type Expected post type.
 * @return string[]
 */
function igp_pro_relationship_expected_post_type_aliases( string $post_type ): array {
	$post_type = sanitize_key( $post_type );
	if ( 'tour' === $post_type ) {
		return array( 'tour', 'igp_tour' );
	}
	if ( 'destination' === $post_type ) {
		return array( 'destination', 'igp_destination' );
	}
	if ( 'any' === $post_type ) {
		return array( 'tour', 'igp_tour', 'destination', 'igp_destination', 'post', 'page' );
	}
	return array( $post_type );
}

/**
 * Validate relationship fields in one block data payload.
 *
 * @param string              $block_id Block ID.
 * @param array<string,mixed> $data     Block data.
 * @param array<string,mixed> $context  Context.
 * @return true|WP_Error
 */
function igp_pro_validate_block_relationship_fields( string $block_id, array $data, array $context = array() ) {
	$block_id = sanitize_key( $block_id );
	$field_map = igp_pro_get_block_relationship_field_post_type_map();
	if ( empty( $field_map[ $block_id ] ) ) {
		return true;
	}

	foreach ( $field_map[ $block_id ] as $field => $expected_post_type ) {
		if ( ! array_key_exists( $field, $data ) || empty( $data[ $field ] ) ) {
			continue;
		}

		$ids = function_exists( 'igp_pro_normalize_post_ids' ) ? igp_pro_normalize_post_ids( $data[ $field ] ) : array_map( 'absint', (array) $data[ $field ] );
		foreach ( $ids as $id ) {
			$id = absint( $id );
			if ( $id <= 0 ) {
				return new WP_Error( 'igp_pro_invalid_relationship_id', sprintf( __( '%1$s.%2$s contains an invalid post ID.', 'igp-pro' ), $block_id, $field ) );
			}

			if ( ! function_exists( 'get_post' ) ) {
				continue;
			}

			$post = get_post( $id );
			if ( ! $post instanceof WP_Post ) {
				return new WP_Error( 'igp_pro_relationship_missing_post', sprintf( __( '%1$s.%2$s references a missing post ID: %3$d.', 'igp-pro' ), $block_id, $field, $id ) );
			}

			$allowed = igp_pro_relationship_expected_post_type_aliases( $expected_post_type );
			if ( ! in_array( (string) $post->post_type, $allowed, true ) ) {
				return new WP_Error( 'igp_pro_relationship_wrong_post_type', sprintf( __( '%1$s.%2$s references post ID %3$d with the wrong post type.', 'igp-pro' ), $block_id, $field, $id ) );
			}
		}
	}

	return true;
}
