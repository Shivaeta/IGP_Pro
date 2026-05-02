<?php
/**
 * Central block registry.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

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
					'version'         => 'v1',
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
				'category'        => 'widgets',
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
			'category'    => 'widgets',
			'keywords'    => array( 'igp', 'travel', str_replace( '_', ' ', $block_id ) ),
			'attributes'  => igp_pro_schema_to_wp_attributes( $schema ),
			'schema'      => $schema,
		);
	}

	return $editor_blocks;
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
	var Notice = components.Notice;
	ServerSideRender = ServerSideRender && (ServerSideRender.default || ServerSideRender);
	var blocksToRegister = window.igpProBlockDefinitions || [];

	function fieldLabel(name) {
		return String(name || '').replace(/_/g, ' ').replace(/\b\w/g, function (match) { return match.toUpperCase(); });
	}

	function jsonValue(value, fallback) {
		try {
			return JSON.stringify(value === undefined ? fallback : value, null, 2);
		} catch (error) {
			return JSON.stringify(fallback, null, 2);
		}
	}

	function renderField(name, field, value, setValue, path) {
		field = field || {};
		path = path || name;
		var type = field.type || 'string';
		var label = field.label || fieldLabel(name);

		if (type === 'boolean') {
			return el(ToggleControl, {
				key: path,
				label: label,
				checked: !!value,
				onChange: function (next) { setValue(!!next); }
			});
		}

		if (type === 'enum') {
			var values = field.values || [];
			return el(SelectControl, {
				key: path,
				label: label,
				value: value || field.default || (values[0] || ''),
				options: values.map(function (item) { return { label: fieldLabel(item), value: item }; }),
				onChange: setValue
			});
		}

		if (type === 'number') {
			return el(TextControl, {
				key: path,
				label: label,
				type: 'number',
				min: field.min,
				max: field.max,
				value: value === undefined || value === null ? '' : value,
				onChange: function (next) {
					setValue(next === '' ? '' : Number(next));
				}
			});
		}

		if (type === 'text') {
			return el(TextareaControl, {
				key: path,
				label: label,
				value: value || '',
				onChange: setValue
			});
		}

		if (type === 'image') {
			var imageValue = value && typeof value === 'object' ? value : { url: value || '', alt: '' };
			return el(PanelBody, { key: path, title: label, initialOpen: false },
				el(TextControl, {
					label: __('Image URL', 'igp-pro'),
					value: imageValue.url || '',
					onChange: function (next) { setValue(Object.assign({}, imageValue, { url: next })); }
				}),
				el(TextControl, {
					label: __('Alt text', 'igp-pro'),
					value: imageValue.alt || '',
					onChange: function (next) { setValue(Object.assign({}, imageValue, { alt: next })); }
				})
			);
		}

		if (type === 'object') {
			var objectValue = value && typeof value === 'object' && !Array.isArray(value) ? value : {};
			var fields = field.fields || {};
			return el(PanelBody, { key: path, title: label, initialOpen: false },
				Object.keys(fields).map(function (childName) {
					return renderField(childName, fields[childName], objectValue[childName], function (next) {
						var nextObject = Object.assign({}, objectValue);
						nextObject[childName] = next;
						setValue(nextObject);
					}, path + '.' + childName);
				})
			);
		}

		if (type === 'repeater' || type === 'array') {
			return el(TextareaControl, {
				key: path,
				label: label + ' JSON',
				help: __('Enter an array of objects. Invalid JSON is ignored until corrected.', 'igp-pro'),
				value: jsonValue(value, []),
				onChange: function (next) {
					try {
						var parsed = next.trim() === '' ? [] : JSON.parse(next);
						if (Array.isArray(parsed)) {
							setValue(parsed);
						}
					} catch (error) {}
				}
			});
		}

		if (type === 'relationship') {
			var relValue = Array.isArray(value) ? value.join(', ') : (value || '');
			return el(TextControl, {
				key: path,
				label: label + ' IDs',
				help: __('Enter one or more post IDs separated by commas, spaces, or new lines.', 'igp-pro'),
				value: relValue,
				onChange: function (next) {
					var ids = String(next || '')
						.split(/[^0-9]+/)
						.map(function (part) { return parseInt(part, 10); })
						.filter(function (id, index, arr) { return id > 0 && arr.indexOf(id) === index; });
					setValue(ids);
				}
			});
		}

		return el(TextControl, {
			key: path,
			label: label,
			value: value || '',
			onChange: setValue
		});
	}

	function getBlockNotice(def, attrs) {
		var required = (((def.schema || {}).validation || {}).required) || [];
		if (!required.length) {
			return null;
		}

		var missing = required.filter(function (name) {
			var value = attrs[name];
			if (value && typeof value === 'object' && !Array.isArray(value) && value.url !== undefined) {
				return !value.url;
			}
			return value === undefined || value === null || value === '' || (Array.isArray(value) && !value.length);
		});

		return missing.length ? missing.map(fieldLabel).join(', ') : null;
	}

	blocksToRegister.forEach(function (def) {
		if (!def || !def.name || blocks.getBlockType(def.name)) {
			return;
		}

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
				var schema = def.schema || {};
				var fields = schema.fields || {};
				var missing = getBlockNotice(def, attrs);
				var blockProps = useBlockProps({ className: 'igp-pro-editor-block igp-pro-editor-block--' + def.id });
				var controls = Object.keys(fields).map(function (fieldName) {
					return renderField(fieldName, fields[fieldName], attrs[fieldName], function (next) {
						var update = {};
						update[fieldName] = next;
						setAttributes(update);
					}, fieldName);
				});

				if (def.id === 'section') {
					var innerBlocksProps = useInnerBlocksProps(
						{ className: 'igp-pro-editor-innerblocks' },
						{ templateLock: false }
					);

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
				if (def.id === 'section' && InnerBlocks) {
					return el(InnerBlocks.Content);
				}
				return null;
			}
		});
	});
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender);
JS
	);
}
