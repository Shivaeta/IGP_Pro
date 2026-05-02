<?php
/**
 * Content Graph sanitization helpers.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize a Content Graph payload before persistence.
 *
 * @param array $graph Raw graph.
 * @return array
 */
function igp_pro_sanitize_content_graph_payload( array $graph ): array {
	$sanitized = array(
		'version'  => isset( $graph['version'] ) ? sanitize_key( (string) $graph['version'] ) : 'v1',
		'sections' => array(),
	);

	if ( isset( $graph['meta'] ) && is_array( $graph['meta'] ) ) {
		$sanitized['meta'] = array();
		if ( isset( $graph['meta']['description'] ) ) {
			$sanitized['meta']['description'] = sanitize_textarea_field( (string) $graph['meta']['description'] );
		}
	}

	$sections = isset( $graph['sections'] ) && is_array( $graph['sections'] ) ? $graph['sections'] : array();

	foreach ( $sections as $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}

		$block_id = isset( $section['block_id'] ) ? sanitize_key( (string) $section['block_id'] ) : '';

		if ( '' === $block_id ) {
			continue;
		}

		$block  = igp_pro_get_registered_block( $block_id );
		$schema = $block ? igp_pro_get_block_schema( $block ) : null;
		$data   = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();

		if ( is_array( $schema ) ) {
			$data = igp_pro_sanitize_schema_data( $schema, $data );
		} else {
			$data = igp_pro_sanitize_unknown_data( $data );
		}

		$next_section = array(
			'block_id' => $block_id,
			'data'     => $data,
		);

		if ( isset( $section['id'] ) && '' !== (string) $section['id'] ) {
			$next_section['id'] = sanitize_key( (string) $section['id'] );
		}

		$sanitized['sections'][] = $next_section;
	}

	return $sanitized;
}

/**
 * Sanitize data using a block schema.
 *
 * @param array $schema Schema.
 * @param array $data   Raw data.
 * @return array
 */
function igp_pro_sanitize_schema_data( array $schema, array $data ): array {
	$fields    = isset( $schema['fields'] ) && is_array( $schema['fields'] ) ? $schema['fields'] : array();
	$defaults  = isset( $schema['defaults'] ) && is_array( $schema['defaults'] ) ? $schema['defaults'] : array();
	$sanitized = array();

	foreach ( $fields as $field_name => $field_schema ) {
		if ( ! is_array( $field_schema ) ) {
			continue;
		}

		$raw_value = array_key_exists( $field_name, $data ) ? $data[ $field_name ] : ( $field_schema['default'] ?? ( $defaults[ $field_name ] ?? null ) );
		$sanitized[ $field_name ] = igp_pro_sanitize_schema_field( (string) $field_name, $field_schema, $raw_value );
	}

	return $sanitized;
}

/**
 * Sanitize one schema field.
 *
 * @param string $field_name   Field name.
 * @param array  $field_schema Field schema.
 * @param mixed  $value        Raw value.
 * @return mixed
 */
