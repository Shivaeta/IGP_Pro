<?php
/**
 * Content projection service for IGP Pro V2.
 *
 * The projection service converts structured Content Graph data into semantic
 * text/HTML for SEO audits, Rank Math, Link Whisper, MCP, search indexing, and
 * summaries. It does not replace the frontend renderer and does not mutate the
 * input graph.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return supported projection targets.
 *
 * @return string[]
 */
function igp_pro_get_content_projection_targets(): array {
	return array(
		'frontend_html',
		'seo_text',
		'analysis_html',
		'rank_math_content',
		'link_whisper_content',
		'mcp_markdown',
		'search_index_text',
		'plain_text_summary',
	);
}

/**
 * Project Content Graph into a target representation.
 *
 * @param array  $graph   Content Graph.
 * @param string $target  Projection target.
 * @param array  $context Optional context. Supports post_id.
 * @return string|WP_Error
 */
function igp_pro_project_content_graph( array $graph, string $target = 'plain_text_summary', array $context = array() ) {
	$target = sanitize_key( $target );
	if ( ! in_array( $target, igp_pro_get_content_projection_targets(), true ) ) {
		return new WP_Error( 'igp_pro_invalid_projection_target', __( 'Unsupported Content Graph projection target.', 'igp-pro' ) );
	}

	$graph_copy = $graph;
	$validation = function_exists( 'igp_pro_validate_content_graph' ) ? igp_pro_validate_content_graph( $graph_copy ) : true;
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$nodes = igp_pro_extract_projection_nodes( $graph_copy, $context );

	if ( in_array( $target, array( 'frontend_html', 'analysis_html', 'rank_math_content' ), true ) ) {
		return igp_pro_render_projection_html( $nodes, $target );
	}

	if ( 'mcp_markdown' === $target ) {
		return igp_pro_render_projection_markdown( $nodes );
	}

	return igp_pro_render_projection_text( $nodes, $target );
}

/**
 * Alias required by future integration code.
 *
 * @param array  $graph   Content Graph.
 * @param string $target  Projection target.
 * @param array  $context Optional context.
 * @return string|WP_Error
 */
function igp_get_content_projection( array $graph, string $target = 'plain_text_summary', array $context = array() ) {
	return igp_pro_project_content_graph( $graph, $target, $context );
}

/**
 * Project a stored post Content Graph.
 *
 * @param int    $post_id Post ID.
 * @param string $target  Projection target.
 * @param array  $context Optional context.
 * @return string|WP_Error
 */
function igp_pro_project_post_content_graph( int $post_id, string $target = 'plain_text_summary', array $context = array() ) {
	$graph = igp_pro_load_content_graph( $post_id );
	if ( is_wp_error( $graph ) ) {
		return $graph;
	}

	$context['post_id'] = $post_id;

	return igp_pro_project_content_graph( $graph, $target, $context );
}

/**
 * Extract semantic projection nodes from a graph.
 *
 * @param array $graph   Content Graph.
 * @param array $context Projection context.
 * @return array<int,array{type:string,text:string,level?:int,url?:string}>
 */
