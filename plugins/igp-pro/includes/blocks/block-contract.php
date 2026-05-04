<?php
/**
 * Canonical UI block and Content Graph contract helpers.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_CONTENT_GRAPH_SCHEMA_VERSION' ) ) {
	define( 'IGP_PRO_CONTENT_GRAPH_SCHEMA_VERSION', '2.0' );
}

/**
 * Return the current canonical Content Graph schema version.
 *
 * @return string
 */
function igp_pro_get_canonical_content_graph_schema_version(): string {
	return function_exists( 'igp_pro_get_current_content_graph_schema_version' ) ? igp_pro_get_current_content_graph_schema_version() : IGP_PRO_CONTENT_GRAPH_SCHEMA_VERSION;
}

/**
 * Return the canonical empty Content Graph shape.
 *
 * @return array<string,mixed>
 */
function igp_pro_get_canonical_empty_content_graph(): array {
	return array(
		'version'          => 'v1',
		'schema_version'   => igp_pro_get_canonical_content_graph_schema_version(),
		'migrated_from'    => '',
		'last_migrated_at' => '',
		'sections'         => array(),
	);
}

/**
 * Legacy top-level variant values mapped into canonical style.variant.
 *
 * @return array<string,array<string,string>>
 */
function igp_pro_get_legacy_block_variant_map(): array {
	$card_map = array(
		'elevated' => 'grid',
		'bordered' => 'list',
		'compact'  => 'featured',
		'default'  => 'default',
		'grid'     => 'grid',
		'list'     => 'list',
		'featured' => 'featured',
	);

	return array(
		'cta'               => array(
			'solid'   => 'default',
			'outline' => 'card',
			'minimal' => 'inline',
			'split'   => 'split',
			'dark'    => 'banner',
			'default' => 'default',
			'inline'  => 'inline',
			'banner'  => 'banner',
			'card'    => 'card',
		),
		'destination_cards' => $card_map,
		'tour_cards'        => $card_map,
		'featured_listings' => $card_map,
		'related_tours'     => $card_map,
		'related_destinations' => $card_map,
		'rich_text'         => array(
			'default' => 'default',
			'lead'    => 'lead',
			'panel'   => 'panel',
			'quote'   => 'quote',
			'article' => 'article',
		),
		'section'           => array(
			'default'   => 'default',
			'contained' => 'band',
			'wide'      => 'split',
			'panel'     => 'band',
			'accent'    => 'band',
			'dark'      => 'band',
			'band'      => 'band',
			'split'     => 'split',
			'grid'      => 'grid',
		),
	);
}

/**
 * Normalize a block ID from old or new section keys.
 *
 * @param array<string,mixed> $section Section.
 * @return string
 */
function igp_pro_get_section_block_id( array $section ): string {
	$block_id = '';
	if ( isset( $section['block_id'] ) ) {
		$block_id = (string) $section['block_id'];
	} elseif ( isset( $section['block'] ) ) {
		$block_id = (string) $section['block'];
	}

	return sanitize_key( $block_id );
}

/**
 * Canonicalize one block data object without trusting visual legacy fields.
 *
 * @param string              $block_id Block ID.
 * @param array<string,mixed> $data     Data.
 * @return array<string,mixed>
 */
function igp_pro_canonicalize_block_data( string $block_id, array $data ): array {
	$block_id = sanitize_key( $block_id );

	if ( function_exists( 'igp_pro_migrate_block_heading_data_for_render' ) ) {
		$data = igp_pro_migrate_block_heading_data_for_render( $block_id, $data );
	}

	$legacy_variant = null;
	if ( array_key_exists( 'variant', $data ) && is_scalar( $data['variant'] ) ) {
		$legacy_variant = sanitize_key( (string) $data['variant'] );
		unset( $data['variant'] );
	}

	if ( ! isset( $data['style'] ) || ! is_array( $data['style'] ) ) {
		$data['style'] = function_exists( 'igp_pro_get_block_style_defaults' ) ? igp_pro_get_block_style_defaults( $block_id ) : array( 'variant' => 'default' );
	}

	if ( null !== $legacy_variant ) {
		$map     = igp_pro_get_legacy_block_variant_map();
		$variant = $map[ $block_id ][ $legacy_variant ] ?? $legacy_variant;
		$data['style']['variant'] = $variant;
	}

	if ( function_exists( 'igp_pro_normalize_block_style' ) ) {
		$data['style'] = igp_pro_normalize_block_style( $block_id, $data['style'] );
	}

	return $data;
}