function igp_pro_sanitize_schema_field( string $field_name, array $field_schema, $value ) {
	$type = isset( $field_schema['type'] ) ? (string) $field_schema['type'] : 'string';

	switch ( $type ) {
		case 'boolean':
			return (bool) $value;

		case 'number':
			if ( '' === $value || null === $value ) {
				return isset( $field_schema['default'] ) && is_numeric( $field_schema['default'] ) ? 0 + $field_schema['default'] : 0;
			}
			$value = is_numeric( $value ) ? 0 + $value : 0;
			if ( isset( $field_schema['min'] ) && is_numeric( $field_schema['min'] ) ) {
				$value = max( 0 + $field_schema['min'], $value );
			}
			if ( isset( $field_schema['max'] ) && is_numeric( $field_schema['max'] ) ) {
				$value = min( 0 + $field_schema['max'], $value );
			}
			return $value;

		case 'enum':
			$allowed = isset( $field_schema['values'] ) && is_array( $field_schema['values'] ) ? array_map( 'sanitize_key', $field_schema['values'] ) : array();
			$value   = sanitize_key( (string) $value );
			if ( in_array( $value, $allowed, true ) ) {
				return $value;
			}
			return isset( $field_schema['default'] ) ? sanitize_key( (string) $field_schema['default'] ) : ( $allowed[0] ?? '' );

		case 'text':
			return wp_kses_post( (string) $value );

		case 'image':
			$image = is_array( $value ) ? $value : array( 'url' => (string) $value );
			return array(
				'url' => isset( $image['url'] ) ? esc_url_raw( (string) $image['url'] ) : '',
				'alt' => isset( $image['alt'] ) ? sanitize_text_field( (string) $image['alt'] ) : '',
			);

		case 'object':
			$object_value = is_array( $value ) ? $value : array();
			$fields       = isset( $field_schema['fields'] ) && is_array( $field_schema['fields'] ) ? $field_schema['fields'] : array();
			$object       = array();

			foreach ( $fields as $child_name => $child_schema ) {
				if ( ! is_array( $child_schema ) ) {
					continue;
				}

				$raw_child = array_key_exists( $child_name, $object_value ) ? $object_value[ $child_name ] : ( $child_schema['default'] ?? null );
				$object[ $child_name ] = igp_pro_sanitize_schema_field( (string) $child_name, $child_schema, $raw_child );
			}

			return $object;

		case 'repeater':
		case 'array':
			$items = $value;
			if ( is_string( $items ) ) {
				$decoded = json_decode( $items, true );
				$items   = JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ? $decoded : array();
			}
			if ( ! is_array( $items ) ) {
				return array();
			}

			$item_fields = isset( $field_schema['fields'] ) && is_array( $field_schema['fields'] ) ? $field_schema['fields'] : array();
			$sanitized_items = array();

			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					if ( is_scalar( $item ) && '' !== trim( (string) $item ) ) {
						$sanitized_items[] = sanitize_text_field( (string) $item );
					}
					continue;
				}

				if ( empty( $item_fields ) ) {
					$sanitized_items[] = igp_pro_sanitize_unknown_data( $item );
					continue;
				}

				$clean_item = array();
				foreach ( $item_fields as $child_name => $child_schema ) {
					if ( ! is_array( $child_schema ) ) {
						continue;
					}
					$raw_child = array_key_exists( $child_name, $item ) ? $item[ $child_name ] : ( $child_schema['default'] ?? null );
					$clean_item[ $child_name ] = igp_pro_sanitize_schema_field( (string) $child_name, $child_schema, $raw_child );
				}
				$sanitized_items[] = $clean_item;
			}

			return $sanitized_items;

		case 'relationship':
			return igp_pro_normalize_post_ids( $value );

		default:
			$value = (string) $value;
			if ( preg_match( '/(^|_)url$/', $field_name ) || false !== strpos( $field_name, 'url' ) ) {
				return esc_url_raw( $value );
			}
			return sanitize_text_field( $value );
	}
}

/**
 * Conservative sanitizer for unknown arrays.
 *
 * @param array $data Raw data.
 * @return array
 */
function igp_pro_sanitize_unknown_data( array $data ): array {
	$clean = array();

	foreach ( $data as $key => $value ) {
		$key = sanitize_key( (string) $key );
		if ( '' === $key ) {
			continue;
		}

		if ( is_array( $value ) ) {
			$clean[ $key ] = igp_pro_sanitize_unknown_data( $value );
		} elseif ( is_bool( $value ) ) {
			$clean[ $key ] = $value;
		} elseif ( is_numeric( $value ) ) {
			$clean[ $key ] = 0 + $value;
		} else {
			$clean[ $key ] = wp_kses_post( (string) $value );
		}
	}

	return $clean;
}
