<?php
/**
 * Content Graph importer.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalize and validate an imported JSON payload.
 *
 * Accepts either a raw Content Graph or an exported wrapper from igp_pro_export_content_graph().
 * Export wrappers must contain type=igp_pro_content_graph and version=v1.
 *
 * @param array|string $payload JSON string or decoded array.
 * @return array|WP_Error
 */
function igp_pro_import_content_graph_payload( $payload ) {
	if ( is_string( $payload ) ) {
		$payload = igp_pro_json_decode_array( $payload );
	}

	if ( is_wp_error( $payload ) ) {
		return $payload;
	}

	if ( ! is_array( $payload ) ) {
		return new WP_Error( 'igp_pro_import_invalid_payload', __( 'Import payload must be a JSON object.', 'igp-pro' ) );
	}

	$wrapper_validation = function_exists( 'igp_pro_validate_import_wrapper_payload' ) ? igp_pro_validate_import_wrapper_payload( $payload ) : true;
	if ( is_wp_error( $wrapper_validation ) ) {
		return $wrapper_validation;
	}

	if ( isset( $payload['graph'] ) && is_array( $payload['graph'] ) ) {
		$graph = $payload['graph'];
	} elseif ( isset( $payload['content_graph'] ) && is_array( $payload['content_graph'] ) ) {
		$graph = $payload['content_graph'];
	} else {
		$graph = $payload;
	}

	$validation = igp_pro_validate_content_graph( $graph );
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$graph = function_exists( 'igp_pro_sanitize_content_graph_payload' ) ? igp_pro_sanitize_content_graph_payload( $graph ) : $graph;

	return $graph;
}


/**
 * Extract sanitized relationship data from an import wrapper, if present.
 *
 * @param array|string $payload JSON string or decoded array.
 * @return array|null|WP_Error Null means no relationships were supplied.
 */
function igp_pro_import_relationship_payload( $payload ) {
	if ( is_string( $payload ) ) {
		$payload = igp_pro_json_decode_array( $payload );
	}

	if ( is_wp_error( $payload ) ) {
		return $payload;
	}

	if ( ! is_array( $payload ) || ! isset( $payload['relationships'] ) ) {
		return null;
	}

	if ( ! function_exists( 'igp_pro_validate_relationship_payload' ) ) {
		return new WP_Error( 'igp_pro_relationship_import_unavailable', __( 'Relationship service is not available. Enable the relationship layer before importing relationship data.', 'igp-pro' ) );
	}

	return igp_pro_validate_relationship_payload( $payload['relationships'] );
}

/**
 * Import a Content Graph wrapper and optionally apply relationship data to a post.
 *
 * @param int          $post_id Post ID.
 * @param array|string $payload JSON string or decoded array.
 * @return array|WP_Error Imported graph.
 */
function igp_pro_import_content_graph_to_post( int $post_id, $payload ) {
	$graph = igp_pro_import_content_graph_payload( $payload );
	if ( is_wp_error( $graph ) ) {
		return $graph;
	}

	$save = igp_pro_save_content_graph( $post_id, $graph );
	if ( is_wp_error( $save ) ) {
		return $save;
	}

	$relationships = igp_pro_import_relationship_payload( $payload );
	if ( is_wp_error( $relationships ) ) {
		return $relationships;
	}

	if ( is_array( $relationships ) && function_exists( 'igp_pro_save_relationships' ) ) {
		$relationship_save = igp_pro_save_relationships(
			$post_id,
			$relationships,
			array(
				'actor_type'    => 'import',
				'source_module' => 'content-importer',
				'reason'        => 'content_graph_import',
			)
		);
		if ( is_wp_error( $relationship_save ) ) {
			return $relationship_save;
		}
	}

	return $graph;
}