/**
 * Validate that all Content Graph section IDs are unique across nesting levels.
 *
 * @param array<int,array<string,mixed>> $sections Sections.
 * @param array<string,bool>             $seen     Seen ID map.
 * @param string                         $path     Path for errors.
 * @return true|WP_Error
 */
function igp_pro_validate_unique_section_ids( array $sections, array &$seen = array(), string $path = 'sections' ) {
	foreach ( $sections as $index => $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$id = isset( $section['id'] ) ? sanitize_key( (string) $section['id'] ) : '';
		if ( '' !== $id ) {
			if ( isset( $seen[ $id ] ) ) {
				return new WP_Error(
					'igp_pro_duplicate_section_id',
					sprintf(
						/* translators: 1: section ID, 2: graph path. */
						__( 'Duplicate Content Graph section ID "%1$s" found at %2$s.', 'igp-pro' ),
						$id,
						$path . '.' . (int) $index
					)
				);
			}
			$seen[ $id ] = true;
		}
		if ( ! empty( $section['children'] ) && is_array( $section['children'] ) ) {
			$child_validation = igp_pro_validate_unique_section_ids( $section['children'], $seen, $path . '.' . (int) $index . '.children' );
			if ( is_wp_error( $child_validation ) ) {
				return $child_validation;
			}
		}
	}
	return true;
}

/**
 * Canonicalize a full Content Graph object into the UI-R1 contract.
 *
 * @param array<string,mixed> $graph Raw graph.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_canonicalize_content_graph( array $graph ) {
	$canonical = igp_pro_get_canonical_empty_content_graph();

	$canonical['version'] = isset( $graph['version'] ) && is_string( $graph['version'] ) ? sanitize_key( $graph['version'] ) : 'v1';
	$incoming_schema = isset( $graph['schema_version'] ) && is_string( $graph['schema_version'] ) ? (string) $graph['schema_version'] : '';
	$current_schema  = igp_pro_get_canonical_content_graph_schema_version();
	$normalized_incoming = '' !== $incoming_schema && function_exists( 'igp_pro_normalize_schema_version' ) ? igp_pro_normalize_schema_version( $incoming_schema ) : $incoming_schema;
	$canonical['schema_version'] = $current_schema;

	if ( isset( $graph['migrated_from'] ) ) {
		$canonical['migrated_from'] = sanitize_text_field( (string) $graph['migrated_from'] );
	} elseif ( '' !== $normalized_incoming && $normalized_incoming !== $current_schema ) {
		$canonical['migrated_from'] = sanitize_text_field( $normalized_incoming );
	}
	if ( isset( $graph['last_migrated_at'] ) ) {
		$canonical['last_migrated_at'] = sanitize_text_field( (string) $graph['last_migrated_at'] );
	}
	if ( isset( $graph['seo'] ) && is_array( $graph['seo'] ) ) {
		$canonical['seo'] = $graph['seo'];
	}
	if ( isset( $graph['meta'] ) && is_array( $graph['meta'] ) ) {
		$canonical['meta'] = $graph['meta'];
	}

	if ( ! isset( $graph['sections'] ) || ! is_array( $graph['sections'] ) ) {
		return new WP_Error( 'igp_pro_graph_missing_sections', __( 'Content graph sections must be an array.', 'igp-pro' ) );
	}

	$sections = igp_pro_canonicalize_graph_sections( $graph['sections'], 'sections', 0 );
	if ( is_wp_error( $sections ) ) {
		return $sections;
	}
	$unique_ids = igp_pro_validate_unique_section_ids( $sections );
	if ( is_wp_error( $unique_ids ) ) {
		return $unique_ids;
	}
	$canonical['sections'] = $sections;

	if ( '' === $canonical['migrated_from'] && '' === $incoming_schema ) {
		$canonical['migrated_from'] = '1.0';
	}
	if ( '' !== $canonical['migrated_from'] && '' === $canonical['last_migrated_at'] ) {
		$canonical['last_migrated_at'] = gmdate( 'c' );
	}

	return $canonical;
}

/**
 * Canonicalize a list of graph sections recursively.
 *
 * @param array<int,mixed> $sections Raw sections.
 * @param string           $path     Error path.
 * @param int              $depth    Nesting depth.
 * @return array<int,array<string,mixed>>|WP_Error
 */
