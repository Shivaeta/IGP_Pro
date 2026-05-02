<?php
/**
 * Starter template preview and dry-run service for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return a dry-run result shell.
 *
 * @param string $template_id Template ID.
 * @return array<string,mixed>
 */
function igp_pro_get_empty_starter_template_dry_run_result( string $template_id ): array {
	return array(
		'mode'                 => 'dry_run',
		'template_id'          => $template_id,
		'valid'                => true,
		'will_write'           => false,
		'errors'               => array(),
		'warnings'             => array(),
		'missing_blocks'       => array(),
		'missing_features'     => array(),
		'duplicate_uuids'      => array(),
		'relationship_errors'  => array(),
		'objects'              => array(
			'pages'        => array(),
			'tours'        => array(),
			'destinations' => array(),
		),
		'counts'               => array(
			'create'              => 0,
			'update'              => 0,
			'pages'               => 0,
			'tours'               => 0,
			'destinations'        => 0,
			'sections'            => 0,
			'media_placeholders'  => 0,
			'seo_profiles'        => 0,
			'relationships'       => 0,
		),
		'media_placeholders'   => array(),
		'seo_profile'          => array(),
		'link_map'             => array(),
		'brand_profile'        => null,
	);
}

/**
 * Run starter template preview/dry run without writes.
 *
 * @param string $template_id Template ID.
 * @return array|WP_Error
 */
function igp_pro_preview_starter_template( string $template_id ) {
	$template_id = sanitize_key( $template_id );
	$template = igp_pro_get_starter_template( $template_id );
	if ( is_wp_error( $template ) ) {
		return $template;
	}

	$result = igp_pro_get_empty_starter_template_dry_run_result( $template_id );
	$result['template'] = array(
		'template_id' => $template['template_id'],
		'name'        => $template['name'],
		'version'     => $template['version'],
		'industry'    => $template['industry'],
	);
	$result['media_placeholders'] = $template['media_placeholders'] ?? array();
	$result['seo_profile']        = $template['seo_profile'] ?? array();
	$result['link_map']           = $template['link_map'] ?? array();
	$result['brand_profile']      = $template['brand_profile'] ?? null;

	$result['missing_blocks'] = igp_pro_get_missing_starter_template_blocks( $template );
	if ( ! empty( $result['missing_blocks'] ) ) {
		$result['valid'] = false;
		$result['errors'][] = __( 'Template requires blocks that are not registered.', 'igp-pro' );
	}

	$result['missing_features'] = igp_pro_get_disabled_starter_template_features( $template );
	if ( ! empty( $result['missing_features'] ) ) {
		$result['valid'] = false;
		$result['errors'][] = __( 'Template requires disabled feature flags.', 'igp-pro' );
	}

	foreach ( array( 'pages', 'tours', 'destinations' ) as $object_type ) {
		foreach ( $template[ $object_type ] ?? array() as $object ) {
			$object_preview = igp_pro_preview_starter_template_object( $template, $object_type, $object );
			if ( is_wp_error( $object_preview ) ) {
				$result['valid'] = false;
				$result['errors'][] = $object_preview->get_error_message();
				continue;
			}

			$result['objects'][ $object_type ][] = $object_preview;
			$result['counts'][ $object_type ]++;
			$result['counts']['sections'] += (int) ( $object_preview['section_count'] ?? 0 );
			$result['counts'][ 'create' === $object_preview['action'] ? 'create' : 'update' ]++;

			if ( ! empty( $object_preview['duplicate_post_ids'] ) ) {
				$result['duplicate_uuids'][] = array(
					'template_uuid' => $object_preview['template_uuid'],
					'object_type'   => $object_type,
					'post_ids'      => $object_preview['duplicate_post_ids'],
				);
			}

			if ( ! empty( $object_preview['relationship_errors'] ) ) {
				foreach ( $object_preview['relationship_errors'] as $relationship_error ) {
					$result['relationship_errors'][] = $relationship_error;
				}
			}
		}
	}

	$result['counts']['media_placeholders'] = count( $result['media_placeholders'] );
	$result['counts']['seo_profiles']       = empty( $result['seo_profile'] ) ? 0 : 1;
	$result['counts']['relationships']      = igp_pro_count_starter_template_relationships( $template );

	if ( ! empty( $result['duplicate_uuids'] ) ) {
		$result['warnings'][] = __( 'One or more template UUIDs already exist. A future import would update/merge instead of creating new objects.', 'igp-pro' );
	}

	if ( ! empty( $result['relationship_errors'] ) ) {
		$result['valid'] = false;
		$result['errors'][] = __( 'Template contains relationship mapping errors.', 'igp-pro' );
	}

	return $result;
}

