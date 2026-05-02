<?php
/**
 * Starter template importer for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_TEMPLATE_BATCHES_OPTION' ) ) {
	define( 'IGP_PRO_TEMPLATE_BATCHES_OPTION', 'igp_pro_starter_template_import_batches' );
}

if ( ! defined( 'IGP_PRO_TEMPLATE_META_SOURCE' ) ) {
	define( 'IGP_PRO_TEMPLATE_META_SOURCE', '_igp_template_source' );
}

if ( ! defined( 'IGP_PRO_TEMPLATE_META_UUID' ) ) {
	define( 'IGP_PRO_TEMPLATE_META_UUID', '_igp_template_uuid' );
}

if ( ! defined( 'IGP_PRO_TEMPLATE_META_BATCH' ) ) {
	define( 'IGP_PRO_TEMPLATE_META_BATCH', '_igp_import_batch_id' );
}

if ( ! defined( 'IGP_PRO_TEMPLATE_META_VERSION' ) ) {
	define( 'IGP_PRO_TEMPLATE_META_VERSION', '_igp_template_version' );
}

/**
 * Return supported real import modes.
 *
 * @return string[]
 */
function igp_pro_get_starter_template_import_modes(): array {
	return array( 'create_new', 'merge_existing' );
}

/**
 * Sanitize import mode.
 *
 * @param string $mode Raw mode.
 * @return string
 */
function igp_pro_sanitize_starter_template_import_mode( string $mode ): string {
	$mode = sanitize_key( $mode );
	return in_array( $mode, igp_pro_get_starter_template_import_modes(), true ) ? $mode : 'create_new';
}

/**
 * Return stored starter template import batches.
 *
 * @return array<int,array<string,mixed>>
 */
function igp_pro_get_starter_template_import_batches(): array {
	$batches = get_option( IGP_PRO_TEMPLATE_BATCHES_OPTION, array() );
	return is_array( $batches ) ? array_values( $batches ) : array();
}

/**
 * Save starter template import batches.
 *
 * @param array<int,array<string,mixed>> $batches Batches.
 */
function igp_pro_save_starter_template_import_batches( array $batches ): void {
	usort(
		$batches,
		static function ( array $a, array $b ): int {
			return strcmp( (string) ( $b['created_at'] ?? '' ), (string) ( $a['created_at'] ?? '' ) );
		}
	);
	$batches = array_slice( array_values( $batches ), 0, 50 );
	update_option( IGP_PRO_TEMPLATE_BATCHES_OPTION, $batches, false );
}

/**
 * Store or update a batch record.
 *
 * @param array<string,mixed> $batch Batch.
 */
function igp_pro_store_starter_template_import_batch( array $batch ): void {
	$batches  = igp_pro_get_starter_template_import_batches();
	$batch_id = sanitize_key( (string) ( $batch['batch_id'] ?? '' ) );
	$stored   = false;

	foreach ( $batches as $index => $existing ) {
		if ( $batch_id !== '' && sanitize_key( (string) ( $existing['batch_id'] ?? '' ) ) === $batch_id ) {
			$batches[ $index ] = $batch;
			$stored = true;
			break;
		}
	}

	if ( ! $stored ) {
		$batches[] = $batch;
	}

	igp_pro_save_starter_template_import_batches( $batches );
}

/**
 * Get one import batch.
 *
 * @param string $batch_id Batch ID.
 * @return array<string,mixed>|null
 */
function igp_pro_get_starter_template_import_batch( string $batch_id ): ?array {
	$batch_id = sanitize_key( $batch_id );
	foreach ( igp_pro_get_starter_template_import_batches() as $batch ) {
		if ( $batch_id === sanitize_key( (string) ( $batch['batch_id'] ?? '' ) ) ) {
			return $batch;
		}
	}
	return null;
}

/**
 * Return most recent non-rolled-back starter import batch.
 *
 * @return array<string,mixed>|null
 */
function igp_pro_get_last_starter_template_import_batch(): ?array {
	foreach ( igp_pro_get_starter_template_import_batches() as $batch ) {
		$status = sanitize_key( (string) ( $batch['status'] ?? '' ) );
		if ( in_array( $status, array( 'completed', 'partial' ), true ) ) {
			return $batch;
		}
	}
	return null;
}

