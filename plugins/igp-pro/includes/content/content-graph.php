<?php
/**
 * Content Graph storage and rendering.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_META_DESCRIPTION_META_KEY' ) ) {
	define( 'IGP_PRO_META_DESCRIPTION_META_KEY', '_igp_pro_meta_description' );
}

/**
 * Return the default empty content graph.
 *
 * @return array
 */
function igp_pro_get_empty_content_graph(): array {
	return array(
		'version'  => 'v1',
		'sections' => array(),
	);
}

/**
 * Load the content graph from post meta.
 *
 * @param int $post_id Post ID.
 * @return array|WP_Error
 */
function igp_pro_load_content_graph( int $post_id ) {
	if ( $post_id <= 0 ) {
		return new WP_Error( 'igp_pro_invalid_post_id', __( 'A valid post ID is required.', 'igp-pro' ) );
	}

	$stored = get_post_meta( $post_id, IGP_PRO_CONTENT_GRAPH_META_KEY, true );

	if ( '' === $stored || null === $stored ) {
		return igp_pro_get_empty_content_graph();
	}

	if ( is_array( $stored ) ) {
		$graph = $stored;
	} elseif ( is_string( $stored ) ) {
		$graph = igp_pro_json_decode_array( $stored );
	} else {
		return new WP_Error( 'igp_pro_invalid_stored_graph', __( 'Stored content graph has an invalid format.', 'igp-pro' ) );
	}

	if ( is_wp_error( $graph ) ) {
		return $graph;
	}

	$validation = igp_pro_validate_content_graph( $graph );

	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	return $graph;
}

/**
 * Save a content graph to post meta.
 *
 * @param int          $post_id Post ID.
 * @param array|string $graph   Graph array or JSON string.
 * @return true|WP_Error
 */
function igp_pro_save_content_graph( int $post_id, $graph ) {
	if ( $post_id <= 0 ) {
		return new WP_Error( 'igp_pro_invalid_post_id', __( 'A valid post ID is required.', 'igp-pro' ) );
	}

	if ( is_string( $graph ) ) {
		$graph = igp_pro_json_decode_array( $graph );
	}

	if ( is_wp_error( $graph ) ) {
		return $graph;
	}

	if ( ! is_array( $graph ) ) {
		return new WP_Error( 'igp_pro_invalid_graph', __( 'Content graph must be an array or valid JSON object.', 'igp-pro' ) );
	}

	$graph = function_exists( 'igp_pro_sanitize_content_graph_payload' ) ? igp_pro_sanitize_content_graph_payload( $graph ) : $graph;

	$validation = igp_pro_validate_content_graph( $graph );

	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$encoded = wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	if ( ! is_string( $encoded ) || '' === $encoded ) {
		return new WP_Error( 'igp_pro_graph_encode_failed', __( 'Content graph could not be encoded.', 'igp-pro' ) );
	}

	update_post_meta( $post_id, IGP_PRO_CONTENT_GRAPH_META_KEY, $encoded );

	return true;
}

/**
 * Validate the content graph.
 *
 * @param array $graph Content graph.
 * @return true|WP_Error
 */
function igp_pro_validate_content_graph( array $graph ) {
	if ( function_exists( 'igp_pro_validate_content_graph_payload' ) ) {
		return igp_pro_validate_content_graph_payload( $graph );
	}

	if ( empty( $graph['version'] ) || ! is_string( $graph['version'] ) ) {
		return new WP_Error( 'igp_pro_graph_missing_version', __( 'Content graph version is required.', 'igp-pro' ) );
	}

	if ( ! array_key_exists( 'sections', $graph ) || ! is_array( $graph['sections'] ) ) {
		return new WP_Error( 'igp_pro_graph_missing_sections', __( 'Content graph sections must be an array.', 'igp-pro' ) );
	}

	return true;
}

/**
 * Render every section in a content graph through the central renderer.
 *
 * @param array $graph Content graph.
 * @return string|WP_Error
 */
function igp_pro_render_content_graph( array $graph ) {
	$validation = igp_pro_validate_content_graph( $graph );

	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$output = '';

	foreach ( $graph['sections'] as $section ) {
		$output .= igp_pro_render_block(
			(string) $section['block_id'],
			isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array(),
			array(
				'section' => $section,
				'graph'   => $graph,
			)
		);
	}

	return $output;
}

