<?php
/**
 * Rank Math content provider for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the current singular post ID if supported.
 */
function igp_pro_rank_math_get_current_post_id(): int {
	$post_id = is_singular() ? absint( get_queried_object_id() ) : 0;
	$post    = $post_id > 0 ? get_post( $post_id ) : null;

	if ( ! $post instanceof WP_Post ) {
		return 0;
	}

	return in_array( $post->post_type, array( 'post', 'page', 'tour', 'destination' ), true ) ? $post_id : 0;
}

/**
 * Load graph safely for Rank Math contexts.
 */
function igp_pro_rank_math_get_content_graph( int $post_id ): array {
	if ( function_exists( 'igp_pro_seo_get_content_graph' ) ) {
		$graph = igp_pro_seo_get_content_graph( $post_id );
		if ( is_array( $graph ) ) {
			return $graph;
		}
	}

	if ( function_exists( 'igp_pro_load_content_graph' ) ) {
		$graph = igp_pro_load_content_graph( $post_id );
		if ( is_array( $graph ) ) {
			return $graph;
		}
	}

	return array( 'version' => 'v1', 'sections' => array() );
}

/**
 * Read the SEO object stored in Content Graph, if present.
 */
function igp_pro_rank_math_get_graph_seo( int $post_id ): array {
	$graph = igp_pro_rank_math_get_content_graph( $post_id );
	$seo   = isset( $graph['seo'] ) && is_array( $graph['seo'] ) ? $graph['seo'] : array();

	return array(
		'h1'                    => isset( $seo['h1'] ) ? sanitize_text_field( (string) $seo['h1'] ) : sanitize_text_field( (string) get_post_meta( $post_id, '_igp_seo_h1', true ) ),
		'title'                 => isset( $seo['title'] ) ? sanitize_text_field( (string) $seo['title'] ) : sanitize_text_field( (string) get_post_meta( $post_id, '_igp_seo_title', true ) ),
		'description'           => isset( $seo['description'] ) ? sanitize_textarea_field( (string) $seo['description'] ) : sanitize_textarea_field( (string) get_post_meta( $post_id, '_igp_pro_meta_description', true ) ),
		'canonical_url'         => isset( $seo['canonical_url'] ) ? esc_url_raw( (string) $seo['canonical_url'] ) : esc_url_raw( (string) get_post_meta( $post_id, '_igp_seo_canonical_url', true ) ),
		'robots'                => isset( $seo['robots'] ) ? sanitize_text_field( (string) $seo['robots'] ) : sanitize_text_field( (string) get_post_meta( $post_id, '_igp_seo_robots', true ) ),
		'og_title'              => isset( $seo['og_title'] ) ? sanitize_text_field( (string) $seo['og_title'] ) : sanitize_text_field( (string) get_post_meta( $post_id, '_igp_seo_og_title', true ) ),
		'og_description'        => isset( $seo['og_description'] ) ? sanitize_textarea_field( (string) $seo['og_description'] ) : sanitize_textarea_field( (string) get_post_meta( $post_id, '_igp_seo_og_description', true ) ),
		'og_image_id'           => isset( $seo['og_image_id'] ) ? absint( $seo['og_image_id'] ) : absint( get_post_meta( $post_id, '_igp_seo_og_image_id', true ) ),
		'schema_policy'         => isset( $seo['schema_policy'] ) ? sanitize_key( (string) $seo['schema_policy'] ) : sanitize_key( (string) get_post_meta( $post_id, '_igp_seo_schema_policy', true ) ),
		'focus_topics'          => isset( $seo['focus_topics'] ) && is_array( $seo['focus_topics'] ) ? array_values( array_filter( array_map( 'sanitize_text_field', $seo['focus_topics'] ) ) ) : array(),
		'internal_link_targets' => isset( $seo['internal_link_targets'] ) && is_array( $seo['internal_link_targets'] ) ? $seo['internal_link_targets'] : array(),
	);
}

/**
 * Return all Rank Math-facing SEO data for a post.
 */