/**
 * Import a starter template.
 *
 * @param string $template_id Template ID.
 * @param string $mode        create_new or merge_existing.
 * @param array  $args        Optional args.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_import_starter_template( string $template_id, string $mode = 'create_new', array $args = array() ) {
	if ( function_exists( 'igp_feature_enabled' ) && ! igp_feature_enabled( 'enable_starter_templates' ) ) {
		return new WP_Error( 'igp_pro_starter_templates_disabled', __( 'Starter Templates feature flag is disabled.', 'igp-pro' ) );
	}

	$template_id = sanitize_key( $template_id );
	$mode        = igp_pro_sanitize_starter_template_import_mode( $mode );
	$template    = function_exists( 'igp_pro_get_starter_template' ) ? igp_pro_get_starter_template( $template_id ) : new WP_Error( 'igp_pro_template_registry_missing', __( 'Starter template registry is unavailable.', 'igp-pro' ) );
	if ( is_wp_error( $template ) ) {
		return $template;
	}

	$dry_run = function_exists( 'igp_pro_dry_run_starter_template' ) ? igp_pro_dry_run_starter_template( $template_id ) : new WP_Error( 'igp_pro_template_preview_missing', __( 'Starter template dry-run service is unavailable.', 'igp-pro' ) );
	if ( is_wp_error( $dry_run ) ) {
		return $dry_run;
	}
	if ( empty( $dry_run['valid'] ) ) {
		return new WP_Error( 'igp_pro_template_dry_run_failed', __( 'Template import blocked because dry run is invalid.', 'igp-pro' ), $dry_run );
	}

	$batch_id = 'igp_tpl_' . gmdate( 'YmdHis' ) . '_' . wp_generate_password( 8, false, false );
	$batch_id = sanitize_key( $batch_id );

	$batch = array(
		'batch_id'           => $batch_id,
		'template_id'        => $template_id,
		'template_version'   => sanitize_text_field( (string) ( $template['version'] ?? '' ) ),
		'mode'               => $mode,
		'status'             => 'running',
		'created_at'         => gmdate( 'c' ),
		'actor_user_id'      => function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0,
		'objects'            => array(),
		'errors'             => array(),
		'warnings'           => array(),
		'media_placeholders' => array(),
		'link_map'           => array(),
		'brand_profile'      => null,
	);
	igp_pro_store_starter_template_import_batch( $batch );

	$result = array(
		'batch_id'    => $batch_id,
		'template_id' => $template_id,
		'mode'        => $mode,
		'success'     => true,
		'created'     => array(),
		'updated'     => array(),
		'skipped'     => array(),
		'errors'      => array(),
		'warnings'    => array(),
	);

	$brand_result = igp_pro_import_starter_template_brand_profile( $template );
	if ( is_wp_error( $brand_result ) ) {
		$result['warnings'][] = $brand_result->get_error_message();
		$batch['warnings'][]  = $brand_result->get_error_message();
	} elseif ( is_array( $brand_result ) ) {
		$batch['brand_profile'] = $brand_result;
	}

	$uuid_map = array();
	foreach ( array( 'pages', 'destinations', 'tours' ) as $bucket ) {
		foreach ( $template[ $bucket ] ?? array() as $object ) {
			$import = igp_pro_import_starter_template_object( $template, $bucket, $object, $mode, $batch_id );
			if ( is_wp_error( $import ) ) {
				$result['success']  = false;
				$result['errors'][] = $import->get_error_message();
				$batch['errors'][]  = $import->get_error_message();
				continue;
			}

			$batch['objects'][] = $import;
			$template_uuid = sanitize_text_field( (string) ( $import['template_uuid'] ?? '' ) );
			$post_id       = absint( $import['post_id'] ?? 0 );
			if ( '' !== $template_uuid && $post_id > 0 ) {
				$uuid_map[ $template_uuid ] = $post_id;
			}

			$action = sanitize_key( (string) ( $import['action'] ?? '' ) );
			if ( 'created' === $action ) {
				$result['created'][] = $post_id;
			} elseif ( 'modified' === $action ) {
				$result['updated'][] = $post_id;
			} else {
				$result['skipped'][] = $post_id;
			}
		}
	}

	$relationship_result = igp_pro_import_starter_template_relationships( $template, $uuid_map, $batch_id );
	if ( is_wp_error( $relationship_result ) ) {
		$result['success']  = false;
		$result['errors'][] = $relationship_result->get_error_message();
		$batch['errors'][]  = $relationship_result->get_error_message();
	} else {
		foreach ( $relationship_result as $relationship_entry ) {
			$batch['objects'][] = $relationship_entry;
		}
	}

	$media_placeholders = igp_pro_import_starter_template_media_placeholders( $template, $batch_id );
	if ( is_wp_error( $media_placeholders ) ) {
		$result['warnings'][] = $media_placeholders->get_error_message();
		$batch['warnings'][]  = $media_placeholders->get_error_message();
	} else {
		$batch['media_placeholders'] = $media_placeholders;
	}

	$link_map = igp_pro_import_starter_template_link_map( $template, $uuid_map, $batch_id );
	if ( is_wp_error( $link_map ) ) {
		$result['warnings'][] = $link_map->get_error_message();
		$batch['warnings'][]  = $link_map->get_error_message();
	} else {
		$batch['link_map'] = $link_map;
	}

	$batch['status']       = $result['success'] ? 'completed' : 'partial';
	$batch['completed_at'] = gmdate( 'c' );
	igp_pro_store_starter_template_import_batch( $batch );

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => 'import',
				'operation'     => 'starter_template_import',
				'object_type'   => 'starter_template',
				'object_id'     => 0,
				'source_module' => 'starter-template-importer',
				'status'        => $result['success'] ? 'success' : 'warning',
				'summary'       => sprintf( 'Starter template %s imported in %s mode. Batch: %s.', $template_id, $mode, $batch_id ),
				'context'       => array(
					'created' => count( $result['created'] ),
					'updated' => count( $result['updated'] ),
					'skipped' => count( $result['skipped'] ),
				),
			)
		);
	}

	return $result;
}

/**
 * Import or create active brand profile from template brand.json.
 *
 * @param array<string,mixed> $template Template.
 * @return array<string,mixed>|WP_Error|null
 */
