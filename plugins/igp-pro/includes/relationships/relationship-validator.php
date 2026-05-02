<?php
/**
 * Relationship payload validation for IGP Pro V2.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_REL_PRIMARY_DESTINATION_META_KEY' ) ) {
	define( 'IGP_PRO_REL_PRIMARY_DESTINATION_META_KEY', '_igp_primary_destination_id' );
}

if ( ! defined( 'IGP_PRO_REL_DESTINATIONS_META_KEY' ) ) {
	define( 'IGP_PRO_REL_DESTINATIONS_META_KEY', '_igp_destination_ids' );
}

if ( ! defined( 'IGP_PRO_REL_ROUTE_STOPS_META_KEY' ) ) {
	define( 'IGP_PRO_REL_ROUTE_STOPS_META_KEY', '_igp_route_stop_ids' );
}

if ( ! defined( 'IGP_PRO_REL_RELATED_TOURS_META_KEY' ) ) {
	define( 'IGP_PRO_REL_RELATED_TOURS_META_KEY', '_igp_related_tour_ids' );
}

if ( ! defined( 'IGP_PRO_REL_RELATED_DESTINATIONS_META_KEY' ) ) {
	define( 'IGP_PRO_REL_RELATED_DESTINATIONS_META_KEY', '_igp_related_destination_ids' );
}

if ( ! defined( 'IGP_PRO_REL_DESTINATION_INDEX_META_KEY' ) ) {
	define( 'IGP_PRO_REL_DESTINATION_INDEX_META_KEY', '_igp_destination_index' );
}

/**
 * Return canonical relationship field definitions.
 *
 * @return array<string,array{post_type:string,multiple:bool}>
 */
function igp_pro_get_relationship_field_definitions(): array {
	return array(
		'primary_destination_id'    => array(
			'post_type' => 'destination',
			'multiple'  => false,
		),
		'destination_ids'           => array(
			'post_type' => 'destination',
			'multiple'  => true,
		),
		'route_stop_ids'            => array(
			'post_type' => 'destination',
			'multiple'  => true,
		),
		'related_tour_ids'          => array(
			'post_type' => 'tour',
			'multiple'  => true,
		),
		'related_destination_ids'   => array(
			'post_type' => 'destination',
			'multiple'  => true,
		),
	);
}

/**
 * Return canonical relationship meta key map.
 *
 * @return array<string,string>
 */
function igp_pro_get_relationship_meta_keys(): array {
	return array(
		'primary_destination_id'  => IGP_PRO_REL_PRIMARY_DESTINATION_META_KEY,
		'destination_ids'         => IGP_PRO_REL_DESTINATIONS_META_KEY,
		'route_stop_ids'          => IGP_PRO_REL_ROUTE_STOPS_META_KEY,
		'related_tour_ids'        => IGP_PRO_REL_RELATED_TOURS_META_KEY,
		'related_destination_ids' => IGP_PRO_REL_RELATED_DESTINATIONS_META_KEY,
	);
}

/**
 * Sanitize a raw relationship payload into canonical shape.
 *
 * This function normalizes IDs but does not verify whether posts exist. Use
 * igp_pro_validate_relationship_payload() before persisting.
 *
 * @param mixed $payload Raw relationship payload.
 * @return array<string,int|int[]>
 */
function igp_pro_sanitize_relationship_payload( $payload ): array {
	$payload     = is_array( $payload ) ? $payload : array();
	$definitions = igp_pro_get_relationship_field_definitions();
	$sanitized   = array();

	foreach ( $definitions as $field => $definition ) {
		$raw_value = $payload[ $field ] ?? ( ! empty( $definition['multiple'] ) ? array() : 0 );

		if ( ! empty( $definition['multiple'] ) ) {
			$sanitized[ $field ] = igp_pro_normalize_post_ids( $raw_value );
		} else {
			$ids = igp_pro_normalize_post_ids( $raw_value );
			$sanitized[ $field ] = isset( $ids[0] ) ? absint( $ids[0] ) : 0;
		}
	}

	return $sanitized;
}