function igp_pro_canonicalize_graph_sections( array $sections, string $path = 'sections', int $depth = 0 ) {
	if ( $depth > 4 ) {
		return new WP_Error( 'igp_pro_graph_max_depth_exceeded', __( 'Content graph nesting cannot exceed four levels.', 'igp-pro' ) );
	}

	$output = array();
	foreach ( $sections as $index => $section ) {
		if ( ! is_array( $section ) ) {
			return new WP_Error( 'igp_pro_graph_invalid_section', sprintf( __( '%s.%d must be an object.', 'igp-pro' ), $path, (int) $index ) );
		}

		$canonical = igp_pro_canonicalize_graph_section( $section, $path . '.' . $index, $depth );
		if ( is_wp_error( $canonical ) ) {
			return $canonical;
		}
		$output[] = $canonical;
	}

	return $output;
}

/**
 * Canonicalize one graph section.
 *
 * @param array<string,mixed> $section Raw section.
 * @param string              $path    Error path.
 * @param int                 $depth   Nesting depth.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_canonicalize_graph_section( array $section, string $path, int $depth = 0 ) {
	$block_id = igp_pro_get_section_block_id( $section );
	if ( '' === $block_id ) {
		return new WP_Error( 'igp_pro_graph_missing_block_id', sprintf( __( '%s requires a block_id.', 'igp-pro' ), $path ) );
	}

	$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
	$data = igp_pro_canonicalize_block_data( $block_id, $data );

	$canonical = array(
		'id'             => isset( $section['id'] ) && '' !== (string) $section['id'] ? sanitize_key( (string) $section['id'] ) : sanitize_key( 'section_' . $depth . '_' . $block_id . '_' . substr( md5( wp_json_encode( $data ) . $path ), 0, 8 ) ),
		'block_id'       => $block_id,
		'block'          => $block_id,
		'schema_version' => isset( $section['schema_version'] ) ? sanitize_text_field( (string) $section['schema_version'] ) : igp_pro_get_canonical_content_graph_schema_version(),
		'data'           => $data,
		'children'       => array(),
	);

	if ( isset( $section['version'] ) ) {
		$canonical['version'] = sanitize_text_field( (string) $section['version'] );
	}
	if ( isset( $section['migrated_from'] ) ) {
		$canonical['migrated_from'] = sanitize_text_field( (string) $section['migrated_from'] );
	}

	$children = array();
	if ( isset( $section['children'] ) && is_array( $section['children'] ) ) {
		$children = $section['children'];
	} elseif ( isset( $section['innerBlocks'] ) && is_array( $section['innerBlocks'] ) ) {
		$children = $section['innerBlocks'];
	}

	if ( ! empty( $children ) ) {
		$canonical_children = igp_pro_canonicalize_graph_sections( $children, $path . '.children', $depth + 1 );
		if ( is_wp_error( $canonical_children ) ) {
			return $canonical_children;
		}
		$canonical['children'] = $canonical_children;
	}

	return $canonical;
}

/**
 * Calculate a deterministic checksum for a graph or derived content.
 *
 * @param mixed $value Value.
 * @return string
 */
function igp_pro_content_graph_checksum( $value ): string {
	$encoded = is_string( $value ) ? $value : wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	return is_string( $encoded ) ? hash( 'sha256', $encoded ) : '';
}