function igp_pro_import_starter_template_brand_profile( array $template ) {
	if ( ! function_exists( 'igp_pro_save_brand_profile' ) || ! function_exists( 'igp_pro_set_active_brand_profile' ) ) {
		return null;
	}

	$template_id = sanitize_key( (string) ( $template['template_id'] ?? '' ) );
	$brand = igp_pro_load_starter_template_json( $template_id, 'brand.json' );
	if ( is_wp_error( $brand ) ) {
		return $brand;
	}

	$profile_id = igp_pro_save_brand_profile( $brand );
	if ( is_wp_error( $profile_id ) ) {
		return $profile_id;
	}

	$active = igp_pro_set_active_brand_profile( (string) $profile_id );
	if ( is_wp_error( $active ) ) {
		return $active;
	}

	return array(
		'profile_id' => (string) $profile_id,
		'name'       => sanitize_text_field( (string) ( $brand['name'] ?? $profile_id ) ),
	);
}

/**
 * Import one starter object.
 *
 * @param array  $template Template.
 * @param string $bucket   pages|tours|destinations.
 * @param array  $object   Object definition.
 * @param string $mode     Import mode.
 * @param string $batch_id Batch ID.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_import_starter_template_object( array $template, string $bucket, array $object, string $mode, string $batch_id ) {
	$template_id      = sanitize_key( (string) ( $template['template_id'] ?? '' ) );
	$template_version = sanitize_text_field( (string) ( $template['version'] ?? '' ) );
	$template_uuid    = sanitize_text_field( (string) ( $object['template_uuid'] ?? '' ) );
	$title            = sanitize_text_field( (string) ( $object['title'] ?? '' ) );
	$slug             = sanitize_title( (string) ( $object['slug'] ?? '' ) );
	$post_type        = function_exists( 'igp_pro_get_starter_template_object_post_type' ) ? igp_pro_get_starter_template_object_post_type( $bucket ) : 'page';
	$existing_ids     = function_exists( 'igp_pro_find_existing_template_posts_by_uuid' ) ? igp_pro_find_existing_template_posts_by_uuid( $template_uuid, $post_type ) : array();
	$existing_id      = ! empty( $existing_ids ) ? absint( $existing_ids[0] ) : 0;
	$graph            = igp_pro_load_starter_template_object_graph( $template, $object );
	if ( is_wp_error( $graph ) ) {
		return $graph;
	}

	$seo = isset( $object['seo'] ) && is_array( $object['seo'] ) ? $object['seo'] : array();
	if ( isset( $template['seo_profile'] ) && is_array( $template['seo_profile'] ) ) {
		$graph['seo'] = wp_parse_args( isset( $graph['seo'] ) && is_array( $graph['seo'] ) ? $graph['seo'] : array(), $template['seo_profile'] );
	}
	$graph['seo'] = wp_parse_args( $seo, isset( $graph['seo'] ) && is_array( $graph['seo'] ) ? $graph['seo'] : array() );

	$entry = array(
		'template_uuid'      => $template_uuid,
		'template_id'        => $template_id,
		'template_version'   => $template_version,
		'object_bucket'      => sanitize_key( $bucket ),
		'post_type'          => $post_type,
		'post_id'            => 0,
		'action'             => '',
		'snapshot_id'        => '',
		'post_hash_at_import'=> '',
	);

	if ( $existing_id > 0 && 'create_new' === $mode ) {
		// Idempotent create_new imports do not mutate existing objects.
		$entry['post_id']             = $existing_id;
		$entry['action']              = 'skipped_existing';
		$entry['post_hash_at_import'] = igp_pro_hash_starter_template_object_state( $existing_id );
		return $entry;
	}

	$snapshot_id = '';
	if ( $existing_id > 0 && 'merge_existing' === $mode ) {
		$before = igp_pro_collect_starter_template_object_state( $existing_id );
		if ( function_exists( 'igp_create_snapshot' ) ) {
			$snapshot = igp_create_snapshot(
				'starter_template_object',
				$existing_id,
				$before,
				array(
					'source_module' => 'starter-template-importer',
					'actor_type'    => 'import',
					'reason'        => 'starter_template_merge',
					'template_id'   => $template_id,
					'batch_id'      => $batch_id,
				)
			);
			if ( is_wp_error( $snapshot ) ) {
				return $snapshot;
			}
			$snapshot_id = is_string( $snapshot ) ? $snapshot : '';
		}
	}

	if ( $existing_id > 0 ) {
		$post_id = wp_update_post(
			array(
				'ID'          => $existing_id,
				'post_title'  => $title,
				'post_name'   => $slug,
				'post_status' => get_post_status( $existing_id ) ?: 'draft',
			),
			true
		);
		$action = 'modified';
	} else {
		$post_id = wp_insert_post(
			array(
				'post_type'   => $post_type,
				'post_title'  => $title,
				'post_name'   => $slug,
				'post_status' => isset( $object['post_status'] ) ? sanitize_key( (string) $object['post_status'] ) : 'draft',
				'post_author' => function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0,
			),
			true
		);
		$action = 'created';
	}

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$post_id = absint( $post_id );
	igp_pro_update_template_source_meta( $post_id, $template_id, $template_uuid, $batch_id, $template_version );
	igp_pro_save_starter_template_seo_fields( $post_id, $graph, $seo );

	$save = function_exists( 'igp_pro_save_content_graph' ) ? igp_pro_save_content_graph( $post_id, $graph ) : new WP_Error( 'igp_pro_content_graph_missing', __( 'Content Graph service is unavailable.', 'igp-pro' ) );
	if ( is_wp_error( $save ) ) {
		return $save;
	}

	if ( function_exists( 'igp_pro_sync_content_graph_to_post_content' ) ) {
		$sync = igp_pro_sync_content_graph_to_post_content( $post_id, $graph );
		if ( is_wp_error( $sync ) ) {
			return $sync;
		}
	}

	if ( '' !== $snapshot_id && function_exists( 'igp_pro_update_snapshot_after_data' ) ) {
		igp_pro_update_snapshot_after_data( $snapshot_id, igp_pro_collect_starter_template_object_state( $post_id ) );
	}

	$entry['post_id']             = $post_id;
	$entry['action']              = $action;
	$entry['snapshot_id']         = $snapshot_id;
	$entry['post_hash_at_import'] = igp_pro_hash_starter_template_object_state( $post_id );

	return $entry;
}

/**
 * Load, migrate, and validate object content graph.
 *
 * @param array $template Template.
 * @param array $object Object.
 * @return array|WP_Error
 */
