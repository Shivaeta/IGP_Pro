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

	$hero_h1 = igp_pro_find_first_hero_heading_for_h1( $graph['sections'] ?? array() );
	if ( is_array( $hero_h1 ) && '' !== (string) ( $hero_h1['text'] ?? '' ) ) {
		return array_merge( $h1, $hero_h1 );
	}

	return $h1;
}

/**
 * Find the first Hero heading recursively for page-H1 fallback.
 *
 * @param array<int,mixed> $sections Sections.
 * @return array<string,string>|null
 */
function igp_pro_find_first_hero_heading_for_h1( array $sections ) {
	foreach ( $sections as $index => $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$block_id = sanitize_key( (string) ( $section['block_id'] ?? '' ) );
		if ( 'hero' === $block_id ) {
			$data    = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
			$heading = function_exists( 'igp_pro_normalize_block_heading' ) ? igp_pro_normalize_block_heading( $data, 'hero' ) : array( 'text' => '' );
			if ( '' !== trim( (string) ( $heading['text'] ?? '' ) ) ) {
				return array(
					'text'       => trim( (string) $heading['text'] ),
					'source'     => 'hero_fallback',
					'section_id' => isset( $section['id'] ) && '' !== (string) $section['id'] ? sanitize_key( (string) $section['id'] ) : 'section_' . ( $index + 1 ),
				);
			}
		}
		if ( ! empty( $section['children'] ) && is_array( $section['children'] ) ) {
			$child = igp_pro_find_first_hero_heading_for_h1( $section['children'] );
			if ( is_array( $child ) ) {
				return $child;
			}
		}
	}

	return null;
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
	igp_pro_collect_graph_block_headings_from_sections( $graph['sections'] ?? array(), $headings );
	return $headings;
}

/**
 * Collect visible block headings recursively.
 *
 * @param array<int,mixed>        $sections Sections.
 * @param array<int,array<string,mixed>> $headings Headings.
 * @param string                  $prefix Path prefix.
 * @return void
 */
function igp_pro_collect_graph_block_headings_from_sections( array $sections, array &$headings, string $prefix = '' ): void {
	foreach ( $sections as $index => $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$block_id = sanitize_key( (string) ( $section['block_id'] ?? '' ) );
		$data     = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$heading  = function_exists( 'igp_pro_normalize_block_heading' ) ? igp_pro_normalize_block_heading( $data, $block_id ) : array( 'text' => '', 'level' => 'h2', 'visible' => false );
		if ( ! empty( $heading['visible'] ) && '' !== trim( (string) $heading['text'] ) ) {
			$headings[] = array(
				'index'    => '' === $prefix ? $index : $prefix . '.' . $index,
				'block_id' => $block_id,
				'level'    => sanitize_key( (string) $heading['level'] ),
				'text'     => trim( (string) $heading['text'] ),
			);
		}
		if ( ! empty( $section['children'] ) && is_array( $section['children'] ) ) {
			igp_pro_collect_graph_block_headings_from_sections( $section['children'], $headings, '' === $prefix ? (string) $index : $prefix . '.' . $index );
		}
	}
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
