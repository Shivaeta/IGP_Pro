<?php
/**
 * Content Graph storage and validation.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

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
 * Validate the content graph backbone.
 *
 * @param array $graph Content graph.
 * @return true|WP_Error
 */
function igp_pro_validate_content_graph( array $graph ) {
	if ( empty( $graph['version'] ) || ! is_string( $graph['version'] ) ) {
		return new WP_Error( 'igp_pro_graph_missing_version', __( 'Content graph version is required.', 'igp-pro' ) );
	}

	if ( ! array_key_exists( 'sections', $graph ) || ! is_array( $graph['sections'] ) ) {
		return new WP_Error( 'igp_pro_graph_missing_sections', __( 'Content graph sections must be an array.', 'igp-pro' ) );
	}

	foreach ( $graph['sections'] as $index => $section ) {
		if ( ! is_array( $section ) ) {
			return new WP_Error(
				'igp_pro_graph_invalid_section',
				sprintf(
					/* translators: %d: section index. */
					__( 'Content graph section %d must be an object.', 'igp-pro' ),
					(int) $index
				)
			);
		}

		if ( empty( $section['block_id'] ) || ! is_string( $section['block_id'] ) ) {
			return new WP_Error(
				'igp_pro_graph_missing_block_id',
				sprintf(
					/* translators: %d: section index. */
					__( 'Content graph section %d requires a block_id.', 'igp-pro' ),
					(int) $index
				)
			);
		}

		if ( array_key_exists( 'data', $section ) && ! is_array( $section['data'] ) ) {
			return new WP_Error(
				'igp_pro_graph_invalid_section_data',
				sprintf(
					/* translators: %d: section index. */
					__( 'Content graph section %d data must be an object.', 'igp-pro' ),
					(int) $index
				)
			);
		}
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