function igp_pro_load_starter_template_object_graph( array $template, array $object ) {
	$template_id = sanitize_key( (string) ( $template['template_id'] ?? '' ) );
	$graph_file  = sanitize_text_field( (string) ( $object['content_graph'] ?? '' ) );
	if ( '' === $graph_file ) {
		return new WP_Error( 'igp_pro_template_missing_graph', __( 'Starter template object is missing a content graph file.', 'igp-pro' ) );
	}

	$graph = igp_pro_load_starter_template_json( $template_id, $graph_file );
	if ( is_wp_error( $graph ) ) {
		return $graph;
	}

	if ( function_exists( 'igp_pro_migrate_content_graph_for_render' ) ) {
		$migrated = igp_pro_migrate_content_graph_for_render( $graph );
		if ( is_wp_error( $migrated ) ) {
			return $migrated;
		}
		$graph = $migrated;
	}

	if ( function_exists( 'igp_pro_validate_content_graph_payload' ) ) {
		$validation = igp_pro_validate_content_graph_payload( $graph );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
	}

	return $graph;
}

/**
 * Store template metadata required by V2 idempotency rule.
 *
 * @param int    $post_id Post ID.
 * @param string $template_id Template ID.
 * @param string $template_uuid Template UUID.
 * @param string $batch_id Batch ID.
 * @param string $template_version Template version.
 */