/**
 * Validate a single relationship target post ID.
 *
 * @param int    $post_id       Target post ID.
 * @param string $expected_type Required post type.
 * @param string $field         Relationship field name.
 * @return true|WP_Error
 */
function igp_pro_validate_relationship_target_id( int $post_id, string $expected_type, string $field ) {
	if ( $post_id <= 0 ) {
		return true;
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return new WP_Error(
			'igp_pro_relationship_invalid_post_id',
			sprintf(
				/* translators: 1: field name, 2: post ID. */
				__( 'Invalid relationship target for %1$s: post ID %2$d does not exist.', 'igp-pro' ),
				sanitize_key( $field ),
				$post_id
			)
		);
	}

	if ( $expected_type !== get_post_type( $post ) ) {
		return new WP_Error(
			'igp_pro_relationship_wrong_post_type',
			sprintf(
				/* translators: 1: field name, 2: post ID, 3: expected post type. */
				__( 'Invalid relationship target for %1$s: post ID %2$d must be a %3$s.', 'igp-pro' ),
				sanitize_key( $field ),
				$post_id,
				sanitize_key( $expected_type )
			)
		);
	}

	return true;
}

/**
 * Validate relationship payload and return sanitized canonical data.
 *
 * @param mixed $payload   Raw relationship payload.
 * @param int   $object_id Optional owner post ID for self-reference checks.
 * @return array<string,int|int[]>|WP_Error
 */
function igp_pro_validate_relationship_payload( $payload, int $object_id = 0 ) {
	$sanitized   = igp_pro_sanitize_relationship_payload( $payload );
	$definitions = igp_pro_get_relationship_field_definitions();
	$owner_type  = $object_id > 0 ? (string) get_post_type( $object_id ) : '';

	foreach ( $definitions as $field => $definition ) {
		$expected_type = (string) $definition['post_type'];
		$ids           = ! empty( $definition['multiple'] ) ? (array) $sanitized[ $field ] : array( absint( $sanitized[ $field ] ) );

		foreach ( $ids as $target_id ) {
			$target_id = absint( $target_id );
			if ( $target_id <= 0 ) {
				continue;
			}

			if ( $object_id > 0 && $target_id === $object_id && $owner_type === $expected_type ) {
				return new WP_Error(
					'igp_pro_relationship_self_reference',
					sprintf(
						/* translators: %s: field name. */
						__( 'Relationship field cannot reference the current post: %s.', 'igp-pro' ),
						sanitize_key( $field )
					)
				);
			}

			$target_validation = igp_pro_validate_relationship_target_id( $target_id, $expected_type, $field );
			if ( is_wp_error( $target_validation ) ) {
				return $target_validation;
			}
		}
	}

	return $sanitized;
}

/**
 * Drop missing or wrong-type relationship references for safe frontend reads.
 *
 * @param array<string,int|int[]> $payload Relationship payload.
 * @return array<string,int|int[]>
 */
function igp_pro_filter_existing_relationship_payload( array $payload ): array {
	$payload     = igp_pro_sanitize_relationship_payload( $payload );
	$definitions = igp_pro_get_relationship_field_definitions();
	$filtered    = array();

	foreach ( $definitions as $field => $definition ) {
		$expected_type = (string) $definition['post_type'];

		if ( ! empty( $definition['multiple'] ) ) {
			$ids = array();
			foreach ( (array) $payload[ $field ] as $target_id ) {
				$target_id = absint( $target_id );
				if ( $target_id > 0 && $expected_type === get_post_type( $target_id ) ) {
					$ids[] = $target_id;
				}
			}
			$filtered[ $field ] = array_values( array_unique( $ids ) );
		} else {
			$target_id = absint( $payload[ $field ] );
			$filtered[ $field ] = ( $target_id > 0 && $expected_type === get_post_type( $target_id ) ) ? $target_id : 0;
		}
	}

	return $filtered;
}
