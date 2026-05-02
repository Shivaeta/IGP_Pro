<?php
/**
 * Block data resolver.
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
	}

	return $data;
}
