<?php
/**
 * Feature flag service for IGP Pro V2 modules.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_FEATURE_FLAGS_OPTION' ) ) {
	define( 'IGP_PRO_FEATURE_FLAGS_OPTION', 'igp_pro_feature_flags' );
}

/**
 * Return the controlled V2 feature flag registry.
 *
 * All V2 subsystem flags default to disabled so the plugin can activate safely
 * before each subsystem is implemented and gate-tested.
 *
 * @return array<string,array{default:bool,label:string,description:string}>
 */
function igp_pro_get_feature_flag_definitions(): array {
	return array(
		'enable_relationship_layer'     => array(
			'default'     => false,
			'label'       => __( 'Relationship Layer', 'igp-pro' ),
			'description' => __( 'Enables V2 tour/destination relationship services and relationship-aware modules after implementation.', 'igp-pro' ),
		),
		'enable_semantic_outline'       => array(
			'default'     => false,
			'label'       => __( 'Semantic Outline', 'igp-pro' ),
			'description' => __( 'Enables V2 page-level H1 policy and semantic heading support after implementation.', 'igp-pro' ),
		),
		'enable_smart_block_variants'   => array(
			'default'     => false,
			'label'       => __( 'Smart Block Variants', 'igp-pro' ),
			'description' => __( 'Enables controlled V2 visual variants and block style support after implementation.', 'igp-pro' ),
		),
		'enable_brand_engine'           => array(
			'default'     => false,
			'label'       => __( 'Brand Engine', 'igp-pro' ),
			'description' => __( 'Enables V2 brand profiles, design tokens, and generated CSS after implementation.', 'igp-pro' ),
		),
		'enable_starter_templates'      => array(
			'default'     => false,
			'label'       => __( 'Starter Templates', 'igp-pro' ),
			'description' => __( 'Enables V2 starter content templates and controlled imports after implementation.', 'igp-pro' ),
		),
		'enable_media_optimizer'        => array(
			'default'     => false,
			'label'       => __( 'Media Optimizer', 'igp-pro' ),
			'description' => __( 'Enables V2 media inventory, audit, and optimization services after implementation.', 'igp-pro' ),
		),
		'enable_rank_math_bridge'       => array(
			'default'     => false,
			'label'       => __( 'Rank Math Bridge', 'igp-pro' ),
			'description' => __( 'Enables the optional V2 Rank Math bridge after implementation.', 'igp-pro' ),
		),
		'enable_link_whisper_bridge'    => array(
			'default'     => false,
			'label'       => __( 'Link Whisper Bridge', 'igp-pro' ),
			'description' => __( 'Enables the optional V2 Link Whisper companion bridge after implementation.', 'igp-pro' ),
		),
		'enable_mcp_bridge'             => array(
			'default'     => false,
			'label'       => __( 'MCP Bridge', 'igp-pro' ),
			'description' => __( 'Enables V2 MCP tooling after REST safety, permissions, validation, logging, and snapshots exist.', 'igp-pro' ),
		),
	);
}

/**
 * Return the default V2 feature flag values.
 *
 * @return array<string,bool>
 */
function igp_pro_get_default_feature_flags(): array {
	$defaults = array();

	foreach ( igp_pro_get_feature_flag_definitions() as $flag => $definition ) {
		$defaults[ $flag ] = (bool) ( $definition['default'] ?? false );
	}

	return $defaults;
}

/**
 * Sanitize a feature flag map against the controlled registry.
 *
 * Unknown flags are intentionally discarded. Missing flags receive safe defaults.
 *
 * @param mixed $flags Raw flag map.
 * @return array<string,bool>
 */
function igp_pro_sanitize_feature_flags( $flags ): array {
	$definitions = igp_pro_get_feature_flag_definitions();
	$stored      = is_array( $flags ) ? $flags : array();
	$sanitized   = array();

	foreach ( $definitions as $flag => $definition ) {
		$default = (bool) ( $definition['default'] ?? false );

		if ( array_key_exists( $flag, $stored ) ) {
			$value = $stored[ $flag ];
			$sanitized[ $flag ] = in_array( $value, array( true, 1, '1', 'yes', 'true', 'on' ), true );
		} else {
			$sanitized[ $flag ] = $default;
		}
	}

	return $sanitized;
}

