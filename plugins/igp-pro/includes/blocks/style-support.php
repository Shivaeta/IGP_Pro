<?php
/**
 * Shared style support for V2 smart block variants.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Determine whether smart block variants are enabled.
 *
 * @return bool
 */
function igp_pro_smart_block_variants_enabled(): bool {
	return function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_smart_block_variants' );
}

/**
 * Convert a block/style slug into a CSS class suffix.
 *
 * @param string $value Raw slug.
 * @return string
 */
function igp_pro_style_css_slug( string $value ): string {
	$value = sanitize_key( $value );
	$value = str_replace( '_', '-', $value );
	return '' !== $value ? $value : 'default';
}

/**
 * Return common style enum values.
 *
 * @return array<string,string[]>
 */
function igp_pro_get_common_style_options(): array {
	return array(
		'density'        => array( 'compact', 'comfortable', 'spacious' ),
		'theme'          => array( 'default', 'brand', 'neutral', 'light', 'dark', 'accent', 'muted' ),
		'container'      => array( 'narrow', 'default', 'wide', 'full' ),
		'surface'        => array( 'default', 'flat', 'elevated', 'card', 'tinted', 'transparent' ),
		'media_position' => array( 'auto', 'left', 'right', 'top', 'bottom', 'background', 'none' ),
	);
}

/**
 * Return style defaults.
 *
 * @param string $block_id Block ID.
 * @return array<string,string>
 */
function igp_pro_get_block_style_defaults( string $block_id = '' ): array {
	return array(
		'variant'        => 'default',
		'density'        => 'comfortable',
		'theme'          => 'brand',
		'container'      => 'wide',
		'surface'        => 'default',
		'media_position' => 'auto',
	);
}

/**
 * Return allowed variants by block.
 *
 * @param string $block_id Block ID.
 * @return string[]
 */
function igp_pro_get_block_style_variants( string $block_id ): array {
	$block_id = sanitize_key( $block_id );

	$card_variants = array( 'default', 'grid', 'carousel-safe', 'list', 'featured' );

	$variants = array(
		'hero'                 => array( 'default', 'full-width', 'image-left', 'image-right', 'split-overlay', 'centered-minimal' ),
		'cta'                  => array( 'default', 'inline', 'banner', 'split', 'card' ),
		'tour_cards'           => $card_variants,
		'destination_cards'    => $card_variants,
		'featured_listings'    => $card_variants,
		'related_tours'        => $card_variants,
		'related_destinations' => $card_variants,
		'gallery'              => array( 'default', 'grid', 'masonry-safe', 'slider-safe' ),
		'faq'                  => array( 'default', 'accordion', 'grouped', 'compact' ),
		'itinerary'            => array( 'default', 'timeline', 'cards', 'compact' ),
		'trust'                => array( 'default', 'logo-strip', 'testimonial-cards', 'stats' ),
		'pricing_summary'      => array( 'default', 'compact', 'featured', 'comparison' ),
		'tour_facts'           => array( 'default', 'grid', 'compact', 'icons' ),
		'inclusions_exclusions'=> array( 'default', 'two-column', 'compact', 'cards' ),
		'departure_dates'      => array( 'default', 'table', 'cards', 'compact' ),
		'package_tiers'        => array( 'default', 'comparison', 'cards', 'compact' ),
		'reviews_summary'      => array( 'default', 'summary', 'cards', 'compact' ),
		'visa_requirements'    => array( 'default', 'checklist', 'cards', 'compact' ),
		'best_time_to_visit'   => array( 'default', 'seasons', 'monthly', 'compact' ),
		'route_timeline'       => array( 'default', 'timeline', 'cards', 'compact' ),
		'expert_box'           => array( 'default', 'card', 'profile', 'compact' ),
		'sticky_booking_cta'   => array( 'default', 'bottom-bar', 'side-card', 'inline' ),
		'nearby_attractions'   => array( 'default', 'grid', 'list', 'compact' ),
		'brochure_cta'         => array( 'default', 'banner', 'card', 'inline' ),
		'rich_text'            => array( 'default', 'article', 'lead', 'panel', 'quote' ),
		'section'              => array( 'default', 'band', 'split', 'grid' ),
		'stats'                => array( 'default', 'grid', 'strip', 'cards' ),
		'accordions'           => array( 'default', 'accordion', 'grouped', 'compact' ),
		'tabs'                 => array( 'default', 'pills', 'underline', 'boxed' ),
		'icon_list'            => array( 'default', 'grid', 'compact', 'cards' ),
		'map'                  => array( 'default', 'wide', 'card' ),
		'breadcrumb'           => array( 'default', 'compact' ),
	);

	return isset( $variants[ $block_id ] ) ? $variants[ $block_id ] : array( 'default' );
}

/**
 * Build the canonical style field schema for a block.
 *
 * @param string $block_id Block ID.
 * @return array<string,mixed>
 */