function igp_pro_update_template_source_meta( int $post_id, string $template_id, string $template_uuid, string $batch_id, string $template_version ): void {
	update_post_meta( $post_id, IGP_PRO_TEMPLATE_META_SOURCE, sanitize_key( $template_id ) );
	update_post_meta( $post_id, IGP_PRO_TEMPLATE_META_UUID, sanitize_text_field( $template_uuid ) );
	update_post_meta( $post_id, IGP_PRO_TEMPLATE_META_BATCH, sanitize_key( $batch_id ) );
	update_post_meta( $post_id, IGP_PRO_TEMPLATE_META_VERSION, sanitize_text_field( $template_version ) );
}

/**
 * Save SEO fields from template object and graph.
 *
 * @param int   $post_id Post ID.
 * @param array $graph   Graph.
 * @param array $seo     Object SEO.
 */
function igp_pro_save_starter_template_seo_fields( int $post_id, array $graph, array $seo ): void {
	$seo = wp_parse_args( $seo, isset( $graph['seo'] ) && is_array( $graph['seo'] ) ? $graph['seo'] : array() );

	$meta_map = array(
		'h1'             => '_igp_seo_h1',
		'title'          => '_igp_seo_title',
		'description'    => IGP_PRO_META_DESCRIPTION_META_KEY,
		'canonical_url'  => '_igp_seo_canonical_url',
		'robots'         => '_igp_seo_robots',
		'og_title'       => '_igp_seo_og_title',
		'og_description' => '_igp_seo_og_description',
		'og_image_id'    => '_igp_seo_og_image_id',
		'schema_policy'  => '_igp_seo_schema_policy',
	);

	foreach ( $meta_map as $field => $meta_key ) {
		if ( ! array_key_exists( $field, $seo ) ) {
			continue;
		}
		$value = $seo[ $field ];
		if ( 'og_image_id' === $field ) {
			update_post_meta( $post_id, $meta_key, absint( $value ) );
		} elseif ( 'canonical_url' === $field ) {
			update_post_meta( $post_id, $meta_key, esc_url_raw( (string) $value ) );
		} else {
			update_post_meta( $post_id, $meta_key, sanitize_text_field( (string) $value ) );
		}
	}

	foreach ( array( 'focus_topics', 'internal_link_targets' ) as $array_field ) {
		if ( isset( $seo[ $array_field ] ) && is_array( $seo[ $array_field ] ) ) {
			update_post_meta( $post_id, '_igp_seo_' . $array_field, array_values( array_map( 'sanitize_text_field', $seo[ $array_field ] ) ) );
		}
	}
}

