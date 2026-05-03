<?php
/**
 * Media inventory service for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_MEDIA_INVENTORY_CACHE_GROUP' ) ) {
	define( 'IGP_PRO_MEDIA_INVENTORY_CACHE_GROUP', 'igp_pro_media_inventory' );
}

/**
 * Determine whether the Media Optimizer module is enabled.
 */
function igp_pro_media_optimizer_enabled(): bool {
	return function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_media_optimizer' );
}

/**
 * Get media inventory for a page, tour, or destination.
 *
 * @param int   $post_id Post ID.
 * @param array $args    Optional args.
 * @return array|WP_Error
 */
function igp_pro_get_media_inventory( int $post_id, array $args = array() ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return new WP_Error( 'igp_pro_media_post_not_found', __( 'Post could not be found for media inventory.', 'igp-pro' ) );
	}

	$allowed_post_types = apply_filters( 'igp_pro_media_inventory_post_types', array( 'page', 'tour', 'destination' ) );
	if ( is_array( $allowed_post_types ) && ! in_array( $post->post_type, $allowed_post_types, true ) ) {
		return new WP_Error( 'igp_pro_media_post_type_not_supported', __( 'Media inventory supports pages, tours, and destinations.', 'igp-pro' ) );
	}

	$force_refresh = ! empty( $args['force_refresh'] );
	$cache_key     = igp_pro_media_inventory_cache_key( $post );
	$cached        = wp_cache_get( $cache_key, IGP_PRO_MEDIA_INVENTORY_CACHE_GROUP );

	if ( ! $force_refresh && is_array( $cached ) ) {
		return $cached;
	}

	$transient_key = 'igp_media_inventory_' . md5( $cache_key );
	$transient     = get_transient( $transient_key );
	if ( ! $force_refresh && is_array( $transient ) ) {
		wp_cache_set( $cache_key, $transient, IGP_PRO_MEDIA_INVENTORY_CACHE_GROUP, 300 );
		return $transient;
	}

	$graph_result = function_exists( 'igp_pro_load_content_graph_for_editor' ) ? igp_pro_load_content_graph_for_editor( $post_id ) : igp_pro_load_content_graph( $post_id );
	$graph        = array();
	if ( is_array( $graph_result ) && isset( $graph_result['graph'] ) && is_array( $graph_result['graph'] ) ) {
		$graph = $graph_result['graph'];
	} elseif ( is_array( $graph_result ) && isset( $graph_result['sections'] ) ) {
		$graph = $graph_result;
	}

	$images = array();

	igp_pro_media_inventory_add_featured_image( $post, $images );
	igp_pro_media_inventory_add_seo_images( $post, $graph, $images );
	igp_pro_media_inventory_add_starter_placeholders( $post, $images );
	igp_pro_media_inventory_add_graph_images( $post, $graph, $images );

	$images = igp_pro_media_inventory_dedupe_images( $images );
	$images = igp_pro_media_inventory_mark_lcp_candidate( $images );

	$inventory = array(
		'post_id'       => (int) $post->ID,
		'post_type'     => (string) $post->post_type,
		'post_title'    => get_the_title( $post ),
		'generated_at'  => current_time( 'mysql' ),
		'cache_key'     => $cache_key,
		'images'        => $images,
		'lcp_candidate' => igp_pro_media_inventory_get_lcp_candidate( $images ),
		'counts'        => array(
			'total'              => count( $images ),
			'with_attachment_id' => count( array_filter( $images, static fn( $image ) => ! empty( $image['attachment_id'] ) ) ),
			'missing'            => count( array_filter( $images, static fn( $image ) => ! empty( $image['missing'] ) ) ),
		),
	);

	wp_cache_set( $cache_key, $inventory, IGP_PRO_MEDIA_INVENTORY_CACHE_GROUP, 300 );
	set_transient( $transient_key, $inventory, 5 * MINUTE_IN_SECONDS );

	return $inventory;
}

/**
 * Alias required by possible future integrations.
 */
function igp_get_media_inventory( int $post_id, array $args = array() ) {
	return igp_pro_get_media_inventory( $post_id, $args );
}

/**
 * Build a cache key based on the post modified timestamp and content graph meta timestamp proxy.
 */
