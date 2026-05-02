<?php
/**
 * Content Graph validation.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Validate a Content Graph payload and registered block data.
 *
 * @param array $graph Graph payload.
 * @return true|WP_Error
 */
function igp_pro_validate_content_graph_payload( array $graph ) {
	if ( empty( $graph['version'] ) || ! is_string( $graph['version'] ) ) {
		return new WP_Error( 'igp_pro_graph_missing_version', __( 'Content graph version is required.', 'igp-pro' ) );
	}

	if ( 'v1' !== $graph['version'] ) {
		return new WP_Error( 'igp_pro_graph_unsupported_version', __( 'Only Content Graph version v1 is supported in this phase.', 'igp-pro' ) );
	}

	if ( ! array_key_exists( 'sections', $graph ) || ! is_array( $graph['sections'] ) ) {
		return new WP_Error( 'igp_pro_graph_missing_sections', __( 'Content graph sections must be an array.', 'igp-pro' ) );
	}

	foreach ( $graph['sections'] as $index => $section ) {
		if ( ! is_array( $section ) ) {
			return new WP_Error( 'igp_pro_graph_invalid_section', sprintf( __( 'Content graph section %d must be an object.', 'igp-pro' ), (int) $index ) );
		}

		if ( empty( $section['block_id'] ) || ! is_string( $section['block_id'] ) ) {
			return new WP_Error( 'igp_pro_graph_missing_block_id', sprintf( __( 'Content graph section %d requires a block_id.', 'igp-pro' ), (int) $index ) );
		}

		$block_id = sanitize_key( (string) $section['block_id'] );
		$block    = igp_pro_get_registered_block( $block_id );

		if ( ! $block ) {
			return new WP_Error( 'igp_pro_graph_unknown_block', sprintf( __( 'Content graph section %1$d references unknown block %2$s.', 'igp-pro' ), (int) $index, $block_id ) );
		}

		if ( array_key_exists( 'data', $section ) && ! is_array( $section['data'] ) ) {
			return new WP_Error( 'igp_pro_graph_invalid_section_data', sprintf( __( 'Content graph section %d data must be an object.', 'igp-pro' ), (int) $index ) );
		}

		$data   = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$schema = igp_pro_get_block_schema( $block );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		if ( function_exists( 'igp_pro_migrate_block_heading_data_for_render' ) ) {
			$data = igp_pro_migrate_block_heading_data_for_render( $block_id, $data );
		}

		$field_validation = igp_pro_validate_schema_data( $schema, $data, 'sections.' . $index . '.data', false );
		if ( is_wp_error( $field_validation ) ) {
			return $field_validation;
		}

		// Validate the same data after schema defaults are applied so required
		// fields with explicit defaults remain renderable, without silently
		// correcting invalid user-supplied values.
		$resolved_data    = igp_pro_apply_schema_defaults_for_validation( $schema, $data );
		$block_validation = igp_pro_validate_block_data( $block, $resolved_data );
		if ( is_wp_error( $block_validation ) ) {
			return $block_validation;
		}
	}

	if ( function_exists( 'igp_pro_semantic_outline_enabled' ) && igp_pro_semantic_outline_enabled() && function_exists( 'igp_pro_validate_heading_hierarchy' ) ) {
		$heading_validation = igp_pro_validate_heading_hierarchy( $graph );
		if ( is_wp_error( $heading_validation ) ) {
			return $heading_validation;
		}
	}

	return true;
}

/**
 * Validate an imported wrapper before graph extraction.
 *
 * @param array $payload Import payload.
 * @return true|WP_Error
 */
function igp_pro_validate_import_wrapper_payload( array $payload ) {
	if ( isset( $payload['graph'] ) ) {
		if ( ! isset( $payload['type'] ) || 'igp_pro_content_graph' !== (string) $payload['type'] ) {
			return new WP_Error( 'igp_pro_import_invalid_type', __( 'Export wrapper type must be igp_pro_content_graph.', 'igp-pro' ) );
		}
		if ( ! isset( $payload['version'] ) || 'v1' !== (string) $payload['version'] ) {
			return new WP_Error( 'igp_pro_import_invalid_version', __( 'Export wrapper version must be v1.', 'igp-pro' ) );
		}
		if ( ! is_array( $payload['graph'] ) ) {
			return new WP_Error( 'igp_pro_import_invalid_graph', __( 'Export wrapper graph must be an object.', 'igp-pro' ) );
		}
	}

	return true;
}

/**
 * Validate a data object against a schema fields object.
 *
 * @param array  $schema Schema.
 * @param array  $data   Data.
 * @param string $path   Error path.
 * @param bool   $allow_string_relationships Whether strings are accepted for relationship fields.
 * @return true|WP_Error
 */
