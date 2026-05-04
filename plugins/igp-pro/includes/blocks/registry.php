<?php
/**
 * Central block registry.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read the declared version from a block schema file without requiring the full
 * schema resolver. Used by the registry to keep block metadata aligned with the
 * actual schema contract loaded by the editor/renderer.
 *
 * @param string $schema_path Schema file path.
 * @param string $fallback    Fallback version.
 * @return string
 */
function igp_pro_read_block_schema_version( string $schema_path, string $fallback = 'v1' ): string {
	if ( '' === $schema_path || ! file_exists( $schema_path ) ) {
		return sanitize_text_field( $fallback );
	}
	$contents = file_get_contents( $schema_path );
	if ( false === $contents || '' === $contents ) {
		return sanitize_text_field( $fallback );
	}
	$schema = json_decode( $contents, true );
	if ( ! is_array( $schema ) || empty( $schema['version'] ) || ! is_scalar( $schema['version'] ) ) {
		return sanitize_text_field( $fallback );
	}
	return sanitize_text_field( (string) $schema['version'] );
}

/**
 * Return canonical block definitions for Phase 1 + Phase 2.
 *
 * @return array[]
 */
function igp_pro_get_default_block_definitions(): array {
	return array(
		array(
			'id'          => 'hero',
			'title'       => __( 'IGP Hero', 'igp-pro' ),
			'description' => __( 'Above-the-fold travel hero.', 'igp-pro' ),
			'icon'        => 'cover-image',
			'category'    => 'core',
			'data_source' => 'manual',
			'folder'      => 'hero',
		),
		array(
			'id'          => 'section',
			'title'       => __( 'IGP Section Wrapper', 'igp-pro' ),
			'description' => __( 'Layout, spacing, and grouping wrapper.', 'igp-pro' ),
			'icon'        => 'layout',
			'category'    => 'layout',
			'data_source' => 'manual',
			'folder'      => 'section-wrapper',
		),
		array(
			'id'          => 'destination_cards',
			'title'       => __( 'IGP Destination Cards', 'igp-pro' ),
			'description' => __( 'Manual or query-driven destination cards.', 'igp-pro' ),
			'icon'        => 'location-alt',
			'category'    => 'discovery',
			'data_source' => 'hybrid',
			'folder'      => 'destination-cards',
		),
		array(
			'id'          => 'tour_cards',
			'title'       => __( 'IGP Tour Cards', 'igp-pro' ),
			'description' => __( 'Query-driven tour cards.', 'igp-pro' ),
			'icon'        => 'tickets-alt',
			'category'    => 'discovery',
			'data_source' => 'query',
			'folder'      => 'tour-cards',
		),
		array(
			'id'          => 'featured_listings',
			'title'       => __( 'IGP Featured Listings', 'igp-pro' ),
			'description' => __( 'Featured tours and destinations.', 'igp-pro' ),
			'icon'        => 'star-filled',
			'category'    => 'discovery',
			'data_source' => 'hybrid',
			'folder'      => 'featured-listings',
		),
		array(
			'id'          => 'cta',
			'title'       => __( 'IGP CTA', 'igp-pro' ),
			'description' => __( 'Conversion-focused call to action.', 'igp-pro' ),
			'icon'        => 'megaphone',
			'category'    => 'conversion',
			'data_source' => 'manual',
			'folder'      => 'cta',
		),
		array(
			'id'          => 'itinerary',
			'title'       => __( 'IGP Itinerary', 'igp-pro' ),
			'description' => __( 'Structured day-wise itinerary.', 'igp-pro' ),
			'icon'        => 'calendar-alt',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'itinerary',
		),
		array(
			'id'          => 'gallery',
			'title'       => __( 'IGP Gallery', 'igp-pro' ),
			'description' => __( 'Travel image gallery.', 'igp-pro' ),
			'icon'        => 'format-gallery',
			'category'    => 'media',
			'data_source' => 'manual',
			'folder'      => 'gallery',
		),
		array(
			'id'          => 'faq',
			'title'       => __( 'IGP FAQ', 'igp-pro' ),
			'description' => __( 'Frequently asked questions.', 'igp-pro' ),
			'icon'        => 'editor-help',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'faq',
		),
		array(
			'id'          => 'trust',
			'title'       => __( 'IGP Trust / Social Proof', 'igp-pro' ),
			'description' => __( 'Testimonials, badges, and social proof.', 'igp-pro' ),
			'icon'        => 'groups',
			'category'    => 'conversion',
			'data_source' => 'manual',
			'folder'      => 'trust',
		),
		array(
			'id'          => 'pricing_summary',
			'title'       => __( 'IGP Pricing Summary', 'igp-pro' ),
			'description' => __( 'Display-only pricing summary.', 'igp-pro' ),
			'icon'        => 'money-alt',
			'category'    => 'conversion',
			'data_source' => 'manual',
			'folder'      => 'pricing-summary',
		),
		array(
			'id'          => 'rich_text',
			'title'       => __( 'IGP Rich Text', 'igp-pro' ),
			'description' => __( 'Schema-driven formatted content section.', 'igp-pro' ),
			'icon'        => 'editor-alignleft',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'rich-text',
		),
		array(
			'id'          => 'breadcrumb',
			'title'       => __( 'IGP Breadcrumb', 'igp-pro' ),
			'description' => __( 'Structured breadcrumb navigation.', 'igp-pro' ),
			'icon'        => 'arrow-right-alt2',
			'category'    => 'navigation',
			'data_source' => 'hybrid',
			'folder'      => 'breadcrumb',
		),
		array(
			'id'          => 'map',
			'title'       => __( 'IGP Map', 'igp-pro' ),
			'description' => __( 'Destination map embed or location link.', 'igp-pro' ),
			'icon'        => 'location',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'map',
		),
		array(
			'id'          => 'icon_list',
			'title'       => __( 'IGP Icon List', 'igp-pro' ),
			'description' => __( 'Icon-led list of inclusions or highlights.', 'igp-pro' ),
			'icon'        => 'editor-ul',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'icon-list',
		),
		array(
			'id'          => 'stats',
			'title'       => __( 'IGP Stats / Highlights', 'igp-pro' ),
			'description' => __( 'Metric and highlight cards.', 'igp-pro' ),
			'icon'        => 'chart-bar',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'stats',
		),
		array(
			'id'          => 'tabs',
			'title'       => __( 'IGP Tabs', 'igp-pro' ),
			'description' => __( 'Tabbed structured content.', 'igp-pro' ),
			'icon'        => 'index-card',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'tabs',
		),
		array(
			'id'          => 'accordions',
			'title'       => __( 'IGP Accordions', 'igp-pro' ),
			'description' => __( 'Expandable structured content.', 'igp-pro' ),
			'icon'        => 'menu-alt3',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'accordions',
		),

		array(
			'id'          => 'tour_facts',
			'title'       => __( 'IGP Tour Facts / Quick Info', 'igp-pro' ),
			'description' => __( 'Structured quick facts for a tour package.', 'igp-pro' ),
			'icon'        => 'info-outline',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'tour-facts',
		),
		array(
			'id'          => 'inclusions_exclusions',
			'title'       => __( 'IGP Inclusions / Exclusions', 'igp-pro' ),
			'description' => __( 'Clear lists of included and excluded package items.', 'igp-pro' ),
			'icon'        => 'yes-alt',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'inclusions-exclusions',
		),
		array(
			'id'          => 'departure_dates',
			'title'       => __( 'IGP Departure Dates / Availability', 'igp-pro' ),
			'description' => __( 'Upcoming departures, availability status, and booking actions.', 'igp-pro' ),
			'icon'        => 'calendar-alt',
			'category'    => 'conversion',
			'data_source' => 'manual',
			'folder'      => 'departure-dates',
		),
		array(
			'id'          => 'package_tiers',
			'title'       => __( 'IGP Package Tiers / Price Comparison', 'igp-pro' ),
			'description' => __( 'Compare package tiers, features, and pricing.', 'igp-pro' ),
			'icon'        => 'list-view',
			'category'    => 'conversion',
			'data_source' => 'manual',
			'folder'      => 'package-tiers',
		),
		array(
			'id'          => 'reviews_summary',
			'title'       => __( 'IGP Reviews Summary / Aggregate Trust', 'igp-pro' ),
			'description' => __( 'Aggregate rating, review summary, and testimonial cards.', 'igp-pro' ),
			'icon'        => 'star-filled',
			'category'    => 'conversion',
			'data_source' => 'manual',
			'folder'      => 'reviews-summary',
		),
		array(
			'id'          => 'visa_requirements',
			'title'       => __( 'IGP Visa / Travel Requirements', 'igp-pro' ),
			'description' => __( 'Structured visa, entry, document, and travel requirement guidance.', 'igp-pro' ),
			'icon'        => 'id-alt',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'visa-requirements',
		),
		array(
			'id'          => 'best_time_to_visit',
			'title'       => __( 'IGP Best Time to Visit', 'igp-pro' ),
			'description' => __( 'Seasonal travel guidance for destinations and tours.', 'igp-pro' ),
			'icon'        => 'cloud',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'best-time-to-visit',
		),
		array(
			'id'          => 'route_timeline',
			'title'       => __( 'IGP Route / Stops Timeline', 'igp-pro' ),
			'description' => __( 'Lean route and stop timeline for trips and itineraries.', 'igp-pro' ),
			'icon'        => 'location-alt',
			'category'    => 'content',
			'data_source' => 'manual',
			'folder'      => 'route-timeline',
		),
		array(
			'id'          => 'expert_box',
			'title'       => __( 'IGP Expert / Travel Consultant Box', 'igp-pro' ),
			'description' => __( 'Consultant profile and assisted-trip CTA.', 'igp-pro' ),
			'icon'        => 'businessperson',
			'category'    => 'conversion',
			'data_source' => 'manual',
			'folder'      => 'expert-box',
		),
		array(
			'id'          => 'sticky_booking_cta',
			'title'       => __( 'IGP Sticky Booking CTA', 'igp-pro' ),
			'description' => __( 'Accessible booking CTA with no heavy frontend JavaScript.', 'igp-pro' ),
			'icon'        => 'megaphone',
			'category'    => 'conversion',
			'data_source' => 'manual',
			'folder'      => 'sticky-booking-cta',
		),
		array(
			'id'          => 'nearby_attractions',
			'title'       => __( 'IGP Nearby Attractions', 'igp-pro' ),
			'description' => __( 'Nearby places and relationship-aware destination discovery.', 'igp-pro' ),
			'icon'        => 'location',
			'category'    => 'discovery',
			'data_source' => 'hybrid',
			'folder'      => 'nearby-attractions',
		),
		array(
			'id'          => 'brochure_cta',
			'title'       => __( 'IGP Download Brochure CTA', 'igp-pro' ),
			'description' => __( 'Downloadable brochure CTA for trip PDFs and quote capture.', 'igp-pro' ),
			'icon'        => 'media-document',
			'category'    => 'conversion',
			'data_source' => 'manual',
			'folder'      => 'brochure-cta',
		),
		array(
			'id'          => 'related_tours',
			'title'       => __( 'IGP Related Tours', 'igp-pro' ),
			'description' => __( 'Related tour query block.', 'igp-pro' ),
			'icon'        => 'admin-links',
			'category'    => 'discovery',
			'data_source' => 'query',
			'folder'      => 'related-tours',
		),
		array(
			'id'          => 'related_destinations',
			'title'       => __( 'IGP Related Destinations', 'igp-pro' ),
			'description' => __( 'Related destination query block.', 'igp-pro' ),
			'icon'        => 'admin-links',
			'category'    => 'discovery',
			'data_source' => 'query',
			'folder'      => 'related-destinations',
		),
	);
}