/**
 * Load a saved meta description.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function igp_pro_load_meta_description( int $post_id ): string {
	return sanitize_textarea_field( (string) get_post_meta( $post_id, IGP_PRO_META_DESCRIPTION_META_KEY, true ) );
}

/**
 * Save a meta description.
 *
 * @param int    $post_id     Post ID.
 * @param string $description Meta description.
 * @return true|WP_Error
 */
function igp_pro_save_meta_description( int $post_id, string $description ) {
	if ( $post_id <= 0 ) {
		return new WP_Error( 'igp_pro_invalid_post_id', __( 'A valid post ID is required.', 'igp-pro' ) );
	}

	update_post_meta( $post_id, IGP_PRO_META_DESCRIPTION_META_KEY, sanitize_textarea_field( $description ) );

	return true;
}

/**
 * Load the editor graph from the canonical editing layer.
 *
 * The Content Editor must stay linked to existing Gutenberg content. Therefore,
 * when a post already contains IGP Pro blocks in post_content, those blocks are
 * parsed into a graph on Load. If the post has no IGP blocks, saved graph meta is
 * used. If neither exists, an empty graph is returned.
 *
 * @param int $post_id Post ID.
 * @return array|WP_Error
 */
function igp_pro_load_content_graph_for_editor( int $post_id ) {
	if ( $post_id <= 0 ) {
		return new WP_Error( 'igp_pro_invalid_post_id', __( 'A valid post ID is required.', 'igp-pro' ) );
	}

	$post_graph = igp_pro_content_graph_from_post_content( $post_id );
	if ( is_wp_error( $post_graph ) ) {
		return $post_graph;
	}

	if ( ! empty( $post_graph['sections'] ) ) {
		return array(
			'graph'   => $post_graph,
			'source'  => 'post_content',
			'message' => __( 'Existing WordPress/Gutenberg IGP blocks loaded into the Content Graph editor.', 'igp-pro' ),
		);
	}

	$stored_graph = igp_pro_load_content_graph( $post_id );
	if ( is_wp_error( $stored_graph ) ) {
		return $stored_graph;
	}

	return array(
		'graph'   => $stored_graph,
		'source'  => empty( $stored_graph['sections'] ) ? 'empty' : 'post_meta',
		'message' => empty( $stored_graph['sections'] )
			? __( 'No existing IGP blocks or saved Content Graph found. Started with an empty graph.', 'igp-pro' )
			: __( 'Saved Content Graph meta loaded.', 'igp-pro' ),
	);
}

/**
 * Parse existing Gutenberg IGP Pro blocks from post_content into a Content Graph.
 *
 * @param int $post_id Post ID.
 * @return array|WP_Error
 */
function igp_pro_content_graph_from_post_content( int $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return new WP_Error( 'igp_pro_post_not_found', __( 'Post could not be found.', 'igp-pro' ) );
	}

	$blocks   = parse_blocks( (string) $post->post_content );
	$sections = array();

	igp_pro_collect_igp_sections_from_blocks( $blocks, $sections );

	$graph = array(
		'version'  => 'v1',
		'sections' => $sections,
	);

	if ( empty( $sections ) ) {
		return $graph;
	}

	$graph = function_exists( 'igp_pro_sanitize_content_graph_payload' ) ? igp_pro_sanitize_content_graph_payload( $graph ) : $graph;

	// Existing Gutenberg blocks may be incomplete drafts. Load them into the
	// editor so the user can finish required fields; strict validation still runs
	// before Save through igp_pro_save_content_graph().
	return $graph;
}

/**
 * Recursively collect IGP Pro blocks from parsed Gutenberg blocks.
 *
 * @param array $blocks   Parsed blocks.
 * @param array $sections Collected sections passed by reference.
 */
function igp_pro_collect_igp_sections_from_blocks( array $blocks, array &$sections ): void {
	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';

		if ( igp_pro_is_igp_wp_block_name( $block_name ) ) {
			$block_id = igp_pro_wp_block_name_to_block_id( $block_name );
			if ( '' !== $block_id && igp_pro_get_registered_block( $block_id ) ) {
				$sections[] = array(
					'id'       => 'section_wp_' . ( count( $sections ) + 1 ) . '_' . sanitize_key( $block_id ),
					'block_id' => $block_id,
					'data'     => isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array(),
				);
			}
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			igp_pro_collect_igp_sections_from_blocks( $block['innerBlocks'], $sections );
		}
	}
}

/**
 * Determine whether a parsed block name belongs to IGP Pro.
 *
 * @param string $block_name Full WordPress block name.
 * @return bool
 */
function igp_pro_is_igp_wp_block_name( string $block_name ): bool {
	return 0 === strpos( $block_name, 'igp-pro/' );
}