function igp_pro_get_block_style_field_schema( string $block_id ): array {
	$options  = igp_pro_get_common_style_options();
	$defaults = igp_pro_get_block_style_defaults( $block_id );

	return array(
		'type'   => 'object',
		'label'  => __( 'Style', 'igp-pro' ),
		'fields' => array(
			'variant'        => array(
				'type'    => 'enum',
				'label'   => __( 'Variant', 'igp-pro' ),
				'values'  => igp_pro_get_block_style_variants( $block_id ),
				'default' => $defaults['variant'],
			),
			'density'        => array(
				'type'    => 'enum',
				'label'   => __( 'Density', 'igp-pro' ),
				'values'  => $options['density'],
				'default' => $defaults['density'],
			),
			'theme'          => array(
				'type'    => 'enum',
				'label'   => __( 'Theme', 'igp-pro' ),
				'values'  => $options['theme'],
				'default' => $defaults['theme'],
			),
			'container'      => array(
				'type'    => 'enum',
				'label'   => __( 'Container', 'igp-pro' ),
				'values'  => $options['container'],
				'default' => $defaults['container'],
			),
			'surface'        => array(
				'type'    => 'enum',
				'label'   => __( 'Surface', 'igp-pro' ),
				'values'  => $options['surface'],
				'default' => $defaults['surface'],
			),
			'media_position' => array(
				'type'    => 'enum',
				'label'   => __( 'Media position', 'igp-pro' ),
				'values'  => $options['media_position'],
				'default' => $defaults['media_position'],
			),
		),
	);
}

/**
 * Normalize style config for safe frontend class output.
 *
 * @param string $block_id Block ID.
 * @param mixed  $style Raw style value.
 * @return array<string,string>
 */
function igp_pro_normalize_block_style( string $block_id, $style ): array {
	$defaults = igp_pro_get_block_style_defaults( $block_id );
	$style    = is_array( $style ) ? $style : array();
	$output   = array_merge( $defaults, $style );
	$options  = igp_pro_get_common_style_options();
	$variants = igp_pro_get_block_style_variants( $block_id );

	$output['variant'] = in_array( (string) $output['variant'], $variants, true ) ? (string) $output['variant'] : $defaults['variant'];
	foreach ( $options as $key => $allowed ) {
		$output[ $key ] = in_array( (string) ( $output[ $key ] ?? '' ), $allowed, true ) ? (string) $output[ $key ] : $defaults[ $key ];
	}

	return array(
		'variant'        => $output['variant'],
		'density'        => $output['density'],
		'theme'          => $output['theme'],
		'container'      => $output['container'],
		'surface'        => $output['surface'],
		'media_position' => $output['media_position'],
	);
}

/**
 * Validate style object beyond generic schema checks.
 *
 * @param string              $block_id Block ID.
 * @param array<string,mixed> $data Block data.
 * @param array<string,mixed> $schema Block schema.
 * @return true|WP_Error
 */
function igp_pro_validate_block_style_data( string $block_id, array $data, array $schema = array() ) {
	if ( ! isset( $schema['fields']['style'] ) ) {
		return true;
	}

	if ( ! array_key_exists( 'style', $data ) ) {
		return true;
	}

	if ( ! is_array( $data['style'] ) ) {
		return new WP_Error( 'igp_pro_style_invalid_shape', __( 'Block style must be a structured object.', 'igp-pro' ) );
	}

	$style    = $data['style'];
	$variants = igp_pro_get_block_style_variants( $block_id );
	$options  = igp_pro_get_common_style_options();

	if ( isset( $style['variant'] ) && ! in_array( (string) $style['variant'], $variants, true ) ) {
		return new WP_Error( 'igp_pro_style_invalid_variant', sprintf( __( 'Invalid style variant for block: %s.', 'igp-pro' ), sanitize_key( $block_id ) ) );
	}

	foreach ( $options as $key => $allowed ) {
		if ( isset( $style[ $key ] ) && ! in_array( (string) $style[ $key ], $allowed, true ) ) {
			return new WP_Error( 'igp_pro_style_invalid_' . sanitize_key( $key ), sprintf( __( 'Invalid style value for %s.', 'igp-pro' ), sanitize_key( $key ) ) );
		}
	}

	return true;
}

/**
 * Return frontend classes for a block style object.
 *
 * @param string              $block_id Block ID.
 * @param array<string,mixed> $data Block data.
 * @return string[]
 */
function igp_pro_get_block_style_classes( string $block_id, array $data ): array {
	$style = igp_pro_normalize_block_style( $block_id, $data['style'] ?? array() );

	return array(
		'igp-variant--' . igp_pro_style_css_slug( $style['variant'] ),
		'igp-density--' . igp_pro_style_css_slug( $style['density'] ),
		'igp-theme--' . igp_pro_style_css_slug( $style['theme'] ),
		'igp-container--' . igp_pro_style_css_slug( $style['container'] ),
		'igp-surface--' . igp_pro_style_css_slug( $style['surface'] ),
		'igp-media--' . igp_pro_style_css_slug( $style['media_position'] ),
	);
}

/**
 * Apply style defaults for render-time fallback/migration.
 *
 * @param string              $block_id Block ID.
 * @param array<string,mixed> $data Block data.
 * @return array<string,mixed>
 */
function igp_pro_apply_block_style_defaults_for_render( string $block_id, array $data ): array {
	if ( ! isset( $data['style'] ) || ! is_array( $data['style'] ) ) {
		$data['style'] = igp_pro_get_block_style_defaults( $block_id );
		return $data;
	}

	$data['style'] = array_merge( igp_pro_get_block_style_defaults( $block_id ), $data['style'] );
	return $data;
}
