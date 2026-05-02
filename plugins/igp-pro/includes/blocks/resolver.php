<?php
/**
 * Block data resolver and schema validator.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve block schema data and apply defaults.
 *
 * @param array $block_definition Block registry definition.
 * @param array $data             Raw block data.
 * @param array $context          Render context.
 * @return array
 */
function igp_pro_resolve_block_data( array $block_definition, array $data = array(), array $context = array() ): array {
	$schema = igp_pro_get_block_schema( $block_definition );

	if ( is_wp_error( $schema ) || empty( $schema['fields'] ) || ! is_array( $schema['fields'] ) ) {
		return $data;
	}

	if ( function_exists( 'igp_pro_migrate_block_heading_data_for_render' ) ) {
		$data = igp_pro_migrate_block_heading_data_for_render( (string) ( $block_definition['id'] ?? '' ), $data );
	}

	if ( isset( $schema['defaults'] ) && is_array( $schema['defaults'] ) ) {
		$data = array_replace_recursive( $schema['defaults'], $data );
	}

	return igp_pro_apply_field_defaults( $schema['fields'], $data );
}

/**
 * Load a block schema from disk.
 *
 * @param array $block_definition Block registry definition.
 * @return array|WP_Error
 */
function igp_pro_get_block_schema( array $block_definition ) {
	$schema_path = isset( $block_definition['schema_path'] ) ? (string) $block_definition['schema_path'] : '';

	if ( '' === $schema_path || ! file_exists( $schema_path ) ) {
		return new WP_Error( 'igp_pro_schema_not_found', __( 'Block schema file could not be found.', 'igp-pro' ) );
	}

	$contents = file_get_contents( $schema_path );

	if ( false === $contents ) {
		return new WP_Error( 'igp_pro_schema_unreadable', __( 'Block schema file could not be read.', 'igp-pro' ) );
	}

	$schema = igp_pro_json_decode_array( $contents );

	if ( is_wp_error( $schema ) ) {
		return $schema;
	}

	return $schema;
}

/**
 * Apply schema defaults recursively.
 *
 * @param array $fields Field schema map.
 * @param array $data   Raw data.
 * @return array
 */
function igp_pro_apply_field_defaults( array $fields, array $data ): array {
	foreach ( $fields as $field_name => $field_schema ) {
		if ( ! is_array( $field_schema ) ) {
			continue;
		}

		$type = isset( $field_schema['type'] ) ? (string) $field_schema['type'] : '';

		if ( ! array_key_exists( $field_name, $data ) && array_key_exists( 'default', $field_schema ) ) {
			$data[ $field_name ] = $field_schema['default'];
		}

		if ( 'object' === $type && isset( $field_schema['fields'] ) && is_array( $field_schema['fields'] ) ) {
			$current = isset( $data[ $field_name ] ) && is_array( $data[ $field_name ] ) ? $data[ $field_name ] : array();
			$data[ $field_name ] = igp_pro_apply_field_defaults( $field_schema['fields'], $current );
		}

		if ( 'repeater' === $type && ! array_key_exists( $field_name, $data ) ) {
			$data[ $field_name ] = array();
		}
	}

	return $data;
}

/**
 * Validate resolved block data against the block schema.
 *
 * @param array $block_definition Block registry definition.
 * @param array $data             Resolved block data.
 * @return true|WP_Error
 */
function igp_pro_validate_block_data( array $block_definition, array $data ) {
	$schema = igp_pro_get_block_schema( $block_definition );

	if ( is_wp_error( $schema ) ) {
		return $schema;
	}

	$fields = isset( $schema['fields'] ) && is_array( $schema['fields'] ) ? $schema['fields'] : array();

	if ( isset( $schema['validation']['required'] ) && is_array( $schema['validation']['required'] ) ) {
		foreach ( $schema['validation']['required'] as $field_name ) {
			if ( ! igp_pro_block_field_has_value( $data[ $field_name ] ?? null ) ) {
				return new WP_Error(
					'igp_pro_block_missing_required_field',
					sprintf(
						/* translators: %s: field name. */
						__( 'Required block field missing: %s', 'igp-pro' ),
						sanitize_key( (string) $field_name )
					)
				);
			}
		}
	}

	$field_validation = igp_pro_validate_block_fields( $fields, $data );
	if ( is_wp_error( $field_validation ) ) {
		return $field_validation;
	}

	if ( function_exists( 'igp_pro_validate_block_heading_data' ) ) {
		$heading_validation = igp_pro_validate_block_heading_data( (string) ( $block_definition['id'] ?? '' ), $data, $schema );
		if ( is_wp_error( $heading_validation ) ) {
			return $heading_validation;
		}
	}

	if ( function_exists( 'igp_pro_validate_block_style_data' ) ) {
		$style_validation = igp_pro_validate_block_style_data( (string) ( $block_definition['id'] ?? '' ), $data, $schema );
		if ( is_wp_error( $style_validation ) ) {
			return $style_validation;
		}
	}

	return true;
}

