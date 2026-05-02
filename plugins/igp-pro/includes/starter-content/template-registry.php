<?php
/**
 * Starter template registry for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the starter template directory.
 *
 * @return string
 */
function igp_pro_get_starter_template_base_dir(): string {
	return trailingslashit( IGP_PRO_PATH . 'includes/starter-content/templates' );
}

/**
 * Return required initial template IDs.
 *
 * @return string[]
 */
function igp_pro_get_required_starter_template_ids(): array {
	return array(
		'luxury-tours',
		'budget-tours',
		'pilgrimage-travel',
		'international-inbound',
	);
}

/**
 * Read and validate a starter template manifest.
 *
 * @param string $template_id Template ID.
 * @return array|WP_Error
 */
function igp_pro_get_starter_template_manifest( string $template_id ) {
	$template_id = sanitize_key( $template_id );
	if ( '' === $template_id ) {
		return new WP_Error( 'igp_pro_template_invalid_id', __( 'Starter template ID is invalid.', 'igp-pro' ) );
	}

	$dir = igp_pro_get_starter_template_base_dir() . $template_id;
	$file = trailingslashit( $dir ) . 'manifest.json';
	$manifest = function_exists( 'igp_pro_read_starter_template_json_file' ) ? igp_pro_read_starter_template_json_file( $file ) : new WP_Error( 'igp_pro_template_validator_missing', __( 'Starter template validator is not loaded.', 'igp-pro' ) );
	if ( is_wp_error( $manifest ) ) {
		return $manifest;
	}

	$validation = function_exists( 'igp_pro_validate_starter_template_manifest' ) ? igp_pro_validate_starter_template_manifest( $manifest, $dir ) : true;
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$manifest['template_id'] = sanitize_key( (string) $manifest['template_id'] );
	$manifest['_template_dir'] = $dir;
	$manifest['_manifest_file'] = $file;

	return $manifest;
}

/**
 * Discover starter templates.
 *
 * @param bool $include_invalid Whether invalid templates should be included with error metadata.
 * @return array<string,array<string,mixed>>
 */
function igp_pro_discover_starter_templates( bool $include_invalid = false ): array {
	$base_dir = igp_pro_get_starter_template_base_dir();
	$templates = array();

	if ( ! is_dir( $base_dir ) ) {
		return $templates;
	}

	$dirs = glob( $base_dir . '*', GLOB_ONLYDIR );
	if ( ! is_array( $dirs ) ) {
		$dirs = array();
	}

	foreach ( $dirs as $dir ) {
		$template_id = sanitize_key( basename( $dir ) );
		if ( '' === $template_id ) {
			continue;
		}

		$manifest = igp_pro_get_starter_template_manifest( $template_id );
		if ( is_wp_error( $manifest ) ) {
			if ( $include_invalid ) {
				$templates[ $template_id ] = array(
					'template_id' => $template_id,
					'name'        => ucwords( str_replace( '-', ' ', $template_id ) ),
					'valid'       => false,
					'error_code'  => $manifest->get_error_code(),
					'error'       => $manifest->get_error_message(),
				);
			}
			continue;
		}

		$templates[ $template_id ] = array_merge(
			$manifest,
			array(
				'valid' => true,
			)
		);
	}

	ksort( $templates );
	return $templates;
}

/**
 * Return one valid starter template package.
 *
 * @param string $template_id Template ID.
 * @return array|WP_Error
 */
function igp_pro_get_starter_template( string $template_id ) {
	$manifest = igp_pro_get_starter_template_manifest( $template_id );
	if ( is_wp_error( $manifest ) ) {
		return $manifest;
	}

	return $manifest;
}

/**
 * Load a template-relative JSON file, constrained to the template directory.
 *
 * @param string $template_id Template ID.
 * @param string $relative_file Relative JSON file path.
 * @return array|WP_Error
 */
function igp_pro_load_starter_template_json( string $template_id, string $relative_file ) {
	$template = igp_pro_get_starter_template( $template_id );
	if ( is_wp_error( $template ) ) {
		return $template;
	}

	$dir = trailingslashit( (string) $template['_template_dir'] );
	$relative_file = ltrim( str_replace( '\\', '/', $relative_file ), '/' );
	$file = realpath( $dir . $relative_file );
	$real_dir = realpath( $dir );

	if ( false === $file || false === $real_dir || 0 !== strpos( $file, $real_dir ) ) {
		return new WP_Error( 'igp_pro_template_file_outside_package', __( 'Starter template file path is invalid.', 'igp-pro' ) );
	}

	return igp_pro_read_starter_template_json_file( $file );
}

/**
 * Return registry summary for admin/REST-like use.
 *
 * @return array<int,array<string,mixed>>
 */
function igp_pro_get_starter_template_registry_summary(): array {
	$templates = igp_pro_discover_starter_templates( true );
	$summary = array();

	foreach ( $templates as $template ) {
		$summary[] = array(
			'template_id'       => $template['template_id'] ?? '',
			'name'              => $template['name'] ?? '',
			'industry'          => $template['industry'] ?? '',
			'version'           => $template['version'] ?? '',
			'valid'             => ! empty( $template['valid'] ),
			'error'             => $template['error'] ?? '',
			'required_blocks'   => $template['required_blocks'] ?? array(),
			'required_features' => $template['required_features'] ?? array(),
			'brand_profile'     => $template['brand_profile'] ?? '',
			'counts'            => array(
				'pages'              => is_array( $template['pages'] ?? null ) ? count( $template['pages'] ) : 0,
				'tours'              => is_array( $template['tours'] ?? null ) ? count( $template['tours'] ) : 0,
				'destinations'       => is_array( $template['destinations'] ?? null ) ? count( $template['destinations'] ) : 0,
				'media_placeholders' => is_array( $template['media_placeholders'] ?? null ) ? count( $template['media_placeholders'] ) : 0,
			),
		);
	}

	return $summary;
}
