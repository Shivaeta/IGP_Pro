<?php
/**
 * Link Whisper-facing content provider for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return Content Graph projection suitable for link analysis tools.
 */
function igp_pro_link_whisper_get_content( int $post_id, string $target = 'link_whisper_content' ): string {
	if ( $post_id <= 0 ) {
		return '';
	}

	if ( function_exists( 'igp_pro_project_post_content_graph' ) ) {
		$projection = igp_pro_project_post_content_graph( $post_id, $target );
		if ( is_string( $projection ) && '' !== trim( $projection ) ) {
			return $projection;
		}
	}

	$post = get_post( $post_id );
	return $post instanceof WP_Post ? wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ) : '';
}

/**
 * Return analysis payload for external companion tools.
 */
function igp_pro_link_whisper_get_analysis_payload( int $post_id ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	return array(
		'post_id'              => $post_id,
		'post_type'            => (string) $post->post_type,
		'title'                => get_the_title( $post_id ),
		'permalink'            => get_permalink( $post_id ),
		'content'              => igp_pro_link_whisper_get_content( $post_id, 'link_whisper_content' ),
		'search_index_text'    => igp_pro_link_whisper_get_content( $post_id, 'search_index_text' ),
		'plain_text_summary'   => igp_pro_link_whisper_get_content( $post_id, 'plain_text_summary' ),
		'internal_link_report' => function_exists( 'igp_pro_generate_internal_link_opportunities' ) ? igp_pro_generate_internal_link_opportunities( $post_id ) : array(),
	);
}

/**
 * Return a bounded set of posts for link-analysis workflows.
 */
function igp_pro_link_whisper_get_posts_for_analysis( array $args = array() ): array {
	$query = new WP_Query(
		array(
			'post_type'           => isset( $args['post_type'] ) ? (array) $args['post_type'] : array( 'page', 'post', 'tour', 'destination' ),
			'post_status'         => 'publish',
			'posts_per_page'      => isset( $args['posts_per_page'] ) ? max( 1, min( 100, absint( $args['posts_per_page'] ) ) ) : 25,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	$payloads = array();
	foreach ( $query->posts as $post ) {
		if ( $post instanceof WP_Post ) {
			$payloads[] = igp_pro_link_whisper_get_analysis_payload( $post->ID );
		}
	}

	return $payloads;
}