function igp_pro_extract_projection_nodes( array $graph, array $context = array() ): array {
	$nodes = array();

	if ( ! empty( $graph['seo'] ) && is_array( $graph['seo'] ) ) {
		foreach ( array( 'h1', 'title', 'description' ) as $seo_key ) {
			if ( ! empty( $graph['seo'][ $seo_key ] ) && is_scalar( $graph['seo'][ $seo_key ] ) ) {
				$nodes[] = array(
					'type'  => 'seo',
					'text'  => igp_pro_projection_clean_text( (string) $graph['seo'][ $seo_key ] ),
					'level' => 'h1' === $seo_key ? 1 : 0,
				);
			}
		}
	}

	$post_id = isset( $context['post_id'] ) ? absint( $context['post_id'] ) : 0;
	if ( $post_id > 0 ) {
		$title = get_the_title( $post_id );
		if ( '' !== trim( (string) $title ) ) {
			$nodes[] = array(
				'type'  => 'post_title',
				'text'  => igp_pro_projection_clean_text( (string) $title ),
				'level' => 1,
			);
		}

		if ( class_exists( 'IGP_Relationships' ) ) {
			foreach ( IGP_Relationships::get_related_destinations( $post_id, 'projection' ) as $destination_id ) {
				$destination_title = get_the_title( $destination_id );
				if ( '' !== trim( (string) $destination_title ) ) {
					$nodes[] = array(
						'type' => 'destination_name',
						'text' => igp_pro_projection_clean_text( (string) $destination_title ),
					);
				}
			}
		}
	}

	$sections = isset( $graph['sections'] ) && is_array( $graph['sections'] ) ? $graph['sections'] : array();
	foreach ( $sections as $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}

		$block_id = isset( $section['block_id'] ) ? sanitize_key( (string) $section['block_id'] ) : '';
		$data     = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();

		$nodes = array_merge( $nodes, igp_pro_extract_projection_nodes_from_block( $block_id, $data ) );
	}

	if ( ! empty( $graph['seo']['internal_link_targets'] ) && is_array( $graph['seo']['internal_link_targets'] ) ) {
		foreach ( $graph['seo']['internal_link_targets'] as $link ) {
			if ( ! is_array( $link ) ) {
				continue;
			}
			$label = igp_pro_projection_clean_text( (string) ( $link['label'] ?? $link['anchor'] ?? '' ) );
			$url   = esc_url_raw( (string) ( $link['url'] ?? '' ) );
			if ( '' !== $label ) {
				$nodes[] = array(
					'type' => 'approved_link',
					'text' => $label,
					'url'  => $url,
				);
			}
		}
	}

	return igp_pro_dedupe_projection_nodes( $nodes );
}

/**
 * Extract semantic nodes from one block data payload.
 *
 * @param string $block_id Block ID.
 * @param array  $data     Block data.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_extract_projection_nodes_from_block( string $block_id, array $data ): array {
	$nodes = array();

	$heading = igp_pro_extract_projection_heading( $data );
	if ( '' !== $heading ) {
		$nodes[] = array(
			'type'  => 'heading',
			'text'  => $heading,
			'level' => 2,
		);
	}

	foreach ( array( 'eyebrow', 'subheading', 'description', 'intro', 'summary', 'body', 'content', 'text' ) as $field ) {
		if ( ! empty( $data[ $field ] ) && is_scalar( $data[ $field ] ) ) {
			$nodes[] = array(
				'type' => in_array( $field, array( 'eyebrow', 'subheading' ), true ) ? 'supporting_text' : 'paragraph',
				'text' => igp_pro_projection_clean_text( (string) $data[ $field ] ),
			);
		}
	}

	if ( 'hero' === $block_id && ! empty( $data['cta']['label'] ) ) {
		$nodes[] = array(
			'type' => 'cta',
			'text' => igp_pro_projection_clean_text( (string) $data['cta']['label'] ),
			'url'  => ! empty( $data['cta']['url'] ) ? esc_url_raw( (string) $data['cta']['url'] ) : '',
		);
	}

	if ( 'faq' === $block_id && ! empty( $data['items'] ) ) {
		foreach ( igp_pro_normalize_list( $data['items'] ) as $item ) {
			$question = igp_pro_projection_clean_text( (string) ( $item['question'] ?? '' ) );
			$answer   = igp_pro_projection_clean_text( (string) ( $item['answer'] ?? '' ) );
			if ( '' !== $question ) {
				$nodes[] = array(
					'type'  => 'faq_question',
					'text'  => $question,
					'level' => 3,
				);
			}
			if ( '' !== $answer ) {
				$nodes[] = array(
					'type' => 'faq_answer',
					'text' => $answer,
				);
			}
		}
	}

	if ( 'itinerary' === $block_id && ! empty( $data['days'] ) ) {
		foreach ( igp_pro_normalize_list( $data['days'] ) as $day ) {
			foreach ( array( 'day_title', 'title', 'description', 'meals', 'stay' ) as $field ) {
				$value = igp_pro_projection_clean_text( (string) ( $day[ $field ] ?? '' ) );
				if ( '' !== $value ) {
					$nodes[] = array(
						'type'  => in_array( $field, array( 'day_title', 'title' ), true ) ? 'itinerary_label' : 'itinerary_detail',
						'text'  => $value,
						'level' => in_array( $field, array( 'day_title', 'title' ), true ) ? 3 : 0,
					);
				}
			}
		}
	}

	if ( in_array( $block_id, array( 'tour_facts', 'inclusions_exclusions', 'departure_dates', 'package_tiers', 'reviews_summary', 'visa_requirements', 'best_time_to_visit', 'route_timeline', 'expert_box', 'sticky_booking_cta', 'nearby_attractions', 'brochure_cta', 'pricing_summary', 'icon_list', 'stats', 'tabs', 'accordions' ), true ) ) {
		$nodes = array_merge( $nodes, igp_pro_extract_projection_nodes_recursive( $data, $block_id ) );
	}

	if ( in_array( $block_id, array( 'destination_cards', 'tour_cards', 'featured_listings', 'related_tours', 'related_destinations' ), true ) ) {
		foreach ( array( 'items', 'destination', 'destinations', 'tours' ) as $field ) {
			$ids = igp_pro_normalize_post_ids( $data[ $field ] ?? array() );
			foreach ( $ids as $id ) {
				$title = get_the_title( $id );
				if ( '' !== trim( (string) $title ) ) {
					$nodes[] = array(
						'type' => 'related_entity',
						'text' => igp_pro_projection_clean_text( (string) $title ),
					);
				}
			}
		}
	}

	if ( ! empty( $data['approved_links'] ) && is_array( $data['approved_links'] ) ) {
		foreach ( $data['approved_links'] as $link ) {
			if ( ! is_array( $link ) ) {
				continue;
			}
			$label = igp_pro_projection_clean_text( (string) ( $link['label'] ?? $link['anchor'] ?? '' ) );
			if ( '' !== $label ) {
				$nodes[] = array(
					'type' => 'approved_link',
					'text' => $label,
					'url'  => esc_url_raw( (string) ( $link['url'] ?? '' ) ),
				);
			}
		}
	}

	return $nodes;
}

/**
 * Extract a block heading without assuming Phase 8 structured heading schema.
 *
 * @param array $data Block data.
 * @return string
 */