/**
 * Validate a field map recursively.
 *
 * @param array  $fields Field schema map.
 * @param array  $data   Data map.
 * @param string $prefix Field path prefix.
 * @return true|WP_Error
 */
function igp_pro_validate_block_fields( array $fields, array $data, string $prefix = '' ) {
	foreach ( $fields as $field_name => $field_schema ) {
		if ( ! is_array( $field_schema ) ) {
			continue;
		}

		$type       = isset( $field_schema['type'] ) ? (string) $field_schema['type'] : 'string';
		$field_path = '' === $prefix ? (string) $field_name : $prefix . '.' . $field_name;
		$value      = $data[ $field_name ] ?? null;

		if ( ! empty( $field_schema['required'] ) && ! igp_pro_block_field_has_value( $value ) ) {
			return new WP_Error(
				'igp_pro_block_missing_required_field',
				sprintf(
					/* translators: %s: field path. */
					__( 'Required block field missing: %s', 'igp-pro' ),
					sanitize_text_field( $field_path )
				)
			);
		}

		if ( ! igp_pro_block_field_has_value( $value ) ) {
			continue;
		}

		if ( 'image' === $type && '' === igp_pro_get_image_url( $value ) ) {
			return new WP_Error( 'igp_pro_block_invalid_image', sprintf( __( 'Image field requires a URL: %s', 'igp-pro' ), sanitize_text_field( $field_path ) ) );
		}

		if ( 'enum' === $type && isset( $field_schema['values'] ) && is_array( $field_schema['values'] ) ) {
			if ( ! in_array( igp_pro_to_string( $value ), $field_schema['values'], true ) ) {
				return new WP_Error( 'igp_pro_block_invalid_enum', sprintf( __( 'Invalid enum value for field: %s', 'igp-pro' ), sanitize_text_field( $field_path ) ) );
			}
		}

		if ( 'number' === $type && is_numeric( $value ) ) {
			if ( isset( $field_schema['min'] ) && $value < $field_schema['min'] ) {
				return new WP_Error( 'igp_pro_block_number_too_small', sprintf( __( 'Number is below minimum for field: %s', 'igp-pro' ), sanitize_text_field( $field_path ) ) );
			}
			if ( isset( $field_schema['max'] ) && $value > $field_schema['max'] ) {
				return new WP_Error( 'igp_pro_block_number_too_large', sprintf( __( 'Number is above maximum for field: %s', 'igp-pro' ), sanitize_text_field( $field_path ) ) );
			}
		}

		if ( 'object' === $type && isset( $field_schema['fields'] ) && is_array( $field_schema['fields'] ) ) {
			if ( ! is_array( $value ) ) {
				return new WP_Error( 'igp_pro_block_invalid_object', sprintf( __( 'Object field must be an object: %s', 'igp-pro' ), sanitize_text_field( $field_path ) ) );
			}

			$child_validation = igp_pro_validate_block_fields( $field_schema['fields'], $value, $field_path );
			if ( is_wp_error( $child_validation ) ) {
				return $child_validation;
			}
		}

		if ( 'repeater' === $type ) {
			$items = igp_pro_normalize_list( $value );
			$min   = isset( $field_schema['min_items'] ) ? absint( $field_schema['min_items'] ) : 0;

			if ( $min > 0 && count( $items ) < $min ) {
				return new WP_Error( 'igp_pro_block_repeater_too_short', sprintf( __( 'Repeater field has too few items: %s', 'igp-pro' ), sanitize_text_field( $field_path ) ) );
			}
		}
	}

	return true;
}

/**
 * Determine whether a field has a non-empty value.
 *
 * @param mixed $value Field value.
 * @return bool
 */
function igp_pro_block_field_has_value( $value ): bool {
	if ( is_array( $value ) ) {
		if ( isset( $value['url'] ) ) {
			return '' !== trim( igp_pro_to_string( $value['url'] ) );
		}

		return ! empty( $value );
	}

	return null !== $value && '' !== trim( igp_pro_to_string( $value ) );
}
