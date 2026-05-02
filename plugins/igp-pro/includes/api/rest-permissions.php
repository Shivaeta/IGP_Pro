<?php
/**
 * REST permission foundations for IGP Pro V2.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build a structured REST permission error.
 *
 * @param string $capability Required capability.
 * @param string $message    Optional message.
 * @return WP_Error
 */
function igp_pro_rest_forbidden_error( string $capability, string $message = '' ): WP_Error {
	$capability = sanitize_key( $capability );
	$message    = '' !== $message ? $message : __( 'You do not have permission to perform this IGP Pro REST operation.', 'igp-pro' );

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => is_user_logged_in() ? 'human' : 'anonymous',
				'operation'     => 'rest_permission_denied',
				'object_type'   => 'rest_request',
				'object_id'     => 0,
				'source_module' => 'rest-permissions',
				'status'        => 'failure',
				'error_code'    => 'igp_pro_rest_forbidden',
				'summary'       => sprintf( 'REST permission denied. Required capability: %s', $capability ),
			)
		);
	}

	return new WP_Error(
		'igp_pro_rest_forbidden',
		$message,
		array(
			'status'              => rest_authorization_required_code(),
			'required_capability' => $capability,
		)
	);
}

/**
 * Check a REST request against a controlled IGP capability.
 *
 * @param string               $capability Required capability.
 * @param WP_REST_Request|null $request    Optional REST request.
 * @return true|WP_Error
 */
function igp_pro_rest_check_capability( string $capability, ?WP_REST_Request $request = null ) {
	$capability = function_exists( 'igp_pro_sanitize_capability' ) ? igp_pro_sanitize_capability( $capability ) : sanitize_key( $capability );

	if ( '' === $capability ) {
		return igp_pro_rest_forbidden_error( $capability, __( 'The REST route requires an unknown IGP capability.', 'igp-pro' ) );
	}

	if ( ! is_user_logged_in() ) {
		return igp_pro_rest_forbidden_error( $capability, __( 'Authentication is required for this IGP Pro REST operation.', 'igp-pro' ) );
	}

	if ( ! current_user_can( $capability ) ) {
		return igp_pro_rest_forbidden_error( $capability );
	}

	return true;
}

/**
 * Return a permission callback for register_rest_route().
 *
 * @param string $capability Required capability.
 * @return callable
 */
function igp_pro_rest_permission_callback( string $capability ): callable {
	return static function ( WP_REST_Request $request ) use ( $capability ) {
		return igp_pro_rest_check_capability( $capability, $request );
	};
}

/**
 * Read permission callback for Content Graph read surfaces.
 *
 * @param WP_REST_Request|null $request Optional request.
 * @return true|WP_Error
 */
function igp_pro_rest_can_edit_content_graph( ?WP_REST_Request $request = null ) {
	return igp_pro_rest_check_capability( 'igp_edit_content_graph', $request );
}

/**
 * Settings permission callback.
 *
 * @param WP_REST_Request|null $request Optional request.
 * @return true|WP_Error
 */
function igp_pro_rest_can_manage_settings( ?WP_REST_Request $request = null ) {
	return igp_pro_rest_check_capability( 'igp_manage_settings', $request );
}

/**
 * Recovery permission callback.
 *
 * @param WP_REST_Request|null $request Optional request.
 * @return true|WP_Error
 */
function igp_pro_rest_can_manage_recovery( ?WP_REST_Request $request = null ) {
	return igp_pro_rest_check_capability( 'igp_manage_recovery', $request );
}

/**
 * Integration permission callback.
 *
 * @param WP_REST_Request|null $request Optional request.
 * @return true|WP_Error
 */
function igp_pro_rest_can_manage_integrations( ?WP_REST_Request $request = null ) {
	return igp_pro_rest_check_capability( 'igp_manage_integrations', $request );
}