function igp_pro_media_inventory_cache_key( WP_Post $post ): string {
	$graph_meta = (string) get_post_meta( (int) $post->ID, defined( 'IGP_PRO_CONTENT_GRAPH_META_KEY' ) ? IGP_PRO_CONTENT_GRAPH_META_KEY : '_igp_pro_content_graph', true );
	return 'post_' . (int) $post->ID . '_' . md5( (string) $post->post_modified_gmt . '|' . $graph_meta . '|' . (string) get_post_thumbnail_id( $post ) );
}

/**
 * Add a normalized image item to inventory.
 *
 * @param array<int,array<string,mixed>> $images Images passed by reference.
 */
function igp_pro_media_inventory_add_image( array &$images, array $image ): void {
	$attachment_id = absint( $image['attachment_id'] ?? $image['id'] ?? 0 );
	$url           = isset( $image['url'] ) ? esc_url_raw( (string) $image['url'] ) : '';

	if ( $attachment_id > 0 && '' === $url ) {
		$url = (string) wp_get_attachment_image_url( $attachment_id, 'full' );
	}

	if ( $attachment_id <= 0 && '' !== $url ) {
		$maybe_id = attachment_url_to_postid( $url );
		if ( $maybe_id > 0 ) {
			$attachment_id = $maybe_id;
		}
	}

	$alt = isset( $image['alt'] ) ? sanitize_text_field( (string) $image['alt'] ) : '';
	if ( '' === $alt && $attachment_id > 0 ) {
		$alt = sanitize_text_field( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
	}

	$attachment = $attachment_id > 0 ? get_post( $attachment_id ) : null;
	$missing    = false;
	if ( $attachment_id > 0 && ! $attachment instanceof WP_Post ) {
		$missing = true;
	}
	if ( $attachment_id <= 0 && '' === $url && empty( $image['placeholder'] ) ) {
		return;
	}

	$meta = $attachment_id > 0 ? wp_get_attachment_metadata( $attachment_id ) : array();
	if ( ! is_array( $meta ) ) {
		$meta = array();
	}

	$filename = '';
	$file     = '';
	if ( $attachment_id > 0 ) {
		$file = (string) get_attached_file( $attachment_id );
		if ( '' !== $file ) {
			$filename = wp_basename( $file );
		}
	}
	if ( '' === $filename && '' !== $url ) {
		$filename = wp_basename( wp_parse_url( $url, PHP_URL_PATH ) ?: $url );
	}

	$post_id        = absint( $image['post_id'] ?? 0 );
	$loading_policy = sanitize_key( (string) ( $image['loading_policy'] ?? '' ) );
	if ( $post_id > 0 && $attachment_id > 0 && function_exists( 'igp_pro_get_media_loading_policy' ) ) {
		$effective_policy = igp_pro_get_media_loading_policy( $post_id, $attachment_id, 'auto' );
		if ( 'auto' !== $effective_policy ) {
			$loading_policy = $effective_policy;
		}
	}

	$images[] = array(
		'source'        => sanitize_key( (string) ( $image['source'] ?? 'graph_image' ) ),
		'context'       => sanitize_text_field( (string) ( $image['context'] ?? '' ) ),
		'path'          => sanitize_text_field( (string) ( $image['path'] ?? '' ) ),
		'block_id'      => sanitize_key( (string) ( $image['block_id'] ?? '' ) ),
		'section_id'    => sanitize_key( (string) ( $image['section_id'] ?? '' ) ),
		'post_id'       => $post_id,
		'attachment_id' => $attachment_id,
		'url'           => $url,
		'alt'           => $alt,
		'filename'      => sanitize_file_name( $filename ),
		'width'         => isset( $meta['width'] ) ? absint( $meta['width'] ) : 0,
		'height'        => isset( $meta['height'] ) ? absint( $meta['height'] ) : 0,
		'filesize'      => $file && file_exists( $file ) ? (int) filesize( $file ) : 0,
		'mime_type'     => $attachment_id > 0 ? (string) get_post_mime_type( $attachment_id ) : '',
		'has_sizes'     => ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ),
		'is_lcp'        => false,
		'missing'       => $missing,
		'placeholder'   => ! empty( $image['placeholder'] ),
		'loading_policy'=> $loading_policy,
	);
}

/**
 * Add featured image.
 *
 * @param array<int,array<string,mixed>> $images Images.
 */