/**
 * Register canonical blocks in the central registry.
 */
function igp_pro_register_core_blocks(): void {
	global $igp_pro_block_registry;

	static $registering = false;

	if ( $registering ) {
		return;
	}

	if ( ! is_array( $igp_pro_block_registry ?? null ) ) {
		$igp_pro_block_registry = array();
	}

	$registering = true;

	foreach ( igp_pro_get_default_block_definitions() as $definition ) {
		$block_id = isset( $definition['id'] ) ? sanitize_key( (string) $definition['id'] ) : '';

		if ( '' === $block_id || isset( $igp_pro_block_registry[ $block_id ] ) ) {
			continue;
		}

		$folder = isset( $definition['folder'] ) ? (string) $definition['folder'] : $block_id;

		igp_pro_register_block_type(
			array_merge(
				$definition,
				array(
					'version'         => igp_pro_read_block_schema_version( igp_pro_path( 'includes/blocks/' . $folder . '/schema.json' ), 'v1' ),
					'schema_path'     => igp_pro_path( 'includes/blocks/' . $folder . '/schema.json' ),
					'render_path'     => igp_pro_path( 'includes/blocks/' . $folder . '/render.php' ),
					'render_callback' => 'igp_pro_render_block',
				)
			)
		);
	}

	$registering = false;
}

