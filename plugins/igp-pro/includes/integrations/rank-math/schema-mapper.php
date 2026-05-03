<?php
/**
 * Rank Math schema mapper for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return schema graph entities keyed for Rank Math's json_ld filter.
 */
function igp_pro_rank_math_get_schema_entities( int $post_id ): array {
	if ( ! function_exists( 'igp_pro_generate_json_ld' ) ) {
		return array();
	}

	$json_ld = igp_pro_generate_json_ld( $post_id );
	if ( ! is_array( $json_ld ) ) {
		return array();
	}

	$graph = isset( $json_ld['@graph'] ) && is_array( $json_ld['@graph'] ) ? $json_ld['@graph'] : array();
	if ( empty( $graph ) ) {
		return array();
	}

	$entities = array();
	foreach ( $graph as $index => $entity ) {
		if ( ! is_array( $entity ) || empty( $entity['@type'] ) ) {
			continue;
		}

		$key = ! empty( $entity['@id'] ) ? sanitize_key( (string) $entity['@id'] ) : 'igp_entity_' . absint( $index );
		if ( '' === $key ) {
			$key = 'igp_entity_' . absint( $index );
		}

		$entities[ 'igp_' . $key ] = $entity;
	}

	return $entities;
}

/**
 * Merge IGP schema into a Rank Math json_ld payload while avoiding duplicates.
 *
 * @param array $rank_math_data Existing Rank Math schema payload.
 * @param int   $post_id        Post ID.
 * @return array
 */
function igp_pro_rank_math_merge_schema_graph( array $rank_math_data, int $post_id ): array {
	$igp_entities = igp_pro_rank_math_get_schema_entities( $post_id );
	if ( empty( $igp_entities ) ) {
		return $rank_math_data;
	}

	$existing_signatures = igp_pro_rank_math_schema_signatures( $rank_math_data );

	foreach ( $igp_entities as $key => $entity ) {
		$signature = igp_pro_rank_math_schema_signature( $entity );
		if ( '' !== $signature && in_array( $signature, $existing_signatures, true ) ) {
			continue;
		}

		$rank_math_data[ $key ] = $entity;
		if ( '' !== $signature ) {
			$existing_signatures[] = $signature;
		}
	}

	return $rank_math_data;
}

/**
 * Build duplicate-detection signatures for a schema payload.
 */
function igp_pro_rank_math_schema_signatures( array $schema_payload ): array {
	$signatures = array();
	foreach ( $schema_payload as $entity ) {
		if ( ! is_array( $entity ) ) {
			continue;
		}
		$signature = igp_pro_rank_math_schema_signature( $entity );
		if ( '' !== $signature ) {
			$signatures[] = $signature;
		}
	}
	return array_values( array_unique( $signatures ) );
}

/**
 * Build a stable schema entity signature.
 */
function igp_pro_rank_math_schema_signature( array $entity ): string {
	$type = isset( $entity['@type'] ) ? ( is_array( $entity['@type'] ) ? implode( ',', $entity['@type'] ) : (string) $entity['@type'] ) : '';
	$id   = isset( $entity['@id'] ) ? (string) $entity['@id'] : '';
	$name = isset( $entity['name'] ) ? (string) $entity['name'] : '';

	if ( '' === $type && '' === $id && '' === $name ) {
		return '';
	}

	return md5( strtolower( $type . '|' . $id . '|' . $name ) );
}

/**
 * Return breadcrumb items in a shape Rank Math filters can consume.
 */
function igp_pro_rank_math_map_breadcrumbs( int $post_id ): array {
	$items = function_exists( 'igp_pro_rank_math_get_breadcrumb_items' ) ? igp_pro_rank_math_get_breadcrumb_items( $post_id ) : array();
	$mapped = array();

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$mapped[] = array(
			'name' => sanitize_text_field( (string) ( $item['name'] ?? '' ) ),
			'url'  => esc_url_raw( (string) ( $item['item'] ?? '' ) ),
		);
	}

	return array_values( array_filter( $mapped, static function ( array $item ): bool {
		return '' !== $item['name'];
	} ) );
}
