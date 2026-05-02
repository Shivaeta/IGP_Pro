<?php
/**
 * Semantic outline engine for IGP-rendered pages.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the page-level H1 according to V2 policy.
 *
 * @param array<string,mixed> $graph Content Graph.
 * @param array<string,mixed> $context Context; accepts post_id.
 * @return array{text:string,source:string,section_id:string}
 */
function igp_pro_resolve_page_h1( array $graph, array $context = array() ): array {
	$h1 = array(
		'text'       => '',
		'source'     => 'none',
		'section_id' => '',
	);

	if ( isset( $graph['seo'] ) && is_array( $graph['seo'] ) && ! empty( $graph['seo']['h1'] ) ) {
		$h1['text']   = trim( wp_strip_all_tags( (string) $graph['seo']['h1'] ) );
		$h1['source'] = 'seo.h1';
		return $h1;
	}

	$post_id = isset( $context['post_id'] ) ? absint( $context['post_id'] ) : ( function_exists( 'get_the_ID' ) ? absint( get_the_ID() ) : 0 );
	if ( $post_id > 0 ) {
		$title = trim( wp_strip_all_tags( (string) get_the_title( $post_id ) ) );
		if ( '' !== $title ) {
			$h1['text']   = $title;
			$h1['source'] = 'post_title';
			return $h1;
		}
	}

	foreach ( $graph['sections'] ?? array() as $index => $section ) {
		if ( ! is_array( $section ) || 'hero' !== sanitize_key( (string) ( $section['block_id'] ?? '' ) ) ) {
			continue;
		}

		$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$heading = function_exists( 'igp_pro_normalize_block_heading' ) ? igp_pro_normalize_block_heading( $data, 'hero' ) : array( 'text' => '' );
		if ( '' !== trim( (string) ( $heading['text'] ?? '' ) ) ) {
			$h1['text']       = trim( (string) $heading['text'] );
			$h1['source']     = 'hero_fallback';
			$h1['section_id'] = isset( $section['id'] ) && '' !== (string) $section['id'] ? sanitize_key( (string) $section['id'] ) : 'section_' . ( $index + 1 );
			return $h1;
		}
	}

	return $h1;
}

/**
 * Build a semantic outline for a Content Graph.
 *
 * @param array<string,mixed> $graph Content Graph.
 * @param array<string,mixed> $context Context.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_build_semantic_outline( array $graph, array $context = array() ) {
	$validation = igp_pro_validate_heading_hierarchy( $graph );
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	return array(
		'h1'       => igp_pro_resolve_page_h1( $graph, $context ),
		'headings' => igp_pro_collect_graph_block_headings( $graph ),
	);
}

/**
 * Collect visible block headings from a graph.
 *
 * @param array<string,mixed> $graph Content Graph.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_collect_graph_block_headings( array $graph ): array {
	$headings = array();
	foreach ( $graph['sections'] ?? array() as $index => $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$block_id = sanitize_key( (string) ( $section['block_id'] ?? '' ) );
		$data     = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$heading  = function_exists( 'igp_pro_normalize_block_heading' ) ? igp_pro_normalize_block_heading( $data, $block_id ) : array( 'text' => '', 'level' => 'h2', 'visible' => false );
		if ( empty( $heading['visible'] ) || '' === trim( (string) $heading['text'] ) ) {
			continue;
		}
		$headings[] = array(
			'index'    => $index,
			'block_id' => $block_id,
			'level'    => sanitize_key( (string) $heading['level'] ),
			'text'     => trim( (string) $heading['text'] ),
		);
	}
	return $headings;
}

/**
 * Validate block-level heading hierarchy. H1 is page-level only.
 *
 * @param array<string,mixed> $graph Content Graph.
 * @return true|WP_Error
 */
function igp_pro_validate_heading_hierarchy( array $graph ) {
	$previous_level = 1; // Page H1 is assumed before block-level headings.

	foreach ( igp_pro_collect_graph_block_headings( $graph ) as $heading ) {
		$level = absint( str_replace( 'h', '', (string) $heading['level'] ) );
		if ( $level < 2 || $level > 4 ) {
			return new WP_Error( 'igp_pro_invalid_block_heading_level', __( 'Block headings must use h2, h3, or h4.', 'igp-pro' ) );
		}
		if ( $level > $previous_level + 1 ) {
			return new WP_Error(
				'igp_pro_heading_hierarchy_jump',
				sprintf(
					__( 'Heading hierarchy jumps from H%1$d to H%2$d near "%3$s".', 'igp-pro' ),
					$previous_level,
					$level,
					sanitize_text_field( (string) $heading['text'] )
				)
			);
		}
		$previous_level = $level;
	}

	return true;
}
