<?php
/**
 * Schema version map for IGP Pro V2 migrations.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return canonical schema versions known to the migration layer.
 *
 * @return array<string,mixed>
 */
function igp_pro_get_schema_version_map(): array {
	return array(
		'content_graph' => array(
			'current'   => '2.0',
			'legacy'    => array( 'v1', '1.0' ),
			'supported' => array( 'v1', '1.0', '2.0' ),
		),
		'blocks'        => array(
			'current'        => '2.0',
			'legacy_default' => '1.0',
		),
	);
}

/**
 * Get the target V2 Content Graph schema version.
 *
 * @return string
 */
function igp_pro_get_current_content_graph_schema_version(): string {
	$map = igp_pro_get_schema_version_map();
	return (string) ( $map['content_graph']['current'] ?? '2.0' );
}

/**
 * Get the target V2 block schema version.
 *
 * @return string
 */
function igp_pro_get_current_block_schema_version(): string {
	$map = igp_pro_get_schema_version_map();
	return (string) ( $map['blocks']['current'] ?? '2.0' );
}

/**
 * Normalize a version string for migration lookup.
 *
 * @param string $version Version.
 * @return string
 */
function igp_pro_normalize_schema_version( string $version ): string {
	$version = strtolower( trim( $version ) );
	if ( 'v1' === $version || '1' === $version ) {
		return '1.0';
	}
	if ( 'v2' === $version || '2' === $version ) {
		return '2.0';
	}
	return $version;
}
