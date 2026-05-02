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

		$block_id = sanitize_key( (string) $section['block_id'] );
		$block    = igp_pro_get_registered_block( $block_id );

		if ( ! $block ) {
			return new WP_Error(
				'igp_pro_graph_unknown_block',
				sprintf(
					/* translators: 1: section index, 2: block ID. */
					__( 'Content graph section %1$d references unknown block %2$s.', 'igp-pro' ),
					(int) $index,
					$block_id
				)
			);
		}

		$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();

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

		$schema = igp_pro_get_block_schema( $block );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		$field_validation = igp_pro_validate_schema_data( $schema, $data, 'sections.' . $index . '.data' );
		if ( is_wp_error( $field_validation ) ) {
			return $field_validation;
		}

		$block_validation = igp_pro_validate_block_data( $block, $data );
		if ( is_wp_error( $block_validation ) ) {
			return $block_validation;
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
 * @return true|WP_Error
 */
function igp_pro_validate_schema_data( array $schema, array $data, string $path = 'data' ) {
	$fields   = isset( $schema['fields'] ) && is_array( $schema['fields'] ) ? $schema['fields'] : array();
	$required = array();

	if ( isset( $schema['validation']['required'] ) && is_array( $schema['validation']['required'] ) ) {
		$required = array_map( 'strval', $schema['validation']['required'] );
	}

	foreach ( $fields as $field_name => $field_schema ) {
		if ( ! is_array( $field_schema ) ) {
			continue;
		}

		$field_path = $path . '.' . $field_name;
		$is_required = ! empty( $field_schema['required'] ) || in_array( (string) $field_name, $required, true );
		$value = array_key_exists( $field_name, $data ) ? $data[ $field_name ] : null;

		if ( $is_required && igp_pro_is_empty_schema_value( $value, $field_schema ) ) {
			return new WP_Error(
				'igp_pro_required_field_missing',
				sprintf(
					/* translators: %s: field path. */
					__( 'Required field missing: %s.', 'igp-pro' ),
					$field_path
				)
			);
		}

		if ( null === $value || '' === $value ) {
			continue;
		}

		$field_result = igp_pro_validate_schema_field( (string) $field_name, $field_schema, $value, $field_path );
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
 * @param string $field_name   Field name.
 * @param array  $field_schema Field schema.
 * @param mixed  $value        Value.
 * @param string $path         Error path.
 * @return true|WP_Error
 */
function igp_pro_validate_schema_field( string $field_name, array $field_schema, $value, string $path ) {
	$type = isset( $field_schema['type'] ) ? (string) $field_schema['type'] : 'string';

	switch ( $type ) {
		case 'boolean':
			return is_bool( $value ) ? true : new WP_Error( 'igp_pro_invalid_boolean', sprintf( __( '%s must be true or false.', 'igp-pro' ), $path ) );

		case 'number':
			if ( ! is_numeric( $value ) ) {
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
			return in_array( (string) $value, $allowed, true ) ? true : new WP_Error( 'igp_pro_invalid_enum', sprintf( __( '%s has an invalid value.', 'igp-pro' ), $path ) );

		case 'image':
			if ( ! is_array( $value ) ) {
				return new WP_Error( 'igp_pro_invalid_image', sprintf( __( '%s must be an image object.', 'igp-pro' ), $path ) );
			}
			return true;

		case 'object':
			if ( ! is_array( $value ) ) {
				return new WP_Error( 'igp_pro_invalid_object', sprintf( __( '%s must be an object.', 'igp-pro' ), $path ) );
			}
			return igp_pro_validate_schema_data( array( 'fields' => $field_schema['fields'] ?? array() ), $value, $path );

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
					$item_result = igp_pro_validate_schema_data( array( 'fields' => $item_fields ), $item, $path . '.' . $item_index );
					if ( is_wp_error( $item_result ) ) {
						return $item_result;
					}
				}
			}
			return true;

		case 'relationship':
			if ( ! is_array( $value ) && ! is_numeric( $value ) && ! is_string( $value ) ) {
				return new WP_Error( 'igp_pro_invalid_relationship', sprintf( __( '%s must contain post IDs.', 'igp-pro' ), $path ) );
			}
			return true;

		default:
			return is_scalar( $value ) || null === $value ? true : new WP_Error( 'igp_pro_invalid_scalar', sprintf( __( '%s must be a scalar value.', 'igp-pro' ), $path ) );
	}
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