/**
 * Register a block definition in the central registry.
 *
 * @param array $definition Block definition.
 * @return true|WP_Error
 */
function igp_pro_register_block_type( array $definition ) {
	global $igp_pro_block_registry;

	if ( ! is_array( $igp_pro_block_registry ?? null ) ) {
		$igp_pro_block_registry = array();
	}

	$block_id = isset( $definition['id'] ) ? igp_pro_normalize_block_id( (string) $definition['id'] ) : '';

	if ( is_wp_error( $block_id ) ) {
		return $block_id;
	}

	if ( isset( $igp_pro_block_registry[ $block_id ] ) ) {
		return new WP_Error(
			'igp_pro_duplicate_block_id',
			sprintf(
				/* translators: %s: block ID. */
				__( 'Duplicate IGP Pro block ID: %s', 'igp-pro' ),
				$block_id
			)
		);
	}

	$schema_path = isset( $definition['schema_path'] ) ? (string) $definition['schema_path'] : '';
	$render_path = isset( $definition['render_path'] ) ? (string) $definition['render_path'] : '';

	if ( '' === $schema_path || ! file_exists( $schema_path ) ) {
		return new WP_Error( 'igp_pro_missing_schema', __( 'Block schema path is missing or invalid.', 'igp-pro' ) );
	}

	if ( '' === $render_path || ! file_exists( $render_path ) ) {
		return new WP_Error( 'igp_pro_missing_render_path', __( 'Block render path is missing or invalid.', 'igp-pro' ) );
	}


	$schema_version = igp_pro_read_block_schema_version( $schema_path, isset( $definition['version'] ) ? (string) $definition['version'] : 'v1' );
	if ( '' !== $schema_version ) {
		$definition['version'] = $schema_version;
	}

	$igp_pro_block_registry[ $block_id ] = array_merge(
		array(
			'id'              => $block_id,
			'version'         => 'v1',
			'category'        => 'core',
			'data_source'     => 'manual',
			'schema_path'     => $schema_path,
			'render_path'     => $render_path,
			'render_callback' => 'igp_pro_render_block',
			'title'           => igp_pro_block_id_to_title( $block_id ),
			'description'     => '',
			'icon'            => 'screenoptions',
		),
		$definition,
		array( 'id' => $block_id )
	);

	return true;
}