/**
 * Import relationships after all template objects have post IDs.
 *
 * @param array  $template Template.
 * @param array  $uuid_map UUID => post ID.
 * @param string $batch_id Batch ID.
 * @return array<int,array<string,mixed>>|WP_Error
 */
function igp_pro_import_starter_template_relationships( array $template, array $uuid_map, string $batch_id ) {
	$entries = array();
	if ( ! function_exists( 'igp_pro_save_relationships' ) ) {
		return $entries;
	}

	foreach ( array( 'tours', 'destinations' ) as $bucket ) {
		foreach ( $template[ $bucket ] ?? array() as $object ) {
			if ( empty( $object['relationships'] ) || ! is_array( $object['relationships'] ) ) {
				continue;
			}
			$template_uuid = sanitize_text_field( (string) ( $object['template_uuid'] ?? '' ) );
			$post_id       = isset( $uuid_map[ $template_uuid ] ) ? absint( $uuid_map[ $template_uuid ] ) : 0;
			if ( $post_id <= 0 ) {
				continue;
			}

			$payload = igp_pro_map_starter_template_relationship_payload( $object['relationships'], $uuid_map );
			if ( is_wp_error( $payload ) ) {
				return $payload;
			}

			$save = igp_pro_save_relationships(
				$post_id,
				$payload,
				array(
					'actor_type'    => 'import',
					'source_module' => 'starter-template-importer',
					'reason'        => 'starter_template_import',
					'batch_id'      => $batch_id,
				)
			);
			if ( is_wp_error( $save ) ) {
				return $save;
			}

			$entries[] = array(
				'template_uuid' => $template_uuid,
				'post_id'       => $post_id,
				'post_type'     => get_post_type( $post_id ),
				'action'        => 'relationships_updated',
				'batch_id'      => $batch_id,
			);
		}
	}

	return $entries;
}

/**
 * Convert relationship UUID fields to post ID payload.
 *
 * @param array $relationships Relationship declaration.
 * @param array $uuid_map UUID => post ID.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_map_starter_template_relationship_payload( array $relationships, array $uuid_map ) {
	$field_map = array(
		'primary_destination_uuid'  => 'primary_destination_id',
		'destination_uuids'         => 'destination_ids',
		'route_stop_uuids'          => 'route_stop_ids',
		'related_tour_uuids'        => 'related_tour_ids',
		'related_destination_uuids' => 'related_destination_ids',
	);

	$payload = array(
		'primary_destination_id'  => 0,
		'destination_ids'         => array(),
		'route_stop_ids'          => array(),
		'related_tour_ids'        => array(),
		'related_destination_ids' => array(),
	);

	foreach ( $field_map as $uuid_field => $payload_field ) {
		if ( ! array_key_exists( $uuid_field, $relationships ) ) {
			continue;
		}
		$values = is_array( $relationships[ $uuid_field ] ) ? $relationships[ $uuid_field ] : array( $relationships[ $uuid_field ] );
		$ids    = array();
		foreach ( $values as $uuid ) {
			$uuid = sanitize_text_field( (string) $uuid );
			if ( '' === $uuid ) {
				continue;
			}
			if ( empty( $uuid_map[ $uuid ] ) ) {
				return new WP_Error( 'igp_pro_template_relationship_unmapped_uuid', __( 'Template relationship references an object that was not imported.', 'igp-pro' ), array( 'uuid' => $uuid ) );
			}
			$ids[] = absint( $uuid_map[ $uuid ] );
		}
		if ( 'primary_destination_id' === $payload_field ) {
			$payload[ $payload_field ] = ! empty( $ids ) ? absint( $ids[0] ) : 0;
		} else {
			$payload[ $payload_field ] = array_values( array_unique( array_filter( $ids ) ) );
		}
	}

	return $payload;
}

/**
 * Store media placeholder map for later media phase.
 *
 * @param array  $template Template.
 * @param string $batch_id Batch ID.
 * @return array<int,array<string,mixed>>|WP_Error
 */
