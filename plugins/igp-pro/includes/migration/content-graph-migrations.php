<?php
/**
 * Content Graph migration framework for IGP Pro V2.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the Content Graph migration registry.
 *
 * @return array<string,callable>
 */
function igp_pro_get_content_graph_migration_registry(): array {
	return array(
		'1.0:2.0' => 'igp_migrate_content_graph_1_0_to_2_0',
	);
}

/**
 * Detect a Content Graph schema version.
 *
 * @param array<string,mixed> $graph Graph.
 * @return string
 */
function igp_pro_detect_content_graph_schema_version( array $graph ): string {
	if ( isset( $graph['schema_version'] ) && is_string( $graph['schema_version'] ) ) {
		return function_exists( 'igp_pro_normalize_schema_version' ) ? igp_pro_normalize_schema_version( $graph['schema_version'] ) : $graph['schema_version'];
	}

	if ( isset( $graph['version'] ) && is_string( $graph['version'] ) ) {
		return function_exists( 'igp_pro_normalize_schema_version' ) ? igp_pro_normalize_schema_version( $graph['version'] ) : $graph['version'];
	}

	return '1.0';
}

/**
 * Migrate a Content Graph in memory without saving it.
 *
 * @param array<string,mixed> $graph  Graph.
 * @param string             $target Target schema version.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_migrate_content_graph( array $graph, string $target = '2.0' ) {
	$from = igp_pro_detect_content_graph_schema_version( $graph );
	$to   = function_exists( 'igp_pro_normalize_schema_version' ) ? igp_pro_normalize_schema_version( $target ) : $target;

	if ( $from === $to ) {
		return $graph;
	}

	$key      = $from . ':' . $to;
	$registry = igp_pro_get_content_graph_migration_registry();

	if ( empty( $registry[ $key ] ) || ! is_callable( $registry[ $key ] ) ) {
		return new WP_Error( 'igp_pro_missing_content_graph_migration', sprintf( __( 'No Content Graph migration exists from %1$s to %2$s.', 'igp-pro' ), $from, $to ) );
	}

	$migrated = call_user_func( $registry[ $key ], $graph );
	if ( is_wp_error( $migrated ) ) {
		return $migrated;
	}

	if ( function_exists( 'igp_pro_validate_content_graph' ) ) {
		$validation = igp_pro_validate_content_graph( $migrated );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
	}

	return $migrated;
}

/**
 * Migrate a Content Graph for non-mutating render/preview/import validation paths.
 *
 * @param array<string,mixed> $graph  Graph.
 * @param string             $target Target schema version.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_migrate_content_graph_for_render( array $graph, string $target = '2.0' ) {
	$migrated = igp_pro_migrate_content_graph( $graph, $target );
	if ( is_wp_error( $migrated ) ) {
		return $migrated;
	}

	if ( function_exists( 'igp_pro_sanitize_content_graph_payload' ) ) {
		$migrated = igp_pro_sanitize_content_graph_payload( $migrated );
	}

	if ( function_exists( 'igp_pro_canonicalize_content_graph' ) ) {
		$migrated = igp_pro_canonicalize_content_graph( $migrated );
		if ( is_wp_error( $migrated ) ) {
			return $migrated;
		}
	}

	if ( function_exists( 'igp_pro_validate_content_graph' ) ) {
		$validation = igp_pro_validate_content_graph( $migrated );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
	}

	return $migrated;
}

/**
 * Traceable V1-to-V2 Content Graph migration.
 *
 * The root `version` remains `v1` during Phase 6 so existing V1 validators,
 * imports, rendering, and editor paths keep working. V2 schema tracking is
 * introduced through explicit schema_version/migrated fields.
 *
 * @param array<string,mixed> $graph V1 graph.
 * @return array<string,mixed>|WP_Error
 */