function igp_pro_extract_projection_heading( array $data ): string {
	if ( isset( $data['heading'] ) ) {
		if ( is_array( $data['heading'] ) ) {
			if ( isset( $data['heading']['visible'] ) && false === (bool) $data['heading']['visible'] ) {
				return '';
			}
			return igp_pro_projection_clean_text( (string) ( $data['heading']['text'] ?? '' ) );
		}

		if ( is_scalar( $data['heading'] ) ) {
			return igp_pro_projection_clean_text( (string) $data['heading'] );
		}
	}

	foreach ( array( 'title', 'label' ) as $field ) {
		if ( ! empty( $data[ $field ] ) && is_scalar( $data[ $field ] ) ) {
			return igp_pro_projection_clean_text( (string) $data[ $field ] );
		}
	}

	return '';
}

/**
 * Recursively extract non-decorative text from nested arrays.
 *
 * @param mixed  $value Current value.
 * @param string $path  Current field path.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_extract_projection_nodes_recursive( $value, string $path = '' ): array {
	$nodes         = array();
	$decorative_re = '/(^|_)(image|images|background|icon|icons|variant|layout|columns|limit|style|color|ratio|url|id|ids|enable|show|source|width|height|class|classes|css)(_|$)/i';

	if ( is_scalar( $value ) ) {
		$text = igp_pro_projection_clean_text( (string) $value );
		if ( '' !== $text && ! preg_match( $decorative_re, $path ) ) {
			$nodes[] = array(
				'type' => 'semantic_field',
				'text' => $text,
			);
		}
		return $nodes;
	}

	if ( ! is_array( $value ) ) {
		return $nodes;
	}

	foreach ( $value as $key => $child ) {
		$key  = is_string( $key ) ? sanitize_key( $key ) : (string) $key;
		$next = '' === $path ? $key : $path . '_' . $key;
		$nodes = array_merge( $nodes, igp_pro_extract_projection_nodes_recursive( $child, $next ) );
	}

	return $nodes;
}

/**
 * Render HTML projection.
 *
 * @param array  $nodes  Projection nodes.
 * @param string $target Projection target.
 * @return string
 */
