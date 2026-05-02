<?php
/**
 * Starter template validation service for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return starter template manifest fields required by V2.
 *
 * @return string[]
 */
function igp_pro_get_required_template_manifest_fields(): array {
	return array(
		'template_id',
		'version',
		'name',
		'industry',
		'required_blocks',
		'required_features',
		'brand_profile',
		'pages',
		'tours',
		'destinations',
		'media_placeholders',
		'seo_profile',
		'link_map',
	);
}

/**
 * Decode a JSON file as an associative array.
 *
 * @param string $file Absolute file path.
 * @return array|WP_Error
 */
function igp_pro_read_starter_template_json_file( string $file ) {
	if ( '' === $file || ! file_exists( $file ) || ! is_readable( $file ) ) {
		return new WP_Error( 'igp_pro_template_file_missing', __( 'Starter template file is missing or unreadable.', 'igp-pro' ), array( 'file' => $file ) );
	}

	$raw = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( false === $raw ) {
		return new WP_Error( 'igp_pro_template_file_unreadable', __( 'Starter template file could not be read.', 'igp-pro' ), array( 'file' => $file ) );
	}

	$data = json_decode( $raw, true );
	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
		return new WP_Error(
			'igp_pro_template_invalid_json',
			sprintf(
				/* translators: %s: file name. */
				__( 'Starter template JSON is invalid: %s', 'igp-pro' ),
				basename( $file )
			),
			array(
				'file'       => $file,
				'json_error' => json_last_error_msg(),
			)
		);
	}

	return $data;
}

/**
 * Validate a starter template manifest.
 *
 * @param array  $manifest     Decoded manifest.
 * @param string $template_dir Absolute template directory.
 * @return true|WP_Error
 */
function igp_pro_validate_starter_template_manifest( array $manifest, string $template_dir = '' ) {
	foreach ( igp_pro_get_required_template_manifest_fields() as $field ) {
		if ( ! array_key_exists( $field, $manifest ) ) {
			return new WP_Error(
				'igp_pro_template_manifest_missing_field',
				sprintf(
					/* translators: %s: manifest field. */
					__( 'Starter template manifest is missing required field: %s', 'igp-pro' ),
					$field
				),
				array( 'field' => $field )
			);
		}
	}

	$template_id = sanitize_key( (string) ( $manifest['template_id'] ?? '' ) );
	if ( '' === $template_id || $template_id !== (string) $manifest['template_id'] ) {
		return new WP_Error( 'igp_pro_template_manifest_invalid_id', __( 'Starter template manifest has an invalid template_id.', 'igp-pro' ) );
	}

	foreach ( array( 'version', 'name', 'industry' ) as $string_field ) {
		if ( ! is_string( $manifest[ $string_field ] ) || '' === trim( $manifest[ $string_field ] ) ) {
			return new WP_Error(
				'igp_pro_template_manifest_invalid_string',
				sprintf(
					/* translators: %s: manifest field. */
					__( 'Starter template manifest field must be a non-empty string: %s', 'igp-pro' ),
					$string_field
				),
				array( 'field' => $string_field )
			);
		}
	}

	foreach ( array( 'required_blocks', 'required_features', 'pages', 'tours', 'destinations', 'media_placeholders' ) as $array_field ) {
		if ( ! is_array( $manifest[ $array_field ] ) ) {
			return new WP_Error(
				'igp_pro_template_manifest_invalid_array',
				sprintf(
					/* translators: %s: manifest field. */
					__( 'Starter template manifest field must be an array: %s', 'igp-pro' ),
					$array_field
				),
				array( 'field' => $array_field )
			);
		}
	}

	foreach ( array( 'seo_profile', 'link_map' ) as $object_field ) {
		if ( ! is_array( $manifest[ $object_field ] ) ) {
			return new WP_Error(
				'igp_pro_template_manifest_invalid_object',
				sprintf(
					/* translators: %s: manifest field. */
					__( 'Starter template manifest field must be an object: %s', 'igp-pro' ),
					$object_field
				),
				array( 'field' => $object_field )
			);
		}
	}

	if ( ! empty( $manifest['brand_profile'] ) && ! is_string( $manifest['brand_profile'] ) && ! is_array( $manifest['brand_profile'] ) ) {
		return new WP_Error( 'igp_pro_template_manifest_invalid_brand_profile', __( 'Starter template brand_profile must be a string, object, or explicitly empty.', 'igp-pro' ) );
	}

	if ( is_array( $manifest['brand_profile'] ) && isset( $manifest['brand_profile']['tokens'] ) && function_exists( 'igp_pro_validate_design_tokens' ) ) {
		$token_validation = igp_pro_validate_design_tokens( $manifest['brand_profile']['tokens'] );
		if ( is_wp_error( $token_validation ) ) {
			return $token_validation;
		}
	}

	$missing_blocks = igp_pro_get_missing_starter_template_blocks( $manifest );
	if ( ! empty( $missing_blocks ) ) {
		return new WP_Error(
			'igp_pro_template_manifest_missing_blocks',
			__( 'Starter template manifest references missing block IDs.', 'igp-pro' ),
			array( 'missing_blocks' => $missing_blocks )
		);
	}

	$unknown_features = igp_pro_get_unknown_starter_template_features( $manifest );
	if ( ! empty( $unknown_features ) ) {
		return new WP_Error(
			'igp_pro_template_manifest_unknown_features',
			__( 'Starter template manifest references unknown feature flags.', 'igp-pro' ),
			array( 'unknown_features' => $unknown_features )
		);
	}

	$file_validation = igp_pro_validate_starter_template_manifest_file_references( $manifest, $template_dir );
	if ( is_wp_error( $file_validation ) ) {
		return $file_validation;
	}

	return true;
}