/**
 * Return all registered block definitions.
 *
 * @return array
 */
function igp_pro_get_block_registry(): array {
	global $igp_pro_block_registry;

	if ( empty( $igp_pro_block_registry ) ) {
		igp_pro_register_core_blocks();
	}

	return is_array( $igp_pro_block_registry ) ? $igp_pro_block_registry : array();
}

/**
 * Return a single registered block definition.
 *
 * @param string $block_id Block ID.
 * @return array|null
 */
function igp_pro_get_registered_block( string $block_id ): ?array {
	global $igp_pro_block_registry;

	$block_id = sanitize_key( $block_id );

	if ( ! is_array( $igp_pro_block_registry ?? null ) || ! isset( $igp_pro_block_registry[ $block_id ] ) ) {
		igp_pro_register_core_blocks();
	}

	return is_array( $igp_pro_block_registry ?? null ) ? ( $igp_pro_block_registry[ $block_id ] ?? null ) : null;
}

/**
 * Convert schema fields to WordPress block attribute definitions.
 *
 * @param array $schema Block schema.
 * @return array
 */
function igp_pro_schema_to_wp_attributes( array $schema ): array {
	$attributes = array();
	$fields     = isset( $schema['fields'] ) && is_array( $schema['fields'] ) ? $schema['fields'] : array();
	$defaults   = isset( $schema['defaults'] ) && is_array( $schema['defaults'] ) ? $schema['defaults'] : array();

	foreach ( $fields as $name => $field ) {
		if ( ! is_array( $field ) ) {
			continue;
		}

		$type       = isset( $field['type'] ) ? (string) $field['type'] : 'string';
		$attribute  = array();
		$has_default = array_key_exists( $name, $defaults ) || array_key_exists( 'default', $field );
		$default    = array_key_exists( $name, $defaults ) ? $defaults[ $name ] : ( $field['default'] ?? null );

		switch ( $type ) {
			case 'boolean':
				$attribute['type'] = 'boolean';
				break;
			case 'number':
				$attribute['type'] = 'number';
				break;
			case 'object':
			case 'image':
				$attribute['type'] = 'object';
				break;
			case 'repeater':
			case 'array':
				$attribute['type'] = 'array';
				break;
			case 'relationship':
				$attribute['type'] = 'array';
				break;
			default:
				$attribute['type'] = 'string';
				break;
		}

		if ( $has_default ) {
			$attribute['default'] = $default;
		} elseif ( 'array' === $attribute['type'] ) {
			$attribute['default'] = array();
		} elseif ( 'object' === $attribute['type'] ) {
			$attribute['default'] = array();
		} elseif ( 'boolean' === $attribute['type'] ) {
			$attribute['default'] = false;
		} elseif ( 'number' === $attribute['type'] ) {
			$attribute['default'] = 0;
		} else {
			$attribute['default'] = '';
		}

		$attributes[ $name ] = $attribute;
	}

	return $attributes;
}