function igp_migrate_content_graph_1_0_to_2_0( array $graph ) {
	if ( ! isset( $graph['sections'] ) || ! is_array( $graph['sections'] ) ) {
		return new WP_Error( 'igp_pro_migration_missing_sections', __( 'Content Graph sections are required before migration.', 'igp-pro' ) );
	}

	$migrated = $graph;
	$sections = array();

	foreach ( $graph['sections'] as $section ) {
		if ( ! is_array( $section ) ) {
			return new WP_Error( 'igp_pro_migration_invalid_section', __( 'Content Graph contains an invalid section.', 'igp-pro' ) );
		}

		$block = function_exists( 'igp_pro_migrate_block_section' ) ? igp_pro_migrate_block_section( $section, '2.0' ) : $section;
		if ( is_wp_error( $block ) ) {
			return $block;
		}

		$sections[] = $block;
	}

	$migrated['version']          = isset( $migrated['version'] ) ? (string) $migrated['version'] : 'v1';
	$migrated['schema_version']   = function_exists( 'igp_pro_get_current_content_graph_schema_version' ) ? igp_pro_get_current_content_graph_schema_version() : '2.0';
	$migrated['migrated_from']    = igp_pro_detect_content_graph_schema_version( $graph );
	$migrated['last_migrated_at'] = gmdate( 'c' );
	$migrated['sections']         = $sections;

	return $migrated;
}

/**
 * Migrate and persist a post Content Graph with a pre-migration snapshot.
 *
 * @param int    $post_id Post ID.
 * @param string $target  Target schema version.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_migrate_content_graph_for_post( int $post_id, string $target = '2.0' ) {
	if ( $post_id <= 0 ) {
		return new WP_Error( 'igp_pro_migration_invalid_post_id', __( 'A valid post ID is required for migration.', 'igp-pro' ) );
	}

	if ( ! function_exists( 'igp_pro_load_content_graph' ) || ! function_exists( 'igp_pro_save_content_graph' ) ) {
		return new WP_Error( 'igp_pro_migration_content_graph_service_missing', __( 'Content Graph service is unavailable.', 'igp-pro' ) );
	}

	$graph = igp_pro_load_content_graph( $post_id );
	if ( is_wp_error( $graph ) ) {
		return $graph;
	}

	$snapshot_id = '';
	if ( function_exists( 'igp_create_snapshot' ) ) {
		$snapshot = igp_create_snapshot(
			'content_graph',
			$post_id,
			$graph,
			array(
				'source_module' => 'migration',
				'actor_type'    => 'system',
				'reason'        => 'pre_migration',
			)
		);
		if ( is_wp_error( $snapshot ) ) {
			return $snapshot;
		}
		$snapshot_id = (string) $snapshot;
	}

	$migrated = igp_pro_migrate_content_graph( $graph, $target );
	if ( is_wp_error( $migrated ) ) {
		return $migrated;
	}

	if ( class_exists( 'IGP_Content_Graph_Save_Service' ) ) {
		$save = IGP_Content_Graph_Save_Service::save(
			$post_id,
			$migrated,
			array(
				'check_capability' => false,
				'skip_snapshot'    => true,
				'source_module'    => 'migration',
				'actor_type'       => 'system',
				'reason'           => 'content_graph_migration',
			)
		);
	} else {
		$save = new WP_Error( 'igp_pro_save_service_missing', __( 'Canonical Content Graph save service is unavailable.', 'igp-pro' ) );
	}
	if ( is_wp_error( $save ) ) {
		return $save;
	}

	if ( '' !== $snapshot_id && function_exists( 'igp_pro_update_snapshot_after_data' ) ) {
		igp_pro_update_snapshot_after_data( $snapshot_id, $migrated );
	}

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => 'system',
				'operation'     => 'content_graph_migrated',
				'object_type'   => 'content_graph',
				'object_id'     => $post_id,
				'source_module' => 'migration',
				'status'        => 'success',
				'summary'       => sprintf( 'Content Graph migrated to schema %s.', $target ),
				'snapshot_id'   => $snapshot_id,
			)
		);
	}

	return $migrated;
}