function igp_pro_validate_schema_data( array $schema, array $data, string $path = 'data', bool $allow_string_relationships = false ) {
	$fields   = isset( $schema['fields'] ) && is_array( $schema['fields'] ) ? $schema['fields'] : array();
	$required = array();

	if ( isset( $schema['validation']['required'] ) && is_array( $schema['validation']['required'] ) ) {
		$required = array_map( 'strval', $schema['validation']['required'] );
	}

	$allowed_wp_attrs = array( 'anchor', 'className', 'style', 'align', 'lock', 'metadata' );
	foreach ( $data as $supplied_field => $unused ) {
		if ( ! array_key_exists( $supplied_field, $fields ) && ! in_array( (string) $supplied_field, $allowed_wp_attrs, true ) ) {
			return new WP_Error( 'igp_pro_unknown_field', sprintf( __( 'Unknown field supplied: %s.', 'igp-pro' ), $path . '.' . $supplied_field ) );
		}
	}

	foreach ( $fields as $field_name => $field_schema ) {
		if ( ! is_array( $field_schema ) ) {
			continue;
		}

		$field_path  = $path . '.' . $field_name;
		$is_required = ! empty( $field_schema['required'] ) || in_array( (string) $field_name, $required, true );
		$value       = array_key_exists( $field_name, $data ) ? $data[ $field_name ] : null;
		$has_default = array_key_exists( $field_name, $schema['defaults'] ?? array() ) || array_key_exists( 'default', $field_schema );

		if ( $is_required && ! $has_default && igp_pro_is_empty_schema_value( $value, $field_schema ) ) {
			return new WP_Error( 'igp_pro_required_field_missing', sprintf( __( 'Required field missing: %s.', 'igp-pro' ), $field_path ) );
		}

		if ( null === $value || '' === $value ) {
			continue;
		}

		$field_result = igp_pro_validate_schema_field( (string) $field_name, $field_schema, $value, $field_path, $allow_string_relationships );
		if ( is_wp_error( $field_result ) ) {
			return $field_result;
		}
	}

	if ( isset( $schema['validation']['manual_min_items'] ) && 'manual' === ( $data['source'] ?? '' ) ) {
		$items = igp_pro_normalize_post_ids( $data['items'] ?? array() );
		$min   = max( 1, absint( $schema['validation']['manual_min_items'] ) );
		if ( count( $items ) < $min ) {
			return new WP_Error( 'igp_pro_manual_items_missing', __( 'Manual listing blocks require at least one selected item.', 'igp-pro' ) );
		}
	}

	return true;
}

/**
 * Validate one field.
 *
 * @param string $field_name Field name.
 * @param array  $field_schema Field schema.
 * @param mixed  $value Value.
 * @param string $path Error path.
 * @param bool   $allow_string_relationships Whether strings are accepted for relationship fields.
 * @return true|WP_Error
 */