/**
 * Register central registry blocks as server-rendered Gutenberg blocks.
 */
function igp_pro_register_wordpress_blocks(): void {
	if ( ! function_exists( 'register_block_type' ) || ! class_exists( 'WP_Block_Type_Registry' ) ) {
		return;
	}

	foreach ( igp_pro_get_block_registry() as $block_id => $definition ) {
		$block_name = 'igp-pro/' . igp_pro_block_id_to_wp_slug( $block_id );

		if ( WP_Block_Type_Registry::get_instance()->is_registered( $block_name ) ) {
			continue;
		}

		$schema     = igp_pro_get_block_schema( $definition );
		$attributes = is_wp_error( $schema ) ? array() : igp_pro_schema_to_wp_attributes( $schema );

		register_block_type(
			$block_name,
			array(
				'api_version'     => 2,
				'title'           => $definition['title'] ?? igp_pro_block_id_to_title( $block_id ),
				'category'        => isset( $schema['category'] ) ? sanitize_key( (string) $schema['category'] ) : 'widgets',
				'attributes'      => $attributes,
				'supports'        => array(
					'html' => false,
				),
				'render_callback' => static function ( array $attributes = array(), string $content = '' ) use ( $block_id ): string {
					return igp_pro_render_block(
						$block_id,
						$attributes,
						array(
							'content' => $content,
						)
					);
				},
			)
		);
	}
}

/**
 * Prepare block definitions for editor-side registration.
 *
 * @return array
 */
function igp_pro_get_editor_block_definitions(): array {
	$editor_blocks = array();

	foreach ( igp_pro_get_block_registry() as $block_id => $definition ) {
		$schema = igp_pro_get_block_schema( $definition );

		if ( is_wp_error( $schema ) ) {
			continue;
		}

		$editor_blocks[] = array(
			'id'          => $block_id,
			'name'        => 'igp-pro/' . igp_pro_block_id_to_wp_slug( $block_id ),
			'title'       => $definition['title'] ?? igp_pro_block_id_to_title( $block_id ),
			'description' => $definition['description'] ?? '',
			'icon'        => $definition['icon'] ?? 'screenoptions',
			'category'    => isset( $schema['category'] ) ? sanitize_key( (string) $schema['category'] ) : 'widgets',
			'keywords'    => array( 'igp', 'travel', str_replace( '_', ' ', $block_id ) ),
			'attributes'  => igp_pro_schema_to_wp_attributes( $schema ),
			'schema'      => $schema,
		);
	}

	return $editor_blocks;
}



/**
 * Register IGP Pro block editor categories used by schema metadata.
 *
 * @param array<int,array<string,string>> $categories Existing categories.
 * @return array<int,array<string,string>>
 */
function igp_pro_register_block_categories( array $categories ): array {
	$existing = array();
	foreach ( $categories as $category ) {
		if ( is_array( $category ) && isset( $category['slug'] ) ) {
			$existing[] = (string) $category['slug'];
		}
	}

	$igp_categories = array(
		array( 'slug' => 'core', 'title' => __( 'IGP Core', 'igp-pro' ) ),
		array( 'slug' => 'layout', 'title' => __( 'IGP Layout', 'igp-pro' ) ),
		array( 'slug' => 'content', 'title' => __( 'IGP Content', 'igp-pro' ) ),
		array( 'slug' => 'conversion', 'title' => __( 'IGP Conversion', 'igp-pro' ) ),
		array( 'slug' => 'discovery', 'title' => __( 'IGP Discovery', 'igp-pro' ) ),
		array( 'slug' => 'navigation', 'title' => __( 'IGP Navigation', 'igp-pro' ) ),
		array( 'slug' => 'media', 'title' => __( 'IGP Media', 'igp-pro' ) ),
		array( 'slug' => 'trust', 'title' => __( 'IGP Trust', 'igp-pro' ) ),
	);

	foreach ( array_reverse( $igp_categories ) as $category ) {
		if ( ! in_array( $category['slug'], $existing, true ) ) {
			array_unshift( $categories, $category );
		}
	}

	return $categories;
}
/**
 * Enqueue editor-side block registration for IGP Pro blocks.
 */