/**
 * Convert a WordPress block name to a registry block ID.
 *
 * @param string $block_name Full block name, e.g. igp-pro/destination-cards.
 * @return string
 */
function igp_pro_wp_block_name_to_block_id( string $block_name ): string {
	if ( ! igp_pro_is_igp_wp_block_name( $block_name ) ) {
		return '';
	}

	$slug = sanitize_title( substr( $block_name, strlen( 'igp-pro/' ) ) );

	$aliases = array(
		'section-wrapper'     => 'section',
		'trust-social-proof'  => 'trust',
		'stats-highlights'    => 'stats',
		'pricing-summary'     => 'pricing_summary',
		'destination-cards'   => 'destination_cards',
		'tour-cards'          => 'tour_cards',
		'featured-listings'   => 'featured_listings',
		'icon-list'           => 'icon_list',
		'related-tours'       => 'related_tours',
		'related-destinations'=> 'related_destinations',
	);

	if ( isset( $aliases[ $slug ] ) ) {
		return $aliases[ $slug ];
	}

	return sanitize_key( str_replace( '-', '_', $slug ) );
}

/**
 * Sync a saved Content Graph back into Gutenberg post_content.
 *
 * This keeps the IGP Pro Content Editor linked with the page/CPT editor. Existing
 * non-IGP top-level blocks are preserved. Existing top-level IGP blocks are
 * replaced by the graph sequence at their first previous position. If the post
 * did not yet contain IGP blocks, the graph blocks are appended after existing
 * content.
 *
 * @param int   $post_id Post ID.
 * @param array $graph   Valid graph.
 * @return true|WP_Error
 */
function igp_pro_sync_content_graph_to_post_content( int $post_id, array $graph ) {
	if ( $post_id <= 0 ) {
		return new WP_Error( 'igp_pro_invalid_post_id', __( 'A valid post ID is required.', 'igp-pro' ) );
	}

	$validation = igp_pro_validate_content_graph( $graph );
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return new WP_Error( 'igp_pro_post_not_found', __( 'Post could not be found.', 'igp-pro' ) );
	}

	$current_blocks = parse_blocks( (string) $post->post_content );
	$graph_blocks   = igp_pro_content_graph_to_wp_blocks( $graph );
	$next_blocks    = array();
	$inserted       = false;

	foreach ( $current_blocks as $block ) {
		if ( is_array( $block ) && igp_pro_is_igp_wp_block_name( (string) ( $block['blockName'] ?? '' ) ) ) {
			if ( ! $inserted ) {
				foreach ( $graph_blocks as $graph_block ) {
					$next_blocks[] = $graph_block;
				}
				$inserted = true;
			}
			continue;
		}

		$next_blocks[] = $block;
	}

	if ( ! $inserted ) {
		foreach ( $graph_blocks as $graph_block ) {
			$next_blocks[] = $graph_block;
		}
	}

	$new_content = function_exists( 'serialize_blocks' ) ? serialize_blocks( $next_blocks ) : igp_pro_serialize_blocks_fallback( $next_blocks );

	if ( (string) $post->post_content === $new_content ) {
		return true;
	}

	$result = wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $new_content,
		),
		true
	);

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return true;
}

/**
 * Convert graph sections into serializable WordPress block arrays.
 *
 * @param array $graph Content graph.
 * @return array
 */
function igp_pro_content_graph_to_wp_blocks( array $graph ): array {
	$blocks = array();

	foreach ( $graph['sections'] as $section ) {
		if ( ! is_array( $section ) || empty( $section['block_id'] ) ) {
			continue;
		}

		$block_id = sanitize_key( (string) $section['block_id'] );
		if ( ! igp_pro_get_registered_block( $block_id ) ) {
			continue;
		}

		$attrs = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();

		$blocks[] = array(
			'blockName'    => 'igp-pro/' . igp_pro_block_id_to_wp_slug( $block_id ),
			'attrs'        => $attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);
	}

	return $blocks;
}

/**
 * Fallback serializer for environments without serialize_blocks().
 *
 * @param array $blocks WordPress block arrays.
 * @return string
 */
function igp_pro_serialize_blocks_fallback( array $blocks ): string {
	$output = '';

	foreach ( $blocks as $block ) {
		if ( function_exists( 'serialize_block' ) && is_array( $block ) ) {
			$output .= serialize_block( $block );
		} elseif ( is_array( $block ) && isset( $block['innerHTML'] ) ) {
			$output .= (string) $block['innerHTML'];
		}
	}

	return $output;
}
