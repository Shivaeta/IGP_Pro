<?php
/**
 * Block migration registry for IGP Pro V2.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the block migration registry.
 *
 * @return array<string,callable>
 */
function igp_pro_get_block_migration_registry(): array {
	return array(
		'1.0:2.0' => 'igp_migrate_block_1_0_to_2_0',
	);
}

/**
 * Detect a block data version.
 *
 * @param array<string,mixed> $section Section object.
 * @param array<string,mixed> $schema  Block schema.
 * @return string
 */
function igp_pro_detect_block_schema_version( array $section, array $schema = array() ): string {
	if ( isset( $section['schema_version'] ) && is_string( $section['schema_version'] ) ) {
		return function_exists( 'igp_pro_normalize_schema_version' ) ? igp_pro_normalize_schema_version( $section['schema_version'] ) : $section['schema_version'];
	}

	if ( isset( $section['version'] ) && is_string( $section['version'] ) ) {
		return function_exists( 'igp_pro_normalize_schema_version' ) ? igp_pro_normalize_schema_version( $section['version'] ) : $section['version'];
	}

	if ( isset( $schema['version'] ) && is_string( $schema['version'] ) ) {
		return function_exists( 'igp_pro_normalize_schema_version' ) ? igp_pro_normalize_schema_version( $schema['version'] ) : $schema['version'];
	}

	return '1.0';
}

/**
 * Migrate one block section.
 *
 * @param array<string,mixed> $section Section object.
 * @param string             $target  Target version.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_migrate_block_section( array $section, string $target = '2.0' ) {
	if ( empty( $section['block_id'] ) || ! is_string( $section['block_id'] ) ) {
		return new WP_Error( 'igp_pro_migration_missing_block_id', __( 'Block migration requires a block_id.', 'igp-pro' ) );
	}

	$block_id = sanitize_key( $section['block_id'] );
	$block    = function_exists( 'igp_pro_get_registered_block' ) ? igp_pro_get_registered_block( $block_id ) : null;
	if ( ! $block ) {
		return new WP_Error( 'igp_pro_migration_unknown_block', sprintf( __( 'Cannot migrate unknown block: %s.', 'igp-pro' ), $block_id ) );
	}

	$schema = function_exists( 'igp_pro_get_block_schema' ) ? igp_pro_get_block_schema( $block ) : array();
	if ( is_wp_error( $schema ) ) {
		return $schema;
	}
	$schema = is_array( $schema ) ? $schema : array();

	$from = igp_pro_detect_block_schema_version( $section, $schema );
	$to   = function_exists( 'igp_pro_normalize_schema_version' ) ? igp_pro_normalize_schema_version( $target ) : $target;

	if ( $from === $to ) {
		$section['schema_version'] = $to;
		return $section;
	}

	$key      = $from . ':' . $to;
	$registry = igp_pro_get_block_migration_registry();
	if ( empty( $registry[ $key ] ) || ! is_callable( $registry[ $key ] ) ) {
		return new WP_Error( 'igp_pro_missing_block_migration', sprintf( __( 'No block migration exists from %1$s to %2$s for %3$s.', 'igp-pro' ), $from, $to, $block_id ) );
	}

	$migrated = call_user_func( $registry[ $key ], $block_id, $section, $schema );
	if ( is_wp_error( $migrated ) ) {
		return $migrated;
	}

	$migrated['schema_version'] = $to;
	$migrated['migrated_from']  = $from;

	return $migrated;
}

/**
 * Generic V1-to-V2 block migration.
 *
 * Applies current schema defaults without dropping unknown WordPress block
 * attributes that are explicitly permitted by the validator.
 *
 * @param string              $block_id Block ID.
 * @param array<string,mixed> $section  Section object.
 * @param array<string,mixed> $schema   Schema.
 * @return array<string,mixed>
 */
function igp_migrate_block_1_0_to_2_0( string $block_id, array $section, array $schema ): array {
	$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();

	if ( function_exists( 'igp_pro_migrate_block_heading_data_for_render' ) ) {
		$data = igp_pro_migrate_block_heading_data_for_render( $block_id, $data );
	}

	if ( function_exists( 'igp_pro_apply_block_style_defaults_for_render' ) ) {
		$data = igp_pro_apply_block_style_defaults_for_render( $block_id, $data );
	}

	$defaults = function_exists( 'igp_pro_apply_schema_defaults_for_validation' )
		? igp_pro_apply_schema_defaults_for_validation( $schema, $data )
		: igp_pro_migration_apply_schema_defaults( $schema, $data );

	$section['block_id'] = sanitize_key( $block_id );
	$section['data']     = $defaults;

	return $section;
}

/**
 * Traceable Hero block V1-to-V2 migration wrapper.
 *
 * @param array<string,mixed> $section Section object.
 * @param array<string,mixed> $schema  Schema.
 * @return array<string,mixed>
 */