/**
 * Alias for dry-run terminology.
 *
 * @param string $template_id Template ID.
 * @return array|WP_Error
 */
function igp_pro_dry_run_starter_template( string $template_id ) {
	return igp_pro_preview_starter_template( $template_id );
}

/**
 * Preview one declared object.
 *
 * @param array  $template    Template manifest.
 * @param string $object_type pages|tours|destinations.
 * @param array  $object      Object definition.
 * @return array|WP_Error
 */
function igp_pro_preview_starter_template_object( array $template, string $object_type, array $object ) {
	$template_uuid = sanitize_text_field( (string) ( $object['template_uuid'] ?? '' ) );
	$title         = sanitize_text_field( (string) ( $object['title'] ?? '' ) );
	$slug          = sanitize_title( (string) ( $object['slug'] ?? '' ) );
	$graph_file    = sanitize_text_field( (string) ( $object['content_graph'] ?? '' ) );
	$post_type     = igp_pro_get_starter_template_object_post_type( $object_type );

	if ( '' === $template_uuid || '' === $title || '' === $slug || '' === $graph_file ) {
		return new WP_Error( 'igp_pro_template_invalid_object', __( 'Starter template object definition is incomplete.', 'igp-pro' ) );
	}

	$dir = trailingslashit( (string) $template['_template_dir'] );
	$graph_path = realpath( $dir . ltrim( $graph_file, '/\\' ) );
	if ( false === $graph_path ) {
		return new WP_Error( 'igp_pro_template_graph_missing', __( 'Starter template content graph file is missing.', 'igp-pro' ) );
	}

	$graph = igp_pro_validate_starter_template_content_graph_file( $graph_path );
	if ( is_wp_error( $graph ) ) {
		return $graph;
	}

	$duplicates = igp_pro_find_existing_template_posts_by_uuid( $template_uuid, $post_type );
	$relationship_errors = igp_pro_validate_starter_template_object_relationships( $template, $object_type, $object );

	return array(
		'object_type'          => $object_type,
		'post_type'            => $post_type,
		'template_uuid'        => $template_uuid,
		'title'                => $title,
		'slug'                 => $slug,
		'action'               => empty( $duplicates ) ? 'create' : 'update',
		'duplicate_post_ids'   => $duplicates,
		'content_graph'        => $graph_file,
		'section_count'        => isset( $graph['sections'] ) && is_array( $graph['sections'] ) ? count( $graph['sections'] ) : 0,
		'block_ids'            => igp_pro_get_starter_template_graph_block_ids( $graph ),
		'seo'                  => isset( $object['seo'] ) && is_array( $object['seo'] ) ? $object['seo'] : array(),
		'relationships'        => isset( $object['relationships'] ) && is_array( $object['relationships'] ) ? $object['relationships'] : array(),
		'relationship_errors'  => $relationship_errors,
	);
}

/**
 * Map manifest object buckets to WordPress post types.
 *
 * @param string $object_type Object bucket.
 * @return string
 */
function igp_pro_get_starter_template_object_post_type( string $object_type ): string {
	switch ( $object_type ) {
		case 'tours':
			return 'tour';
		case 'destinations':
			return 'destination';
		case 'pages':
		default:
			return 'page';
	}
}

/**
 * Find existing posts by template UUID without writing data.
 *
 * @param string $template_uuid Template UUID.
 * @param string $post_type Post type.
 * @return int[]
 */