/**
 * Validate graph file references declared in manifest objects.
 *
 * @param array  $manifest     Manifest.
 * @param string $template_dir Template directory.
 * @return true|WP_Error
 */
function igp_pro_validate_starter_template_manifest_file_references( array $manifest, string $template_dir = '' ) {
	if ( '' === $template_dir ) {
		return true;
	}

	foreach ( array( 'pages', 'tours', 'destinations' ) as $object_type ) {
		foreach ( $manifest[ $object_type ] ?? array() as $index => $object ) {
			if ( ! is_array( $object ) ) {
				return new WP_Error( 'igp_pro_template_manifest_invalid_object_entry', __( 'Starter template object definitions must be objects.', 'igp-pro' ), array( 'object_type' => $object_type, 'index' => $index ) );
			}

			foreach ( array( 'template_uuid', 'title', 'slug' ) as $required_field ) {
				if ( empty( $object[ $required_field ] ) || ! is_string( $object[ $required_field ] ) ) {
					return new WP_Error( 'igp_pro_template_manifest_invalid_object_definition', __( 'Starter template object definition is missing a required field.', 'igp-pro' ), array( 'object_type' => $object_type, 'index' => $index, 'field' => $required_field ) );
				}
			}

			$graph_file = isset( $object['content_graph'] ) ? sanitize_text_field( (string) $object['content_graph'] ) : '';
			if ( '' === $graph_file ) {
				continue;
			}

			$real_template_dir = realpath( $template_dir );
			$real_graph_file   = realpath( $template_dir . '/' . ltrim( $graph_file, '/\\' ) );
			if ( false === $real_template_dir || false === $real_graph_file || 0 !== strpos( $real_graph_file, $real_template_dir ) || ! is_readable( $real_graph_file ) ) {
				return new WP_Error(
					'igp_pro_template_manifest_missing_graph',
					__( 'Starter template manifest references a missing content graph file.', 'igp-pro' ),
					array( 'object_type' => $object_type, 'graph_file' => $graph_file )
				);
			}
		}
	}

	return true;
}

/**
 * Return missing blocks declared by a template manifest.
 *
 * @param array $manifest Manifest.
 * @return string[]
 */
function igp_pro_get_missing_starter_template_blocks( array $manifest ): array {
	$required = isset( $manifest['required_blocks'] ) && is_array( $manifest['required_blocks'] ) ? $manifest['required_blocks'] : array();
	$missing  = array();

	foreach ( $required as $block_id ) {
		$block_id = sanitize_key( (string) $block_id );
		if ( '' === $block_id ) {
			continue;
		}
		if ( ! function_exists( 'igp_pro_get_registered_block' ) || ! igp_pro_get_registered_block( $block_id ) ) {
			$missing[] = $block_id;
		}
	}

	return array_values( array_unique( $missing ) );
}

/**
 * Return unknown feature flags declared by a manifest.
 *
 * @param array $manifest Manifest.
 * @return string[]
 */
function igp_pro_get_unknown_starter_template_features( array $manifest ): array {
	$required    = isset( $manifest['required_features'] ) && is_array( $manifest['required_features'] ) ? $manifest['required_features'] : array();
	$definitions = function_exists( 'igp_pro_get_feature_flag_definitions' ) ? igp_pro_get_feature_flag_definitions() : array();
	$unknown     = array();

	foreach ( $required as $feature ) {
		$feature = sanitize_key( (string) $feature );
		if ( '' !== $feature && ! isset( $definitions[ $feature ] ) ) {
			$unknown[] = $feature;
		}
	}

	return array_values( array_unique( $unknown ) );
}

/**
 * Return disabled required feature flags declared by a manifest.
 *
 * @param array $manifest Manifest.
 * @return string[]
 */
function igp_pro_get_disabled_starter_template_features( array $manifest ): array {
	$required = isset( $manifest['required_features'] ) && is_array( $manifest['required_features'] ) ? $manifest['required_features'] : array();
	$disabled = array();

	foreach ( $required as $feature ) {
		$feature = sanitize_key( (string) $feature );
		if ( '' === $feature ) {
			continue;
		}
		if ( function_exists( 'igp_feature_enabled' ) && ! igp_feature_enabled( $feature ) ) {
			$disabled[] = $feature;
		}
	}

	return array_values( array_unique( $disabled ) );
}

/**
 * Validate a content graph file for preview/dry run.
 *
 * @param string $file Content graph file path.
 * @return array|WP_Error
 */
function igp_pro_validate_starter_template_content_graph_file( string $file ) {
	$graph = igp_pro_read_starter_template_json_file( $file );
	if ( is_wp_error( $graph ) ) {
		return $graph;
	}

	if ( function_exists( 'igp_pro_migrate_content_graph_for_render' ) ) {
		$migrated = igp_pro_migrate_content_graph_for_render( $graph );
		if ( is_wp_error( $migrated ) ) {
			return $migrated;
		}
		$graph = $migrated;
	}

	if ( function_exists( 'igp_pro_validate_content_graph_payload' ) ) {
		$validation = igp_pro_validate_content_graph_payload( $graph );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
	}

	return $graph;
}
