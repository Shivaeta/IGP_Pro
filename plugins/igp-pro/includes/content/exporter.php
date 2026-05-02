<?php
/**
 * Content Graph exporter.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Create a structured export payload for a post.
 *
 * @param int $post_id Post ID.
 * @return array|WP_Error
 */
function igp_pro_export_content_graph( int $post_id ) {
	if ( $post_id <= 0 ) {
		return new WP_Error( 'igp_pro_invalid_post_id', __( 'A valid post ID is required.', 'igp-pro' ) );
	}

	$graph = igp_pro_load_content_graph( $post_id );
	if ( is_wp_error( $graph ) ) {
		return $graph;
	}

	return array(
		'type'        => 'igp_pro_content_graph',
		'version'     => 'v1',
		'exported_at' => gmdate( 'c' ),
		'post'        => array(
			'id'         => $post_id,
			'title'      => get_the_title( $post_id ),
			'post_type'  => get_post_type( $post_id ),
			'permalink'  => get_permalink( $post_id ),
		),
		'meta'        => array(
			'description' => igp_pro_load_meta_description( $post_id ),
		),
		'graph'       => $graph,
	);
}
