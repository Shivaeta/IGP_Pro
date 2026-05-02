<?php
/**
 * Relationship-aware query helpers for IGP Pro V2.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return destination query options for relationship admin UI.
 *
 * @param array $args Optional query args.
 * @return WP_Post[]
 */
function igp_pro_get_relationship_destination_options( array $args = array() ): array {
	$query = new WP_Query(
		wp_parse_args(
			$args,
			array(
				'post_type'              => 'destination',
				'post_status'            => array( 'publish', 'draft', 'private', 'pending' ),
				'posts_per_page'         => 200,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		)
	);

	return is_array( $query->posts ) ? $query->posts : array();
}

/**
 * Return tour query options for relationship admin UI.
 *
 * @param array $args Optional query args.
 * @return WP_Post[]
 */
function igp_pro_get_relationship_tour_options( array $args = array() ): array {
	$query = new WP_Query(
		wp_parse_args(
			$args,
			array(
				'post_type'              => 'tour',
				'post_status'            => array( 'publish', 'draft', 'private', 'pending' ),
				'posts_per_page'         => 200,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		)
	);

	return is_array( $query->posts ) ? $query->posts : array();
}

/**
 * Build relationship-aware WP_Query args for related tours block.
 *
 * @param int   $post_id Owner post ID.
 * @param int   $limit   Limit.
 * @param array $extra   Extra query args.
 * @return WP_Query|null Null means caller should use legacy fallback query.
 */
function igp_pro_get_related_tours_query( int $post_id, int $limit = 3, array $extra = array() ): ?WP_Query {
	$limit     = max( 1, min( 12, $limit ) );
	$post_type = get_post_type( $post_id );

	if ( 'destination' === $post_type ) {
		return IGP_Relationships::get_tours_for_destination(
			$post_id,
			wp_parse_args(
				$extra,
				array(
					'posts_per_page' => $limit,
				)
			)
		);
	}

	$ids = IGP_Relationships::get_related_tours( $post_id, 'block' );
	if ( empty( $ids ) ) {
		return null;
	}

	return igp_pro_get_listing_query(
		'tour',
		max( $limit, count( $ids ) ),
		array_slice( $ids, 0, $limit ),
		$extra
	);
}

/**
 * Build relationship-aware WP_Query args for related destinations block.
 *
 * @param int   $post_id Owner post ID.
 * @param int   $limit   Limit.
 * @param array $extra   Extra query args.
 * @return WP_Query|null Null means caller should use legacy fallback query.
 */
function igp_pro_get_related_destinations_query( int $post_id, int $limit = 3, array $extra = array() ): ?WP_Query {
	$ids = IGP_Relationships::get_related_destinations( $post_id, 'block' );
	if ( empty( $ids ) ) {
		return null;
	}

	$limit = max( 1, min( 12, $limit ) );

	return igp_pro_get_listing_query(
		'destination',
		max( $limit, count( $ids ) ),
		array_slice( $ids, 0, $limit ),
		$extra
	);
}

/**
 * Count tours related to a destination.
 *
 * @param int $destination_id Destination ID.
 * @return int
 */
function igp_pro_count_tours_for_destination( int $destination_id ): int {
	$query = IGP_Relationships::get_tours_for_destination(
		$destination_id,
		array(
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	return absint( $query->found_posts );
}