function igp_migrate_block_hero_1_0_to_2_0( array $section, array $schema = array() ): array {
	return igp_migrate_block_1_0_to_2_0( 'hero', $section, $schema );
}

/**
 * Fallback recursive schema default applier used before validator helpers load.
 *
 * @param array<string,mixed> $schema Schema.
 * @param array<string,mixed> $data   Data.
 * @return array<string,mixed>
 */
function igp_pro_migration_apply_schema_defaults( array $schema, array $data ): array {
	$defaults = isset( $schema['defaults'] ) && is_array( $schema['defaults'] ) ? $schema['defaults'] : array();
	$fields   = isset( $schema['fields'] ) && is_array( $schema['fields'] ) ? $schema['fields'] : array();

	$output = array_merge( $defaults, $data );

	foreach ( $fields as $field_name => $field_schema ) {
		if ( array_key_exists( $field_name, $output ) || ! is_array( $field_schema ) ) {
			continue;
		}

		if ( array_key_exists( 'default', $field_schema ) ) {
			$output[ $field_name ] = $field_schema['default'];
			continue;
		}

		$type = isset( $field_schema['type'] ) ? (string) $field_schema['type'] : 'string';
		if ( 'object' === $type ) {
			$output[ $field_name ] = igp_pro_migration_apply_schema_defaults( array( 'fields' => $field_schema['fields'] ?? array() ), array() );
		} elseif ( in_array( $type, array( 'array', 'repeater', 'relationship' ), true ) ) {
			$output[ $field_name ] = array();
		} elseif ( 'image' === $type ) {
			$output[ $field_name ] = array( 'url' => '', 'alt' => '' );
		} elseif ( 'boolean' === $type ) {
			$output[ $field_name ] = false;
		} elseif ( 'number' === $type ) {
			$output[ $field_name ] = 0;
		} else {
			$output[ $field_name ] = '';
		}
	}

	return $output;
}


/**
 * Migrate legacy V1 heading/title fields into the V2 heading object shape.
 * This function is intentionally safe for render-time use and does not mutate
 * storage unless its result is later persisted by the Content Graph service.
 *
 * @param string              $block_id Block ID.
 * @param array<string,mixed> $data Block data.
 * @return array<string,mixed>
 */
function igp_pro_migrate_block_heading_data_for_render( string $block_id, array $data ): array {
	if ( isset( $data['heading'] ) && is_array( $data['heading'] ) ) {
		$data['heading'] = array_merge(
			array(
				'text'    => '',
				'level'   => 'h2',
				'eyebrow' => '',
				'visible' => true,
			),
			$data['heading']
		);
		$data['heading']['level'] = in_array( sanitize_key( (string) $data['heading']['level'] ), array( 'h2', 'h3', 'h4' ), true ) ? sanitize_key( (string) $data['heading']['level'] ) : 'h2';
		$data['heading']['visible'] = (bool) $data['heading']['visible'];
		return $data;
	}

	$block_id = sanitize_key( $block_id );
	$text     = '';
	$eyebrow  = '';

	if ( isset( $data['heading'] ) && is_scalar( $data['heading'] ) ) {
		$text = trim( (string) $data['heading'] );
	} elseif ( isset( $data['title'] ) && is_scalar( $data['title'] ) ) {
		$text = trim( (string) $data['title'] );
	}

	if ( isset( $data['eyebrow'] ) && is_scalar( $data['eyebrow'] ) ) {
		$eyebrow = trim( (string) $data['eyebrow'] );
	}

	if ( '' === $text && ! in_array( $block_id, array( 'hero', 'cta' ), true ) && ! array_key_exists( 'title', $data ) ) {
		return $data;
	}

	$data['heading'] = array(
		'text'    => $text,
		'level'   => 'h2',
		'eyebrow' => $eyebrow,
		'visible' => true,
	);

	unset( $data['title'], $data['eyebrow'] );

	return $data;
}


/**
 * Migrate missing V1 style data into the V2 shared style object shape.
 * This is safe for render-time use and does not persist unless saved later.
 *
 * @param string              $block_id Block ID.
 * @param array<string,mixed> $data Block data.
 * @return array<string,mixed>
 */
function igp_pro_migrate_block_style_data_for_render( string $block_id, array $data ): array {
	if ( function_exists( 'igp_pro_apply_block_style_defaults_for_render' ) ) {
		return igp_pro_apply_block_style_defaults_for_render( $block_id, $data );
	}

	if ( ! isset( $data['style'] ) || ! is_array( $data['style'] ) ) {
		$data['style'] = array(
			'variant'        => 'default',
			'density'        => 'comfortable',
			'theme'          => 'brand',
			'container'      => 'wide',
			'surface'        => 'default',
			'media_position' => 'auto',
		);
	}

	return $data;
}