function igp_pro_validate_schema_field( string $field_name, array $field_schema, $value, string $path, bool $allow_string_relationships = false ) {
	$type = isset( $field_schema['type'] ) ? (string) $field_schema['type'] : 'string';

	switch ( $type ) {
		case 'boolean':
			return is_bool( $value ) ? true : new WP_Error( 'igp_pro_invalid_boolean', sprintf( __( '%s must be true or false.', 'igp-pro' ), $path ) );

		case 'number':
			if ( ! is_int( $value ) && ! is_float( $value ) ) {
				return new WP_Error( 'igp_pro_invalid_number', sprintf( __( '%s must be numeric.', 'igp-pro' ), $path ) );
			}
			if ( isset( $field_schema['min'] ) && is_numeric( $field_schema['min'] ) && $value < $field_schema['min'] ) {
				return new WP_Error( 'igp_pro_number_too_low', sprintf( __( '%s is below the minimum.', 'igp-pro' ), $path ) );
			}
			if ( isset( $field_schema['max'] ) && is_numeric( $field_schema['max'] ) && $value > $field_schema['max'] ) {
				return new WP_Error( 'igp_pro_number_too_high', sprintf( __( '%s exceeds the maximum.', 'igp-pro' ), $path ) );
			}
			return true;

		case 'enum':
			$allowed = isset( $field_schema['values'] ) && is_array( $field_schema['values'] ) ? array_map( 'strval', $field_schema['values'] ) : array();
			return is_string( $value ) && in_array( $value, $allowed, true ) ? true : new WP_Error( 'igp_pro_invalid_enum', sprintf( __( '%s has an invalid value.', 'igp-pro' ), $path ) );

		case 'image':
			if ( ! is_array( $value ) ) {
				return new WP_Error( 'igp_pro_invalid_image', sprintf( __( '%s must be an image object.', 'igp-pro' ), $path ) );
			}
			if ( isset( $value['url'] ) && ! is_string( $value['url'] ) ) {
				return new WP_Error( 'igp_pro_invalid_image_url', sprintf( __( '%s.url must be a string.', 'igp-pro' ), $path ) );
			}
			if ( isset( $value['alt'] ) && ! is_string( $value['alt'] ) ) {
				return new WP_Error( 'igp_pro_invalid_image_alt', sprintf( __( '%s.alt must be a string.', 'igp-pro' ), $path ) );
			}
			return true;

		case 'object':
			if ( ! is_array( $value ) ) {
				return new WP_Error( 'igp_pro_invalid_object', sprintf( __( '%s must be an object.', 'igp-pro' ), $path ) );
			}
			return igp_pro_validate_schema_data( array( 'fields' => $field_schema['fields'] ?? array(), 'defaults' => $field_schema['defaults'] ?? array() ), $value, $path, $allow_string_relationships );

		case 'repeater':
		case 'array':
			if ( ! is_array( $value ) ) {
				return new WP_Error( 'igp_pro_invalid_array', sprintf( __( '%s must be an array.', 'igp-pro' ), $path ) );
			}
			if ( isset( $field_schema['min_items'] ) && count( $value ) < absint( $field_schema['min_items'] ) ) {
				return new WP_Error( 'igp_pro_array_too_short', sprintf( __( '%s does not contain enough items.', 'igp-pro' ), $path ) );
			}
			$item_fields = isset( $field_schema['fields'] ) && is_array( $field_schema['fields'] ) ? $field_schema['fields'] : array();
			if ( ! empty( $item_fields ) ) {
				foreach ( $value as $item_index => $item ) {
					if ( ! is_array( $item ) ) {
						return new WP_Error( 'igp_pro_invalid_repeater_item', sprintf( __( '%s.%d must be an object.', 'igp-pro' ), $path, (int) $item_index ) );
					}
					$item_result = igp_pro_validate_schema_data( array( 'fields' => $item_fields ), $item, $path . '.' . $item_index, $allow_string_relationships );
					if ( is_wp_error( $item_result ) ) {
						return $item_result;
					}
				}
			}
			return true;

		case 'relationship':
			if ( is_array( $value ) ) {
				foreach ( $value as $item ) {
					if ( ! is_numeric( $item ) && ! ( is_array( $item ) && ( isset( $item['id'] ) || isset( $item['ID'] ) ) ) ) {
						return new WP_Error( 'igp_pro_invalid_relationship', sprintf( __( '%s must contain numeric post IDs.', 'igp-pro' ), $path ) );
					}
				}
				return true;
			}
			if ( $allow_string_relationships && ( is_string( $value ) || is_numeric( $value ) ) ) {
				return true;
			}
			return new WP_Error( 'igp_pro_invalid_relationship', sprintf( __( '%s must contain an array of post IDs.', 'igp-pro' ), $path ) );

		case 'text':
		case 'string':
			return is_string( $value ) ? true : new WP_Error( 'igp_pro_invalid_string', sprintf( __( '%s must be a string.', 'igp-pro' ), $path ) );

		default:
			return is_scalar( $value ) || null === $value ? true : new WP_Error( 'igp_pro_invalid_scalar', sprintf( __( '%s must be a scalar value.', 'igp-pro' ), $path ) );
	}
}

/**
 * Apply schema defaults recursively for render-level validation only.
 *
 * @param array $schema Schema.
 * @param array $data Data.
 * @return array
 */
function igp_pro_apply_schema_defaults_for_validation( array $schema, array $data ): array {
	$fields   = isset( $schema['fields'] ) && is_array( $schema['fields'] ) ? $schema['fields'] : array();
	$defaults = isset( $schema['defaults'] ) && is_array( $schema['defaults'] ) ? $schema['defaults'] : array();
	$result   = $data;

	foreach ( $fields as $field_name => $field_schema ) {
		if ( array_key_exists( $field_name, $result ) || ! is_array( $field_schema ) ) {
			continue;
		}
		if ( array_key_exists( $field_name, $defaults ) ) {
			$result[ $field_name ] = $defaults[ $field_name ];
		} elseif ( array_key_exists( 'default', $field_schema ) ) {
			$result[ $field_name ] = $field_schema['default'];
		}
	}

	return $result;
}

/**
 * Determine if a value is empty for required field validation.
 *
 * @param mixed $value Field value.
 * @param array $field_schema Field schema.
 * @return bool
 */
function igp_pro_is_empty_schema_value( $value, array $field_schema ): bool {
	$type = isset( $field_schema['type'] ) ? (string) $field_schema['type'] : 'string';

	if ( null === $value || '' === $value ) {
		return true;
	}

	if ( 'image' === $type && is_array( $value ) ) {
		return empty( $value['url'] );
	}

	if ( in_array( $type, array( 'array', 'repeater', 'relationship' ), true ) ) {
		return empty( $value );
	}

	return false;
}