function igp_pro_media_inventory_add_featured_image( WP_Post $post, array &$images ): void {
	$thumb_id = get_post_thumbnail_id( $post );
	if ( $thumb_id > 0 ) {
		igp_pro_media_inventory_add_image(
			$images,
			array(
				'source'         => 'featured_image',
				'post_id'        => (int) $post->ID,
				'context'        => __( 'Featured image', 'igp-pro' ),
				'attachment_id'  => $thumb_id,
				'path'           => 'featured_image',
				'loading_policy' => 'eager_candidate',
			)
		);
	}
}

/**
 * Add OG/schema-related images.
 *
 * @param array<int,array<string,mixed>> $images Images.
 */
function igp_pro_media_inventory_add_seo_images( WP_Post $post, array $graph, array &$images ): void {
	$og_keys = array( '_igp_seo_og_image_id', '_igp_og_image_id', 'igp_og_image_id' );
	foreach ( $og_keys as $key ) {
		$og_image_id = absint( get_post_meta( (int) $post->ID, $key, true ) );
		if ( $og_image_id > 0 ) {
			igp_pro_media_inventory_add_image(
				$images,
				array(
					'source'        => 'og_image',
					'post_id'       => (int) $post->ID,
					'context'       => __( 'Open Graph image', 'igp-pro' ),
					'attachment_id' => $og_image_id,
					'path'          => $key,
				)
			);
			break;
		}
	}

	$schema_keys = array( '_igp_schema_image_id', '_igp_seo_schema_image_id', 'igp_schema_image_id' );
	foreach ( $schema_keys as $key ) {
		$schema_image_id = absint( get_post_meta( (int) $post->ID, $key, true ) );
		if ( $schema_image_id > 0 ) {
			igp_pro_media_inventory_add_image(
				$images,
				array(
					'source'        => 'schema_image',
					'post_id'       => (int) $post->ID,
					'context'       => __( 'Schema image', 'igp-pro' ),
					'attachment_id' => $schema_image_id,
					'path'          => $key,
				)
			);
			break;
		}
	}

	if ( function_exists( 'igp_pro_seo_get_primary_image' ) ) {
		$primary = igp_pro_seo_get_primary_image( (int) $post->ID, $graph );
		if ( '' !== $primary ) {
			igp_pro_media_inventory_add_image(
				$images,
				array(
					'source'  => 'schema_primary_image',
					'post_id' => (int) $post->ID,
					'context' => __( 'Schema primary image fallback', 'igp-pro' ),
					'url'     => $primary,
					'path'    => 'schema.primaryImageOfPage',
				)
			);
		}
	}
}

/**
 * Add imported starter media placeholder records where present.
 *
 * @param array<int,array<string,mixed>> $images Images.
 */
function igp_pro_media_inventory_add_starter_placeholders( WP_Post $post, array &$images ): void {
	$placeholders = get_post_meta( (int) $post->ID, '_igp_template_media_placeholders', true );
	if ( is_string( $placeholders ) ) {
		$decoded      = json_decode( $placeholders, true );
		$placeholders = is_array( $decoded ) ? $decoded : array();
	}
	if ( ! is_array( $placeholders ) ) {
		return;
	}

	foreach ( $placeholders as $index => $placeholder ) {
		if ( ! is_array( $placeholder ) ) {
			continue;
		}
		igp_pro_media_inventory_add_image(
			$images,
			array(
				'source'      => 'starter_placeholder',
				'post_id'     => (int) $post->ID,
				'context'     => sanitize_text_field( (string) ( $placeholder['role'] ?? __( 'Starter media placeholder', 'igp-pro' ) ) ),
				'url'         => '',
				'alt'         => sanitize_text_field( (string) ( $placeholder['alt'] ?? '' ) ),
				'path'        => '_igp_template_media_placeholders.' . absint( $index ),
				'placeholder' => true,
			)
		);
	}
}

/**
 * Add content graph media.
 *
 * @param array<int,array<string,mixed>> $images Images.
 */