/**
 * Register the default feature flag option without overwriting existing values.
 */
function igp_pro_register_default_feature_flags(): void {
	$existing = get_option( IGP_PRO_FEATURE_FLAGS_OPTION, null );

	if ( null === $existing ) {
		add_option( IGP_PRO_FEATURE_FLAGS_OPTION, igp_pro_get_default_feature_flags(), '', false );
		return;
	}

	$sanitized = igp_pro_sanitize_feature_flags( $existing );
	if ( $sanitized !== $existing ) {
		update_option( IGP_PRO_FEATURE_FLAGS_OPTION, $sanitized, false );
	}
}

/**
 * Get all controlled V2 feature flags.
 *
 * Required V2 helper.
 *
 * @return array<string,bool>
 */
function igp_get_feature_flags(): array {
	$stored = get_option( IGP_PRO_FEATURE_FLAGS_OPTION, array() );

	return igp_pro_sanitize_feature_flags( $stored );
}

/**
 * Determine whether a V2 feature flag is enabled.
 *
 * Required V2 helper.
 *
 * @param string $flag Feature flag slug.
 * @return bool
 */
function igp_feature_enabled( string $flag ): bool {
	$flag = sanitize_key( $flag );
	$all  = igp_get_feature_flags();

	return isset( $all[ $flag ] ) && true === $all[ $flag ];
}

/**
 * Update one controlled V2 feature flag.
 *
 * Required V2 helper.
 *
 * @param string $flag    Feature flag slug.
 * @param mixed  $enabled Desired enabled value.
 * @return bool True when updated, false when the flag is unknown or update failed.
 */
function igp_update_feature_flag( string $flag, $enabled ): bool {
	$flag        = sanitize_key( $flag );
	$definitions = igp_pro_get_feature_flag_definitions();

	if ( ! isset( $definitions[ $flag ] ) ) {
		return false;
	}

	$flags   = igp_get_feature_flags();
	$enabled = in_array( $enabled, array( true, 1, '1', 'yes', 'true', 'on' ), true );

	if ( isset( $flags[ $flag ] ) && $enabled === $flags[ $flag ] ) {
		return true;
	}

	$flags[ $flag ] = $enabled;

	return update_option( IGP_PRO_FEATURE_FLAGS_OPTION, igp_pro_sanitize_feature_flags( $flags ), false );
}

/**
 * Update the full controlled V2 feature flag map in one option write.
 *
 * @param mixed $flags Raw flag map.
 * @return bool
 */
function igp_pro_update_feature_flags( $flags ): bool {
	return update_option( IGP_PRO_FEATURE_FLAGS_OPTION, igp_pro_sanitize_feature_flags( $flags ), false );
}

/**
 * Conditionally load a V2 module file only when its feature flag is enabled.
 *
 * This helper is intentionally conservative: it does not run missing modules,
 * does not fatal when a future module file is absent, and returns a structured
 * boolean result that callers can use during later phases.
 *
 * @param string $flag          Feature flag slug.
 * @param string $relative_file Plugin-relative PHP file path.
 * @return bool True when the module file was loaded.
 */
function igp_pro_load_feature_module_if_enabled( string $flag, string $relative_file ): bool {
	if ( ! igp_feature_enabled( $flag ) ) {
		return false;
	}

	$relative_file = ltrim( $relative_file, '/\\' );
	$path          = IGP_PRO_PATH . $relative_file;

	if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
		return false;
	}

	require_once $path;

	return true;
}

/**
 * Project-prefixed alias for the required V2 helper.
 *
 * @return array<string,bool>
 */
function igp_pro_get_feature_flags(): array {
	return igp_get_feature_flags();
}

/**
 * Project-prefixed alias for the required V2 helper.
 *
 * @param string $flag Feature flag slug.
 * @return bool
 */
function igp_pro_feature_enabled( string $flag ): bool {
	return igp_feature_enabled( $flag );
}

/**
 * Project-prefixed alias for the required V2 helper.
 *
 * @param string $flag    Feature flag slug.
 * @param mixed  $enabled Desired enabled value.
 * @return bool
 */
function igp_pro_update_feature_flag( string $flag, $enabled ): bool {
	return igp_update_feature_flag( $flag, $enabled );
}
