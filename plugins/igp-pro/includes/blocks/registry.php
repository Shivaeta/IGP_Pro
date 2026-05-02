<?php
/**
 * Central block registry.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Phase 1 core blocks.
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

	if ( isset( $igp_pro_block_registry['hero'] ) ) {
		return;
	}

	$registering = true;

	igp_pro_register_block_type(
		array(
			'id'              => 'hero',
			'version'         => 'v1',
			'category'        => 'core',
			'data_source'     => 'manual',
			'schema_path'     => igp_pro_path( 'includes/blocks/hero/schema.json' ),
			'render_path'     => igp_pro_path( 'includes/blocks/hero/render.php' ),
			'render_callback' => 'igp_pro_render_block',
		)
	);

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
 * Register central registry blocks as server-rendered Gutenberg blocks.
 */
function igp_pro_register_wordpress_blocks(): void {
	if ( ! function_exists( 'register_block_type' ) || ! class_exists( 'WP_Block_Type_Registry' ) ) {
		return;
	}

	foreach ( igp_pro_get_block_registry() as $block_id => $definition ) {
		$block_name = 'igp-pro/' . $block_id;

		if ( WP_Block_Type_Registry::get_instance()->is_registered( $block_name ) ) {
			continue;
		}

		register_block_type(
			$block_name,
			array(
				'api_version'     => 2,
				'title'           => ucwords( str_replace( array( '-', '_' ), ' ', $block_id ) ),
				'category'        => 'widgets',
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