function igp_pro_import_starter_template_media_placeholders( array $template, string $batch_id ) {
	$template_id = sanitize_key( (string) ( $template['template_id'] ?? '' ) );
	$media       = igp_pro_load_starter_template_json( $template_id, 'media.json' );
	if ( is_wp_error( $media ) ) {
		$media = isset( $template['media_placeholders'] ) && is_array( $template['media_placeholders'] ) ? $template['media_placeholders'] : array();
	}

	$sanitized = array();
	foreach ( is_array( $media ) ? $media : array() as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$sanitized[] = array(
			'template_uuid' => sanitize_text_field( (string) ( $item['template_uuid'] ?? '' ) ),
			'role'          => sanitize_key( (string) ( $item['role'] ?? '' ) ),
			'filename'      => sanitize_file_name( (string) ( $item['filename'] ?? '' ) ),
			'alt'           => sanitize_text_field( (string) ( $item['alt'] ?? '' ) ),
			'batch_id'      => sanitize_key( $batch_id ),
		);
	}

	$all = get_option( 'igp_pro_starter_template_media_placeholders', array() );
	$all = is_array( $all ) ? $all : array();
	$all[ $batch_id ] = $sanitized;
	update_option( 'igp_pro_starter_template_media_placeholders', $all, false );

	return $sanitized;
}

/**
 * Store link map entries for later internal linking phase.
 *
 * @param array  $template Template.
 * @param array  $uuid_map UUID => post ID.
 * @param string $batch_id Batch ID.
 * @return array<string,array<string,mixed>>|WP_Error
 */
function igp_pro_import_starter_template_link_map( array $template, array $uuid_map, string $batch_id ) {
	$template_id = sanitize_key( (string) ( $template['template_id'] ?? '' ) );
	$link_map    = igp_pro_load_starter_template_json( $template_id, 'link-map.json' );
	if ( is_wp_error( $link_map ) ) {
		$link_map = isset( $template['link_map'] ) && is_array( $template['link_map'] ) ? $template['link_map'] : array();
	}

	$resolved = array();
	foreach ( is_array( $link_map ) ? $link_map : array() as $key => $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}
		$from_uuid = sanitize_text_field( (string) ( $entry['from_uuid'] ?? '' ) );
		$to_uuid   = sanitize_text_field( (string) ( $entry['to_uuid'] ?? '' ) );
		$resolved[ sanitize_key( (string) $key ) ] = array(
			'from_uuid' => $from_uuid,
			'to_uuid'   => $to_uuid,
			'from_id'   => isset( $uuid_map[ $from_uuid ] ) ? absint( $uuid_map[ $from_uuid ] ) : 0,
			'to_id'     => isset( $uuid_map[ $to_uuid ] ) ? absint( $uuid_map[ $to_uuid ] ) : 0,
			'anchor'    => sanitize_text_field( (string) ( $entry['anchor'] ?? '' ) ),
			'batch_id'  => sanitize_key( $batch_id ),
		);
	}

	$all = get_option( 'igp_pro_starter_template_link_maps', array() );
	$all = is_array( $all ) ? $all : array();
	$all[ $batch_id ] = $resolved;
	update_option( 'igp_pro_starter_template_link_maps', $all, false );

	return $resolved;
}

/**
 * Collect restorable object state before merge.
 *
 * @param int $post_id Post ID.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_collect_starter_template_object_state( int $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return new WP_Error( 'igp_pro_template_state_post_missing', __( 'Post could not be found for template snapshot.', 'igp-pro' ) );
	}

	$graph = function_exists( 'igp_pro_load_content_graph' ) ? igp_pro_load_content_graph( $post_id ) : array();
	if ( is_wp_error( $graph ) ) {
		$graph = array();
	}

	$relationships = function_exists( 'igp_pro_get_relationships' ) ? igp_pro_get_relationships( $post_id, true ) : array();

	return array(
		'post'          => array(
			'ID'           => $post_id,
			'post_title'   => $post->post_title,
			'post_name'    => $post->post_name,
			'post_status'  => $post->post_status,
			'post_type'    => $post->post_type,
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
		),
		'graph'         => is_array( $graph ) ? $graph : array(),
		'seo_meta'      => igp_pro_get_starter_template_seo_meta_state( $post_id ),
		'relationships' => is_array( $relationships ) ? $relationships : array(),
		'template_meta' => array(
			IGP_PRO_TEMPLATE_META_SOURCE  => get_post_meta( $post_id, IGP_PRO_TEMPLATE_META_SOURCE, true ),
			IGP_PRO_TEMPLATE_META_UUID    => get_post_meta( $post_id, IGP_PRO_TEMPLATE_META_UUID, true ),
			IGP_PRO_TEMPLATE_META_BATCH   => get_post_meta( $post_id, IGP_PRO_TEMPLATE_META_BATCH, true ),
			IGP_PRO_TEMPLATE_META_VERSION => get_post_meta( $post_id, IGP_PRO_TEMPLATE_META_VERSION, true ),
		),
	);
}

/**
 * Hash current restorable state.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function igp_pro_hash_starter_template_object_state( int $post_id ): string {
	$state = igp_pro_collect_starter_template_object_state( $post_id );
	if ( is_wp_error( $state ) ) {
		return '';
	}
	$encoded = wp_json_encode( $state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	return is_string( $encoded ) ? md5( $encoded ) : '';
}

/**
 * Return SEO meta state.
 *
 * @param int $post_id Post ID.
 * @return array<string,mixed>
 */