function igp_pro_render_projection_html( array $nodes, string $target ): string {
	$output = '';

	foreach ( $nodes as $node ) {
		$text = igp_pro_projection_clean_text( (string) ( $node['text'] ?? '' ) );
		if ( '' === $text ) {
			continue;
		}

		$type  = sanitize_key( (string) ( $node['type'] ?? 'paragraph' ) );
		$level = isset( $node['level'] ) ? max( 2, min( 4, absint( $node['level'] ) ) ) : 0;

		if ( $level > 0 && in_array( $type, array( 'heading', 'faq_question', 'itinerary_label' ), true ) ) {
			$output .= sprintf( '<h%d>%s</h%d>' . "\n", $level, esc_html( $text ), $level );
			continue;
		}

		if ( 'approved_link' === $type && ! empty( $node['url'] ) ) {
			$output .= sprintf( '<p><a href="%s">%s</a></p>' . "\n", esc_url( (string) $node['url'] ), esc_html( $text ) );
			continue;
		}

		$output .= '<p>' . esc_html( $text ) . '</p>' . "\n";
	}

	return trim( wp_kses_post( $output ) );
}

/**
 * Render Markdown projection for MCP context.
 *
 * @param array $nodes Projection nodes.
 * @return string
 */
function igp_pro_render_projection_markdown( array $nodes ): string {
	$lines = array();

	foreach ( $nodes as $node ) {
		$text = igp_pro_projection_clean_text( (string) ( $node['text'] ?? '' ) );
		if ( '' === $text ) {
			continue;
		}

		$type  = sanitize_key( (string) ( $node['type'] ?? 'paragraph' ) );
		$level = isset( $node['level'] ) ? max( 1, min( 4, absint( $node['level'] ) ) ) : 0;

		if ( $level > 0 && in_array( $type, array( 'post_title', 'heading', 'faq_question', 'itinerary_label' ), true ) ) {
			$lines[] = str_repeat( '#', $level ) . ' ' . $text;
		} elseif ( 'approved_link' === $type && ! empty( $node['url'] ) ) {
			$lines[] = '- [' . str_replace( array( '[', ']' ), '', $text ) . '](' . esc_url_raw( (string) $node['url'] ) . ')';
		} else {
			$lines[] = $text;
		}
	}

	return trim( implode( "\n\n", $lines ) );
}

/**
 * Render text projection.
 *
 * @param array  $nodes  Projection nodes.
 * @param string $target Projection target.
 * @return string
 */
function igp_pro_render_projection_text( array $nodes, string $target ): string {
	$parts = array();

	foreach ( $nodes as $node ) {
		$text = igp_pro_projection_clean_text( (string) ( $node['text'] ?? '' ) );
		if ( '' === $text ) {
			continue;
		}
		$parts[] = $text;
	}

	$text = trim( implode( "\n", array_values( array_unique( $parts ) ) ) );

	if ( 'plain_text_summary' === $target ) {
		return wp_trim_words( $text, 80, '' );
	}

	return $text;
}

/**
 * Clean projection text.
 *
 * @param string $text Raw text.
 * @return string
 */
function igp_pro_projection_clean_text( string $text ): string {
	$text = wp_strip_all_tags( html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) ) );
	$text = preg_replace( '/\s+/u', ' ', $text );
	return trim( (string) $text );
}

/**
 * Dedupe projection nodes by type/text/url.
 *
 * @param array $nodes Projection nodes.
 * @return array
 */
function igp_pro_dedupe_projection_nodes( array $nodes ): array {
	$seen   = array();
	$result = array();

	foreach ( $nodes as $node ) {
		$text = igp_pro_projection_clean_text( (string) ( $node['text'] ?? '' ) );
		if ( '' === $text ) {
			continue;
		}
		$node['text'] = $text;
		$key          = md5( sanitize_key( (string) ( $node['type'] ?? '' ) ) . '|' . strtolower( $text ) . '|' . (string) ( $node['url'] ?? '' ) );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$result[]     = $node;
	}

	return $result;
}