function igp_pro_media_inventory_add_graph_images( WP_Post $post, array $graph, array &$images ): void {
	$sections = isset( $graph['sections'] ) && is_array( $graph['sections'] ) ? $graph['sections'] : array();

	foreach ( $sections as $index => $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}

		$block_id   = sanitize_key( (string) ( $section['block_id'] ?? '' ) );
		$section_id = sanitize_key( (string) ( $section['id'] ?? 'section_' . $index ) );
		$data       = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();

		if ( 'hero' === $block_id && isset( $data['background_image'] ) ) {
			igp_pro_media_inventory_add_image_from_value(
				$images,
				$data['background_image'],
				array(
					'source'         => 'hero_image',
					'post_id'        => (int) $post->ID,
					'context'        => __( 'Hero / likely LCP image', 'igp-pro' ),
					'path'           => 'sections.' . $index . '.data.background_image',
					'block_id'       => $block_id,
					'section_id'     => $section_id,
					'loading_policy' => 'eager',
				)
			);
		}

		if ( 'gallery' === $block_id && ! empty( $data['images'] ) ) {
			foreach ( igp_pro_normalize_list( $data['images'] ) as $gallery_index => $image ) {
				igp_pro_media_inventory_add_image_from_value(
					$images,
					$image,
					array(
						'source'         => 'gallery_image',
						'post_id'        => (int) $post->ID,
						'context'        => __( 'Gallery image', 'igp-pro' ),
						'path'           => 'sections.' . $index . '.data.images.' . $gallery_index,
						'block_id'       => $block_id,
						'section_id'     => $section_id,
						'loading_policy' => 'lazy',
					)
				);
			}
		}

		if ( in_array( $block_id, array( 'tour_cards', 'destination_cards', 'featured_listings', 'related_tours', 'related_destinations' ), true ) ) {
			igp_pro_media_inventory_add_card_block_images( $post, $block_id, $data, $images, $index, $section_id );
		}

		igp_pro_media_inventory_collect_recursive( $data, 'sections.' . $index . '.data', $images, $block_id, $section_id, (int) $post->ID );
	}
}

/**
 * Add image item from scalar or array value.
 *
 * @param array<int,array<string,mixed>> $images Images.
 * @param mixed                         $value  Raw image value.
 */
function igp_pro_media_inventory_add_image_from_value( array &$images, $value, array $base ): void {
	$item = $base;
	if ( is_array( $value ) ) {
		$item['attachment_id'] = absint( $value['id'] ?? $value['ID'] ?? $value['attachment_id'] ?? 0 );
		$item['url']           = isset( $value['url'] ) ? (string) $value['url'] : '';
		$item['alt']           = isset( $value['alt'] ) ? (string) $value['alt'] : '';
	} elseif ( is_numeric( $value ) ) {
		$item['attachment_id'] = absint( $value );
	} elseif ( is_string( $value ) ) {
		$item['url'] = $value;
	} else {
		return;
	}

	igp_pro_media_inventory_add_image( $images, $item );
}

/**
 * Add featured images from card/listing blocks without unbounded queries.
 *
 * @param array<int,array<string,mixed>> $images Images.
 */
function igp_pro_media_inventory_add_card_block_images( WP_Post $post, string $block_id, array $data, array &$images, int $section_index, string $section_id ): void {
	$post_type = in_array( $block_id, array( 'destination_cards', 'related_destinations' ), true ) ? 'destination' : 'tour';
	$limit     = isset( $data['limit'] ) ? max( 1, min( 12, absint( $data['limit'] ) ) ) : 6;
	$ids       = igp_pro_normalize_post_ids( $data['items'] ?? array() );

	if ( empty( $ids ) && 'related_tours' === $block_id && function_exists( 'igp_pro_get_related_tours' ) ) {
		$ids = igp_pro_get_related_tours( (int) $post->ID, 'media_inventory' );
	}

	if ( empty( $ids ) && 'related_destinations' === $block_id && function_exists( 'igp_pro_get_related_destinations' ) ) {
		$ids = igp_pro_get_related_destinations( (int) $post->ID, 'media_inventory' );
	}

	if ( empty( $ids ) ) {
		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => $limit,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		$ids   = is_array( $query->posts ) ? array_map( 'absint', $query->posts ) : array();
	}

	foreach ( array_slice( array_values( array_unique( array_filter( $ids ) ) ), 0, $limit ) as $card_index => $related_id ) {
		$thumb_id = get_post_thumbnail_id( $related_id );
		if ( $thumb_id <= 0 ) {
			continue;
		}
		igp_pro_media_inventory_add_image(
			$images,
			array(
				'source'         => 'card_image',
				'post_id'        => (int) $post->ID,
				'context'        => sprintf( __( '%1$s card image: %2$s', 'igp-pro' ), igp_pro_block_id_to_title( $block_id ), get_the_title( $related_id ) ),
				'attachment_id'  => $thumb_id,
				'path'           => 'sections.' . $section_index . '.data.card_posts.' . $card_index,
				'block_id'       => $block_id,
				'section_id'     => $section_id,
				'loading_policy' => 'lazy',
			)
		);
	}
}

