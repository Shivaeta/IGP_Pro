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