function igp_pro_get_starter_template_seo_meta_state( int $post_id ): array {
	$keys = array(
		IGP_PRO_META_DESCRIPTION_META_KEY,
		'_igp_seo_h1',
		'_igp_seo_title',
		'_igp_seo_canonical_url',
		'_igp_seo_robots',
		'_igp_seo_og_title',
		'_igp_seo_og_description',
		'_igp_seo_og_image_id',
		'_igp_seo_schema_policy',
		'_igp_seo_focus_topics',
		'_igp_seo_internal_link_targets',
	);
	$state = array();
	foreach ( $keys as $key ) {
		$state[ $key ] = get_post_meta( $post_id, $key, true );
	}
	return $state;
}

/**
 * Restore a full starter template object state.
 *
 * @param int   $post_id Post ID.
 * @param array $state   State returned by igp_pro_collect_starter_template_object_state().
 * @return true|WP_Error
 */
function igp_pro_restore_starter_template_object_state( int $post_id, array $state ) {
	$post_state = isset( $state['post'] ) && is_array( $state['post'] ) ? $state['post'] : array();
	if ( empty( $post_state ) ) {
		return new WP_Error( 'igp_pro_template_restore_invalid_post_state', __( 'Template snapshot lacks post state.', 'igp-pro' ) );
	}

	$update = wp_update_post(
		array(
			'ID'           => $post_id,
			'post_title'   => sanitize_text_field( (string) ( $post_state['post_title'] ?? '' ) ),
			'post_name'    => sanitize_title( (string) ( $post_state['post_name'] ?? '' ) ),
			'post_status'  => sanitize_key( (string) ( $post_state['post_status'] ?? 'draft' ) ),
			'post_content' => (string) ( $post_state['post_content'] ?? '' ),
			'post_excerpt' => sanitize_textarea_field( (string) ( $post_state['post_excerpt'] ?? '' ) ),
		),
		true
	);
	if ( is_wp_error( $update ) ) {
		return $update;
	}

	if ( isset( $state['graph'] ) && is_array( $state['graph'] ) && function_exists( 'igp_pro_save_content_graph' ) ) {
		$save = igp_pro_save_content_graph( $post_id, $state['graph'] );
		if ( is_wp_error( $save ) ) {
			return $save;
		}
	}

	if ( isset( $state['seo_meta'] ) && is_array( $state['seo_meta'] ) ) {
		foreach ( $state['seo_meta'] as $key => $value ) {
			update_post_meta( $post_id, sanitize_key( (string) $key ), $value );
		}
	}

	if ( isset( $state['template_meta'] ) && is_array( $state['template_meta'] ) ) {
		foreach ( $state['template_meta'] as $key => $value ) {
			update_post_meta( $post_id, sanitize_key( (string) $key ), $value );
		}
	}

	if ( isset( $state['relationships'] ) && is_array( $state['relationships'] ) && function_exists( 'igp_pro_save_relationships' ) ) {
		$relationship_restore = igp_pro_save_relationships(
			$post_id,
			$state['relationships'],
			array(
				'actor_type'    => 'import',
				'source_module' => 'starter-template-rollback',
				'reason'        => 'starter_template_object_restore',
			)
		);
		if ( is_wp_error( $relationship_restore ) ) {
			return $relationship_restore;
		}
	}

	return true;
}