/**
 * Recursive collection of image-shaped fields.
 *
 * @param mixed                         $value      Raw value.
 * @param array<int,array<string,mixed>> $images     Images.
 */
function igp_pro_media_inventory_collect_recursive( $value, string $path, array &$images, string $block_id = '', string $section_id = '', int $post_id = 0 ): void {
	if ( ! is_array( $value ) ) {
		return;
	}

	$has_image_shape = ( isset( $value['url'] ) || isset( $value['id'] ) || isset( $value['ID'] ) || isset( $value['attachment_id'] ) )
		&& ( false !== stripos( $path, 'image' ) || false !== stripos( $path, 'gallery' ) || false !== stripos( $path, 'media' ) || false !== stripos( $path, 'logo' ) || false !== stripos( $path, 'avatar' ) || false !== stripos( $path, 'photo' ) );

	if ( $has_image_shape ) {
		igp_pro_media_inventory_add_image_from_value(
			$images,
			$value,
			array(
				'source'         => 'block_image',
				'post_id'        => $post_id,
				'context'        => igp_pro_block_id_to_title( $block_id ),
				'path'           => $path,
				'block_id'       => $block_id,
				'section_id'     => $section_id,
				'loading_policy' => 'lazy',
			)
		);
	}

	foreach ( $value as $key => $child ) {
		igp_pro_media_inventory_collect_recursive( $child, $path . '.' . sanitize_key( (string) $key ), $images, $block_id, $section_id, $post_id );
	}
}

/**
 * Dedupe images by attachment ID, URL, or placeholder path.
 *
 * @param array<int,array<string,mixed>> $images Images.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_media_inventory_dedupe_images( array $images ): array {
	$seen   = array();
	$result = array();

	foreach ( $images as $image ) {
		$key = '';
		if ( ! empty( $image['attachment_id'] ) ) {
			$key = 'id:' . absint( $image['attachment_id'] );
		} elseif ( ! empty( $image['url'] ) ) {
			$key = 'url:' . strtolower( (string) $image['url'] );
		} else {
			$key = 'placeholder:' . (string) ( $image['path'] ?? count( $result ) );
		}

		if ( isset( $seen[ $key ] ) ) {
			continue;
		}

		$seen[ $key ] = true;
		$result[]     = $image;
	}

	return $result;
}

/**
 * Mark likely LCP image: hero first, then featured, then first image.
 *
 * @param array<int,array<string,mixed>> $images Images.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_media_inventory_mark_lcp_candidate( array $images ): array {
	$preferred_index = null;

	foreach ( $images as $index => $image ) {
		if ( 'hero_image' === ( $image['source'] ?? '' ) ) {
			$preferred_index = $index;
			break;
		}
	}

	if ( null === $preferred_index ) {
		foreach ( $images as $index => $image ) {
			if ( 'featured_image' === ( $image['source'] ?? '' ) ) {
				$preferred_index = $index;
				break;
			}
		}
	}

	if ( null === $preferred_index && ! empty( $images ) ) {
		$preferred_index = 0;
	}

	if ( null !== $preferred_index && isset( $images[ $preferred_index ] ) ) {
		$images[ $preferred_index ]['is_lcp'] = true;
	}

	return $images;
}

/**
 * Return likely LCP candidate from inventory.
 *
 * @param array<int,array<string,mixed>> $images Images.
 * @return array<string,mixed>|null
 */
function igp_pro_media_inventory_get_lcp_candidate( array $images ): ?array {
	foreach ( $images as $image ) {
		if ( ! empty( $image['is_lcp'] ) ) {
			return $image;
		}
	}

	return null;
}

/**
 * Invalidate inventory cache by post ID.
 */
function igp_pro_media_inventory_invalidate_post( int $post_id ): void {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$cache_key     = igp_pro_media_inventory_cache_key( $post );
	$transient_key = 'igp_media_inventory_' . md5( $cache_key );
	wp_cache_delete( $cache_key, IGP_PRO_MEDIA_INVENTORY_CACHE_GROUP );
	delete_transient( $transient_key );
}