function igp_pro_rank_math_get_seo_data( int $post_id ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	$graph = igp_pro_rank_math_get_content_graph( $post_id );
	$seo   = igp_pro_rank_math_get_graph_seo( $post_id );

	$title = '' !== $seo['title'] ? $seo['title'] : ( function_exists( 'igp_pro_generate_seo_title' ) ? igp_pro_generate_seo_title( $post_id ) : get_the_title( $post_id ) );
	$desc  = '' !== $seo['description'] ? $seo['description'] : ( function_exists( 'igp_pro_generate_meta_description' ) ? igp_pro_generate_meta_description( $post_id ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 ) );

	$canonical = '' !== $seo['canonical_url'] ? $seo['canonical_url'] : get_permalink( $post_id );
	$robots    = '' !== $seo['robots'] ? $seo['robots'] : ( 'publish' === get_post_status( $post_id ) && (int) get_option( 'blog_public', 1 ) === 1 ? 'index,follow' : 'noindex,nofollow' );

	$og_title       = '' !== $seo['og_title'] ? $seo['og_title'] : $title;
	$og_description = '' !== $seo['og_description'] ? $seo['og_description'] : $desc;
	$og_image       = '';
	if ( $seo['og_image_id'] > 0 ) {
		$og_image = (string) wp_get_attachment_image_url( $seo['og_image_id'], 'full' );
	}
	if ( '' === $og_image && function_exists( 'igp_pro_seo_get_primary_image' ) ) {
		$og_image = igp_pro_seo_get_primary_image( $post_id, $graph );
	}

	return array(
		'post_id'               => $post_id,
		'post_type'             => $post->post_type,
		'title'                 => trim( wp_strip_all_tags( (string) $title ) ),
		'description'           => trim( wp_strip_all_tags( (string) $desc ) ),
		'canonical'             => esc_url_raw( (string) $canonical ),
		'robots'                => igp_pro_rank_math_parse_robots( $robots ),
		'robots_string'         => $robots,
		'og_title'              => trim( wp_strip_all_tags( (string) $og_title ) ),
		'og_description'        => trim( wp_strip_all_tags( (string) $og_description ) ),
		'og_image'              => esc_url_raw( (string) $og_image ),
		'schema_policy'         => $seo['schema_policy'],
		'focus_topics'          => $seo['focus_topics'],
		'internal_link_targets' => $seo['internal_link_targets'],
		'breadcrumbs'           => function_exists( 'igp_pro_rank_math_get_breadcrumb_items' ) ? igp_pro_rank_math_get_breadcrumb_items( $post_id ) : array(),
		'analysis_content'      => igp_pro_rank_math_get_analysis_content( $post_id ),
		'link_analysis_content' => igp_pro_rank_math_get_link_analysis_content( $post_id ),
		'image_analysis_content'=> igp_pro_rank_math_get_image_analysis_content( $post_id ),
	);
}

/**
 * Parse robots string into Rank Math-style directive array.
 *
 * @param string|array $robots Robots value.
 * @return array<string,string>
 */
function igp_pro_rank_math_parse_robots( $robots ): array {
	if ( is_array( $robots ) ) {
		$parsed = array();
		foreach ( $robots as $key => $value ) {
			$parsed[ sanitize_key( (string) $key ) ] = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '1';
		}
		return $parsed;
	}

	$parts  = preg_split( '/[\s,]+/', strtolower( (string) $robots ) ) ?: array();
	$output = array();
	foreach ( $parts as $part ) {
		$part = sanitize_key( $part );
		if ( '' === $part ) {
			continue;
		}
		$output[ $part ] = '1';
	}

	return $output;
}

/**
 * Return Content Graph projection for Rank Math analysis.
 */
function igp_pro_rank_math_get_analysis_content( int $post_id ): string {
	if ( function_exists( 'igp_pro_project_post_content_graph' ) ) {
		$projection = igp_pro_project_post_content_graph( $post_id, 'rank_math_content' );
		if ( is_string( $projection ) ) {
			return $projection;
		}
	}

	$post = get_post( $post_id );
	return $post instanceof WP_Post ? wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) : '';
}

/**
 * Return image-aware analysis text.
 */
function igp_pro_rank_math_get_image_analysis_content( int $post_id ): string {
	$parts = array();
	if ( function_exists( 'igp_pro_get_media_inventory' ) ) {
		$inventory = igp_pro_get_media_inventory( $post_id );
		$items     = isset( $inventory['items'] ) && is_array( $inventory['items'] ) ? $inventory['items'] : array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			foreach ( array( 'alt', 'title', 'source', 'url' ) as $key ) {
				if ( ! empty( $item[ $key ] ) && is_scalar( $item[ $key ] ) ) {
					$parts[] = wp_strip_all_tags( (string) $item[ $key ] );
				}
			}
		}
	}

	return trim( implode( ' ', array_unique( array_filter( $parts ) ) ) );
}

/**
 * Return link-analysis content.
 */
function igp_pro_rank_math_get_link_analysis_content( int $post_id ): string {
	if ( function_exists( 'igp_pro_project_post_content_graph' ) ) {
		$projection = igp_pro_project_post_content_graph( $post_id, 'link_whisper_content' );
		if ( is_string( $projection ) ) {
			return $projection;
		}
	}

	return igp_pro_rank_math_get_analysis_content( $post_id );
}

/**
 * Return breadcrumb data for Rank Math bridge.
 */
function igp_pro_rank_math_get_breadcrumb_items( int $post_id ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	if ( function_exists( 'igp_pro_seo_get_breadcrumb_items' ) ) {
		return igp_pro_seo_get_breadcrumb_items( $post );
	}

	return array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => get_bloginfo( 'name' ),
			'item'     => home_url( '/' ),
		),
		array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => get_the_title( $post ),
			'item'     => get_permalink( $post ),
		),
	);
}