function igp_pro_enqueue_block_editor_assets(): void {
	if ( ! function_exists( 'wp_register_script' ) || ! function_exists( 'wp_add_inline_script' ) ) {
		return;
	}

	$handle = 'igp-pro-blocks-editor';

	wp_register_script(
		$handle,
		false,
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
		IGP_PRO_VERSION,
		true
	);

	wp_enqueue_script( $handle );

	$definitions = wp_json_encode( igp_pro_get_editor_block_definitions(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	wp_add_inline_script(
		$handle,
		'window.igpProBlockDefinitions = ' . ( $definitions ? $definitions : '[]' ) . ';',
		'before'
	);

	$editor_posts = function_exists( 'igp_pro_get_content_editor_post_options' ) ? igp_pro_get_content_editor_post_options( '', 100 ) : array();
	$posts_json   = wp_json_encode( $editor_posts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	wp_add_inline_script(
		$handle,
		'window.igpProBlockEditorPosts = ' . ( $posts_json ? $posts_json : '[]' ) . ';',
		'before'
	);

	wp_add_inline_script(
		$handle,
		<<<'JS'
(function (blocks, blockEditor, components, element, i18n, ServerSideRender) {
	if (!blocks || !blockEditor || !components || !element || !i18n) {
		return;
	}

	var el = element.createElement;
	var __ = i18n.__;
	var useBlockProps = blockEditor.useBlockProps;
	var useInnerBlocksProps = blockEditor.useInnerBlocksProps;
	var InnerBlocks = blockEditor.InnerBlocks;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var TextareaControl = components.TextareaControl;
	var ToggleControl = components.ToggleControl;
	var SelectControl = components.SelectControl;
	var Button = components.Button;
	var Notice = components.Notice;
	var CheckboxControl = components.CheckboxControl;
	ServerSideRender = ServerSideRender && (ServerSideRender.default || ServerSideRender);
	var blocksToRegister = window.igpProBlockDefinitions || [];
	var availablePosts = window.igpProBlockEditorPosts || [];

	function fieldLabel(name) {
		return String(name || '').replace(/_/g, ' ').replace(/-/g, ' ').replace(/\b\w/g, function (match) { return match.toUpperCase(); });
	}

	function clone(value) {
		try { return JSON.parse(JSON.stringify(value === undefined ? null : value)); } catch (error) { return value; }
	}

	function normalizeExpectedPostType(value) {
		value = String(value || '').trim();
		if (value === 'tour') { return ['tour', 'igp_tour']; }
		if (value === 'destination') { return ['destination', 'igp_destination']; }
		if (value === 'page') { return ['page']; }
		return [];
	}

	function postTypeMatches(post, expected) {
		var allowed = normalizeExpectedPostType(expected);
		if (!allowed.length || expected === 'any') { return true; }
		return allowed.indexOf(String(post.post_type || '')) !== -1;
	}

	function postLabel(post) {
		return '[' + post.post_type + '] ' + post.title + ' #' + post.id + (post.status ? ' · ' + post.status : '');
	}

	function setDeep(attributes, path, value, setAttributes) {
		var rootName = path[0];
		var nextRoot = clone(attributes[rootName]);
		if (!nextRoot || typeof nextRoot !== 'object') { nextRoot = typeof path[1] === 'number' ? [] : {}; }
		var target = nextRoot;
		for (var i = 1; i < path.length - 1; i += 1) {
			var key = path[i];
			if (!target[key] || typeof target[key] !== 'object') { target[key] = typeof path[i + 1] === 'number' ? [] : {}; }
			target = target[key];
		}
		target[path[path.length - 1]] = value;
		var update = {};
		update[rootName] = nextRoot;
		setAttributes(update);
	}

	function renderField(name, field, value, setValue, path, attributes, setAttributes) {
		field = field || {};
		path = path || [name];
		var type = field.type || 'string';
		var label = field.label || fieldLabel(name);

		if (type === 'boolean') {
			return el(ToggleControl, { key: path.join('.'), label: label, checked: !!value, onChange: function (next) { setValue(!!next); } });
		}

		if (type === 'enum') {
			var values = field.values || [];
			return el(SelectControl, { key: path.join('.'), label: label, value: value || field.default || (values[0] || ''), options: values.map(function (item) { return { label: fieldLabel(item), value: item }; }), onChange: setValue });
		}

		if (type === 'number') {
			return el(TextControl, { key: path.join('.'), label: label, type: 'number', min: field.min, max: field.max, value: value === undefined || value === null ? '' : value, onChange: function (next) { setValue(next === '' ? '' : Number(next)); } });
		}

		if (type === 'text') {
			return el(TextareaControl, { key: path.join('.'), label: label, value: value || '', onChange: setValue });
		}

		if (type === 'image') {
			var imageValue = value && typeof value === 'object' && !Array.isArray(value) ? Object.assign({ url: '', alt: '', caption: '', pending: false, prompt: '' }, value) : { url: value || '', alt: '', caption: '', pending: false, prompt: '' };
			return el(PanelBody, { key: path.join('.'), title: label, initialOpen: false },
				el(TextControl, { label: __('Image URL', 'igp-pro'), value: imageValue.url || '', onChange: function (next) { setValue(Object.assign({}, imageValue, { url: next, pending: false })); } }),
				el(TextControl, { label: __('Alt text', 'igp-pro'), value: imageValue.alt || '', onChange: function (next) { setValue(Object.assign({}, imageValue, { alt: next })); } }),
				el(TextControl, { label: __('Caption', 'igp-pro'), value: imageValue.caption || '', onChange: function (next) { setValue(Object.assign({}, imageValue, { caption: next })); } }),
				el(ToggleControl, { label: __('Pending media requirement', 'igp-pro'), checked: !!imageValue.pending, onChange: function (next) { setValue(Object.assign({}, imageValue, { pending: !!next })); } }),
				imageValue.pending ? el(TextareaControl, { label: __('Media prompt', 'igp-pro'), value: imageValue.prompt || '', onChange: function (next) { setValue(Object.assign({}, imageValue, { prompt: next })); } }) : null
			);
		}

		if (type === 'object') {
			var objectValue = value && typeof value === 'object' && !Array.isArray(value) ? value : {};
			var fields = field.fields || {};
			return el(PanelBody, { key: path.join('.'), title: label, initialOpen: false },
				Object.keys(fields).map(function (childName) {
					return renderField(childName, fields[childName], objectValue[childName], function (next) {
						var nextObject = Object.assign({}, objectValue);
						nextObject[childName] = next;
						setValue(nextObject);
					}, path.concat([childName]), attributes, setAttributes);
				})
			);
		}

		if (type === 'repeater' || type === 'array') {
			var items = Array.isArray(value) ? value.slice() : [];
			var itemFields = field.fields || null;
			function updateItems(nextItems) { setValue(nextItems); }
			function makeItem() {
				var item = {};
				Object.keys(itemFields || {}).forEach(function (childName) {
					var child = itemFields[childName] || {};
					item[childName] = child.default !== undefined ? clone(child.default) : (child.type === 'boolean' ? false : (child.type === 'number' ? 0 : (child.type === 'array' || child.type === 'repeater' || child.type === 'relationship' ? [] : '')));
				});
				item.id = item.id || 'item_' + Date.now();
				return item;
			}
			return el(PanelBody, { key: path.join('.'), title: label, initialOpen: false },
				items.map(function (item, index) {
					if (!itemFields || !Object.keys(itemFields).length) {
						return el('div', { key: path.join('.') + '.' + index, className: 'igp-pro-block-editor-repeater-row' },
							el(TextControl, { label: label + ' #' + (index + 1), value: item || '', onChange: function (next) { items[index] = next; updateItems(items.slice()); } }),
							el(Button, { isSmall: true, onClick: function () { items.splice(index, 1); updateItems(items.slice()); } }, __('Remove', 'igp-pro'))
						);
					}
					return el(PanelBody, { key: path.join('.') + '.' + index, title: label + ' #' + (index + 1), initialOpen: false },
						Object.keys(itemFields).map(function (childName) {
							return renderField(childName, itemFields[childName], item && item[childName], function (next) {
								var nextItem = Object.assign({}, item || {});
								nextItem[childName] = next;
								items[index] = nextItem;
								updateItems(items.slice());
							}, path.concat([index, childName]), attributes, setAttributes);
						}),
						el('div', { className: 'igp-pro-block-editor-repeater-actions' },
							el(Button, { isSmall: true, onClick: function () { if (index > 0) { var moved = items.splice(index, 1)[0]; items.splice(index - 1, 0, moved); updateItems(items.slice()); } } }, __('Up', 'igp-pro')),
							el(Button, { isSmall: true, onClick: function () { if (index < items.length - 1) { var moved = items.splice(index, 1)[0]; items.splice(index + 1, 0, moved); updateItems(items.slice()); } } }, __('Down', 'igp-pro')),
							el(Button, { isSmall: true, onClick: function () { items.splice(index + 1, 0, clone(item)); updateItems(items.slice()); } }, __('Duplicate', 'igp-pro')),
							el(Button, { isDestructive: true, isSmall: true, onClick: function () { items.splice(index, 1); updateItems(items.slice()); } }, __('Remove', 'igp-pro'))
						)
					);
				}),
				el(Button, { isSecondary: true, onClick: function () { items.push(itemFields && Object.keys(itemFields).length ? makeItem() : ''); updateItems(items.slice()); } }, __('Add item', 'igp-pro'))
			);
		}

		if (type === 'relationship') {
			var ids = Array.isArray(value) ? value.map(function (id) { return parseInt(id, 10); }).filter(Boolean) : [];
			var expected = field.post_type || 'any';
			var candidates = availablePosts.filter(function (post) { return postTypeMatches(post, expected); }).slice(0, 80);
			var selectedMap = {};
			ids.forEach(function (id) { selectedMap[String(id)] = true; });
			return el(PanelBody, { key: path.join('.'), title: label, initialOpen: false },
				el('p', {}, __('Select valid posts. Wrong post types are rejected on save.', 'igp-pro')),
				candidates.map(function (post) {
					var id = parseInt(post.id, 10);
					return el(CheckboxControl, { key: 'rel-' + id, label: postLabel(post), checked: !!selectedMap[String(id)], onChange: function (checked) {
						var next = ids.slice();
						if (checked && next.indexOf(id) === -1) { next.push(id); }
						if (!checked) { next = next.filter(function (selectedId) { return selectedId !== id; }); }
						setValue(next);
					} });
				}),
				ids.filter(function (id) { return !availablePosts.some(function (post) { return parseInt(post.id, 10) === id; }); }).map(function (id) { return el(Notice, { key: 'missing-' + id, status: 'warning', isDismissible: false }, __('Selected post not loaded or deleted: #', 'igp-pro') + id); })
			);
		}

		return el(TextControl, { key: path.join('.'), label: label, value: value || '', onChange: setValue });
	}

	function getBlockNotice(def, attrs) {
		var required = (((def.schema || {}).validation || {}).required) || [];
		if (!required.length) { return null; }
		var missing = required.filter(function (name) {
			var value = attrs[name];
			if (value && typeof value === 'object' && !Array.isArray(value) && value.url !== undefined) { return !value.url; }
			return value === undefined || value === null || value === '' || (Array.isArray(value) && !value.length);
		});
		return missing.length ? missing.map(fieldLabel).join(', ') : null;
	}

	blocksToRegister.forEach(function (def) {
		if (!def || !def.name || blocks.getBlockType(def.name)) { return; }
		blocks.registerBlockType(def.name, {
			apiVersion: 2,
			title: def.title,
			description: def.description || '',
			icon: def.icon || 'screenoptions',
			category: def.category || 'widgets',
			keywords: def.keywords || ['igp', 'travel'],
			attributes: def.attributes || {},
			supports: { html: false },
			edit: function (props) {
				var attrs = props.attributes || {};
				var setAttributes = props.setAttributes;
				var fields = (def.schema || {}).fields || {};
				var missing = getBlockNotice(def, attrs);
				var blockProps = useBlockProps({ className: 'igp-pro-editor-block igp-pro-editor-block--' + def.id });
				var controls = Object.keys(fields).map(function (fieldName) {
					return renderField(fieldName, fields[fieldName], attrs[fieldName], function (next) {
						var update = {};
						update[fieldName] = next;
						setAttributes(update);
					}, [fieldName], attrs, setAttributes);
				});
				if (def.id === 'section') {
					var innerBlocksProps = useInnerBlocksProps({ className: 'igp-pro-editor-innerblocks' }, { templateLock: false });
					return el('div', blockProps,
						el(InspectorControls, {}, el(PanelBody, { title: __('Block Settings', 'igp-pro'), initialOpen: true }, controls)),
						missing ? el(Notice, { status: 'warning', isDismissible: false }, __('Required fields missing: ', 'igp-pro') + missing) : null,
						el('strong', {}, def.title),
						el('div', innerBlocksProps)
					);
				}
				return el('div', blockProps,
					el(InspectorControls, {}, el(PanelBody, { title: __('Block Settings', 'igp-pro'), initialOpen: true }, controls)),
					missing ? el(Notice, { status: 'warning', isDismissible: false }, __('Required fields missing: ', 'igp-pro') + missing) : null,
					ServerSideRender ? el(ServerSideRender, { block: def.name, attributes: attrs }) : el('p', {}, def.title)
				);
			},
			save: function () {
				if (def.id === 'section' && InnerBlocks) { return el(InnerBlocks.Content); }
				return null;
			}
		});
	});
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender);
JS
	);
}