function igp_pro_find_existing_template_posts_by_uuid( string $template_uuid, string $post_type ): array {
	if ( '' === $template_uuid || ! function_exists( 'get_posts' ) ) {
		return array();
	}

	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => 20,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => '_igp_template_uuid',
					'value' => $template_uuid,
				),
			),
		)
	);

	return array_map( 'absint', is_array( $posts ) ? $posts : array() );
}

/**
 * Return block IDs used by a graph.
 *
 * @param array $graph Graph.
 * @return string[]
 */
function igp_pro_get_starter_template_graph_block_ids( array $graph ): array {
	$block_ids = array();
	foreach ( $graph['sections'] ?? array() as $section ) {
		if ( is_array( $section ) && ! empty( $section['block_id'] ) ) {
			$block_ids[] = sanitize_key( (string) $section['block_id'] );
		}
	}
	return array_values( array_unique( $block_ids ) );
}

/**
 * Validate relationship template UUID mappings for one object.
 *
 * @param array  $template Template manifest.
 * @param string $object_type Object bucket.
 * @param array  $object Object definition.
 * @return array<int,array<string,string>>
 */
function igp_pro_validate_starter_template_object_relationships( array $template, string $object_type, array $object ): array {
	$errors = array();
	$relationships = isset( $object['relationships'] ) && is_array( $object['relationships'] ) ? $object['relationships'] : array();
	if ( empty( $relationships ) ) {
		return $errors;
	}

	$known = igp_pro_get_starter_template_uuid_index( $template );
	$relationship_targets = array(
		'primary_destination_uuid'      => array( 'destination' ),
		'destination_uuids'             => array( 'destination' ),
		'route_stop_uuids'              => array( 'destination' ),
		'related_tour_uuids'            => array( 'tour' ),
		'related_destination_uuids'     => array( 'destination' ),
	);

	foreach ( $relationship_targets as $field => $allowed_types ) {
		if ( ! array_key_exists( $field, $relationships ) ) {
			continue;
		}

		$values = is_array( $relationships[ $field ] ) ? $relationships[ $field ] : array( $relationships[ $field ] );
		foreach ( $values as $value ) {
			$uuid = sanitize_text_field( (string) $value );
			if ( '' === $uuid ) {
				continue;
			}
			if ( ! isset( $known[ $uuid ] ) ) {
				$errors[] = array(
					'field' => $field,
					'uuid'  => $uuid,
					'error' => __( 'Referenced template UUID does not exist in this template.', 'igp-pro' ),
				);
				continue;
			}
			if ( ! in_array( $known[ $uuid ], $allowed_types, true ) ) {
				$errors[] = array(
					'field' => $field,
					'uuid'  => $uuid,
					'error' => __( 'Referenced template UUID has the wrong object type.', 'igp-pro' ),
				);
			}
		}
	}

	return $errors;
}

/**
 * Build UUID-to-object-type index for a template manifest.
 *
 * @param array $template Template manifest.
 * @return array<string,string>
 */
function igp_pro_get_starter_template_uuid_index( array $template ): array {
	$index = array();
	foreach ( array( 'pages' => 'page', 'tours' => 'tour', 'destinations' => 'destination' ) as $bucket => $type ) {
		foreach ( $template[ $bucket ] ?? array() as $object ) {
			if ( is_array( $object ) && ! empty( $object['template_uuid'] ) ) {
				$index[ sanitize_text_field( (string) $object['template_uuid'] ) ] = $type;
			}
		}
	}
	return $index;
}

/**
 * Count relationship entries declared by a template.
 *
 * @param array $template Template manifest.
 * @return int
 */
function igp_pro_count_starter_template_relationships( array $template ): int {
	$count = 0;
	foreach ( array( 'pages', 'tours', 'destinations' ) as $bucket ) {
		foreach ( $template[ $bucket ] ?? array() as $object ) {
			if ( empty( $object['relationships'] ) || ! is_array( $object['relationships'] ) ) {
				continue;
			}
			foreach ( $object['relationships'] as $value ) {
				$count += is_array( $value ) ? count( array_filter( $value ) ) : ( '' !== (string) $value ? 1 : 0 );
			}
		}
	}
	return $count;
}
