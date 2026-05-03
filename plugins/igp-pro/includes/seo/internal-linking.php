<?php
/**
 * IGP-native internal link intelligence.
 *
 * Suggestions are generated from structured Content Graph data, relationships,
 * headings, FAQ, itinerary fields, and bounded post queries. Approved links are
 * stored in the Content Graph and rendered visibly; rejected links are stored as
 * post meta. Nothing is auto-inserted without human approval.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_REJECTED_INTERNAL_LINKS_META_KEY' ) ) {
	define( 'IGP_PRO_REJECTED_INTERNAL_LINKS_META_KEY', '_igp_rejected_internal_link_opportunity_ids' );
}

/**
 * Generate native internal link opportunities for a post.
 *
 * @param int   $post_id Post ID.
 * @param array $args    Optional args.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_generate_internal_link_opportunities( int $post_id, array $args = array() ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return new WP_Error( 'igp_pro_internal_links_invalid_post', __( 'A valid post is required to generate internal link opportunities.', 'igp-pro' ) );
	}

	$graph = function_exists( 'igp_pro_load_content_graph' ) ? igp_pro_load_content_graph( $post_id ) : array();
	if ( is_wp_error( $graph ) ) {
		$graph = array( 'version' => 'v1', 'sections' => array() );
	}

	$limit          = isset( $args['limit'] ) ? max( 1, min( 50, absint( $args['limit'] ) ) ) : 24;
	$approved       = igp_pro_get_approved_internal_links( is_array( $graph ) ? $graph : array() );
	$rejected_ids   = igp_pro_get_rejected_internal_link_ids( $post_id );
	$existing_urls  = igp_pro_collect_existing_internal_link_urls( $post, is_array( $graph ) ? $graph : array() );
	$semantic_terms = igp_pro_collect_internal_link_terms( $post_id, is_array( $graph ) ? $graph : array() );

	$opportunities = array();
	$candidates    = igp_pro_get_internal_link_candidates( $post, $semantic_terms, $limit );

	foreach ( $candidates as $candidate ) {
		if ( ! $candidate instanceof WP_Post ) {
			continue;
		}
		$opportunity = igp_pro_build_internal_link_opportunity( $post, $candidate, $semantic_terms, $existing_urls );
		if ( empty( $opportunity['id'] ) ) {
			continue;
		}
		$opportunities[ $opportunity['id'] ] = $opportunity;
	}

	foreach ( igp_pro_get_relationship_internal_link_candidates( $post ) as $candidate ) {
		if ( ! $candidate instanceof WP_Post ) {
			continue;
		}
		$opportunity = igp_pro_build_internal_link_opportunity( $post, $candidate, $semantic_terms, $existing_urls, 'relationship' );
		if ( empty( $opportunity['id'] ) ) {
			continue;
		}
		$opportunity['priority'] = 'high';
		$opportunities[ $opportunity['id'] ] = $opportunity;
	}

	$approved_ids = array();
	foreach ( $approved as $link ) {
		if ( ! empty( $link['id'] ) ) {
			$approved_ids[] = sanitize_key( (string) $link['id'] );
		}
		if ( ! empty( $link['target_post_id'] ) ) {
			$approved_ids[] = igp_pro_internal_link_opportunity_id( $post_id, absint( $link['target_post_id'] ), (string) ( $link['anchor'] ?? $link['label'] ?? '' ), (string) ( $link['source'] ?? 'approved' ) );
		}
	}

	foreach ( $opportunities as $id => $opportunity ) {
		if ( in_array( $id, $approved_ids, true ) ) {
			$opportunities[ $id ]['status'] = 'approved';
		} elseif ( in_array( $id, $rejected_ids, true ) ) {
			$opportunities[ $id ]['status'] = 'rejected';
		} else {
			$opportunities[ $id ]['status'] = 'suggested';
		}
	}

	$opportunities = igp_pro_filter_internal_link_spam_patterns( array_values( $opportunities ) );
	usort(
		$opportunities,
		function ( array $a, array $b ): int {
			$weights = array( 'high' => 0, 'normal' => 1, 'low' => 2 );
			$ap = $weights[ $a['priority'] ?? 'normal' ] ?? 1;
			$bp = $weights[ $b['priority'] ?? 'normal' ] ?? 1;
			if ( $ap === $bp ) {
				return strcmp( (string) ( $a['anchor'] ?? '' ), (string) ( $b['anchor'] ?? '' ) );
			}
			return $ap <=> $bp;
		}
	);

	$opportunities = array_slice( $opportunities, 0, $limit );

	return array(
		'post_id'       => $post_id,
		'post_type'     => (string) $post->post_type,
		'generated_at'  => gmdate( 'c' ),
		'orphan_risk'   => igp_pro_get_orphan_risk_report( $post ),
		'anchor_report' => igp_pro_get_anchor_duplication_report( $opportunities ),
		'opportunities' => $opportunities,
		'approved'      => $approved,
		'rejected_ids'  => $rejected_ids,
	);
}

/**
 * Return approved internal links from a graph SEO object.
 *
 * @param array $graph Content Graph.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_get_approved_internal_links( array $graph ): array {
	$links = isset( $graph['seo']['internal_link_targets'] ) && is_array( $graph['seo']['internal_link_targets'] ) ? $graph['seo']['internal_link_targets'] : array();
	$out   = array();
	foreach ( $links as $link ) {
		$normalized = igp_pro_normalize_internal_link_target( $link );
		if ( ! empty( $normalized ) ) {
			$out[] = $normalized;
		}
	}
	return $out;
}

/**
 * Normalize a stored/approved link target.
 *
 * @param mixed $link Link target.
 * @return array<string,mixed>
 */
function igp_pro_normalize_internal_link_target( $link ): array {
	if ( ! is_array( $link ) ) {
		return array();
	}

	$target_post_id = isset( $link['target_post_id'] ) ? absint( $link['target_post_id'] ) : 0;
	$url            = isset( $link['url'] ) ? esc_url_raw( (string) $link['url'] ) : '';
	if ( $target_post_id > 0 && '' === $url ) {
		$url = get_permalink( $target_post_id );
		$url = is_string( $url ) ? esc_url_raw( $url ) : '';
	}

	$anchor = sanitize_text_field( (string) ( $link['anchor'] ?? $link['label'] ?? '' ) );
	if ( '' === $anchor && $target_post_id > 0 ) {
		$anchor = sanitize_text_field( get_the_title( $target_post_id ) );
	}

	if ( '' === $anchor || '' === $url ) {
		return array();
	}

	$id = isset( $link['id'] ) ? sanitize_key( (string) $link['id'] ) : '';
	if ( '' === $id ) {
		$id = igp_pro_internal_link_opportunity_id( 0, $target_post_id, $anchor, (string) ( $link['source'] ?? 'approved' ) );
	}

	return array(
		'id'             => $id,
		'target_post_id' => $target_post_id,
		'url'            => $url,
		'anchor'         => $anchor,
		'label'          => $anchor,
		'source'         => sanitize_key( (string) ( $link['source'] ?? 'approved' ) ),
		'context'        => sanitize_text_field( (string) ( $link['context'] ?? '' ) ),
		'status'         => 'approved',
		'approved_at'    => sanitize_text_field( (string) ( $link['approved_at'] ?? '' ) ),
	);
}

/**
 * Return rejected opportunity IDs.
 */
function igp_pro_get_rejected_internal_link_ids( int $post_id ): array {
	$ids = get_post_meta( $post_id, IGP_PRO_REJECTED_INTERNAL_LINKS_META_KEY, true );
	if ( ! is_array( $ids ) ) {
		return array();
	}
	return array_values( array_unique( array_filter( array_map( 'sanitize_key', $ids ) ) ) );
}

/**
 * Persist rejected opportunity IDs.
 */
function igp_pro_update_rejected_internal_link_ids( int $post_id, array $ids ): void {
	$ids = array_values( array_unique( array_filter( array_map( 'sanitize_key', $ids ) ) ) );
	update_post_meta( $post_id, IGP_PRO_REJECTED_INTERNAL_LINKS_META_KEY, $ids );
}

/**
 * Collect terms from headings, FAQ, itinerary, graph SEO, and projections.
 */
function igp_pro_collect_internal_link_terms( int $post_id, array $graph ): array {
	$terms = array();
	$post  = get_post( $post_id );
	if ( $post instanceof WP_Post ) {
		$terms[] = get_the_title( $post );
	}

	if ( ! empty( $graph['seo'] ) && is_array( $graph['seo'] ) ) {
		foreach ( array( 'h1', 'title', 'description' ) as $field ) {
			if ( ! empty( $graph['seo'][ $field ] ) && is_scalar( $graph['seo'][ $field ] ) ) {
				$terms[] = (string) $graph['seo'][ $field ];
			}
		}
		if ( ! empty( $graph['seo']['focus_topics'] ) && is_array( $graph['seo']['focus_topics'] ) ) {
			$terms = array_merge( $terms, array_map( 'strval', $graph['seo']['focus_topics'] ) );
		}
	}

	foreach ( $graph['sections'] ?? array() as $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$terms = array_merge( $terms, igp_pro_collect_internal_link_terms_from_value( $data ) );
	}

	if ( function_exists( 'igp_pro_project_content_graph' ) ) {
		$projection = igp_pro_project_content_graph( $graph, 'search_index_text', array( 'post_id' => $post_id ) );
		if ( is_string( $projection ) ) {
			$terms[] = $projection;
		}
	}

	$terms = array_map( 'igp_pro_internal_link_clean_text', $terms );
	$terms = array_filter(
		$terms,
		function ( string $term ): bool {
			return strlen( $term ) >= 3;
		}
	);

	return array_values( array_unique( $terms ) );
}

/**
 * Recursively collect semantic text for link matching.
 *
 * @param mixed $value Value.
 * @return array<int,string>
 */
function igp_pro_collect_internal_link_terms_from_value( $value ): array {
	$terms = array();
	if ( is_scalar( $value ) ) {
		$text = igp_pro_internal_link_clean_text( (string) $value );
		return '' !== $text ? array( $text ) : array();
	}
	if ( ! is_array( $value ) ) {
		return array();
	}
	foreach ( $value as $key => $child ) {
		$key = is_string( $key ) ? sanitize_key( $key ) : '';
		if ( preg_match( '/(^|_)(image|images|icon|icons|style|variant|layout|url|id|ids|color|class|css|enable|show)(_|$)/', $key ) ) {
			continue;
		}
		$terms = array_merge( $terms, igp_pro_collect_internal_link_terms_from_value( $child ) );
	}
	return $terms;
}

/**
 * Build bounded candidate set.
 *
 * @param WP_Post $post Post.
 * @param array   $terms Terms.
 * @param int     $limit Limit.
 * @return array<int,WP_Post>
 */
function igp_pro_get_internal_link_candidates( WP_Post $post, array $terms, int $limit = 24 ): array {
	$post_types = array( 'tour', 'destination', 'page' );
	if ( 'tour' === $post->post_type ) {
		$post_types = array( 'destination', 'tour', 'page' );
	} elseif ( 'destination' === $post->post_type ) {
		$post_types = array( 'tour', 'destination', 'page' );
	}

	$candidates = array();
	$query      = new WP_Query(
		array(
			'post_type'           => $post_types,
			'post_status'         => 'publish',
			'post__not_in'        => array( $post->ID ),
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
		)
	);

	foreach ( $query->posts as $candidate ) {
		if ( $candidate instanceof WP_Post ) {
			$candidates[ $candidate->ID ] = $candidate;
		}
	}

	foreach ( array_slice( $terms, 0, 10 ) as $term ) {
		$query = new WP_Query(
			array(
				'post_type'           => $post_types,
				'post_status'         => 'publish',
				'post__not_in'        => array( $post->ID ),
				'posts_per_page'      => 6,
				's'                   => $term,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		foreach ( $query->posts as $candidate ) {
			if ( $candidate instanceof WP_Post ) {
				$candidates[ $candidate->ID ] = $candidate;
			}
		}
		if ( count( $candidates ) >= $limit ) {
			break;
		}
	}

	return array_slice( array_values( $candidates ), 0, $limit );
}

/**
 * Return relationship-derived candidates.
 *
 * @param WP_Post $post Post.
 * @return array<int,WP_Post>
 */
function igp_pro_get_relationship_internal_link_candidates( WP_Post $post ): array {
	$ids = array();
	if ( class_exists( 'IGP_Relationships' ) ) {
		if ( 'tour' === $post->post_type ) {
			$primary = IGP_Relationships::get_primary_destination( $post->ID );
			if ( $primary > 0 ) {
				$ids[] = $primary;
			}
			$ids = array_merge( $ids, IGP_Relationships::get_destination_ids_for_tour( $post->ID ), IGP_Relationships::get_related_tours( $post->ID, 'internal-links' ), IGP_Relationships::get_related_destinations( $post->ID, 'internal-links' ) );
		} elseif ( 'destination' === $post->post_type ) {
			$related_tours = IGP_Relationships::get_tours_for_destination( $post->ID, array( 'posts_per_page' => 12 ) );
			$tour_posts    = $related_tours instanceof WP_Query ? $related_tours->posts : ( is_array( $related_tours ) ? $related_tours : array() );
			foreach ( $tour_posts as $tour ) {
				if ( $tour instanceof WP_Post ) {
					$ids[] = $tour->ID;
				}
			}
			$ids = array_merge( $ids, IGP_Relationships::get_related_destinations( $post->ID, 'internal-links' ) );
		}
	}

	$posts = array();
	foreach ( array_values( array_unique( array_map( 'absint', $ids ) ) ) as $id ) {
		if ( $id <= 0 || $id === $post->ID ) {
			continue;
		}
		$candidate = get_post( $id );
		if ( $candidate instanceof WP_Post && 'publish' === $candidate->post_status ) {
			$posts[] = $candidate;
		}
	}
	return $posts;
}

/**
 * Build a single opportunity.
 */
function igp_pro_build_internal_link_opportunity( WP_Post $source, WP_Post $target, array $terms, array $existing_urls, string $source_module = 'native' ): array {
	$url = get_permalink( $target );
	if ( ! is_string( $url ) || '' === $url ) {
		return array();
	}
	$url = esc_url_raw( $url );
	if ( in_array( $url, $existing_urls, true ) ) {
		return array();
	}

	$target_title = igp_pro_internal_link_clean_text( get_the_title( $target ) );
	if ( '' === $target_title ) {
		return array();
	}

	$context = igp_pro_match_internal_link_context( $target_title, $terms );
	$anchor  = '' !== $context ? $target_title : $target_title;
	$id      = igp_pro_internal_link_opportunity_id( $source->ID, $target->ID, $anchor, $source_module );

	return array(
		'id'             => $id,
		'source_post_id' => $source->ID,
		'target_post_id' => $target->ID,
		'target_type'    => (string) $target->post_type,
		'target_title'   => $target_title,
		'url'            => $url,
		'anchor'         => $anchor,
		'label'          => $anchor,
		'context'        => $context,
		'source'         => sanitize_key( $source_module ),
		'priority'       => '' !== $context ? 'normal' : 'low',
		'warnings'       => array(),
	);
}

/**
 * Compute opportunity ID.
 */
function igp_pro_internal_link_opportunity_id( int $source_post_id, int $target_post_id, string $anchor, string $source = 'native' ): string {
	return 'igp-link-' . substr( md5( absint( $source_post_id ) . '|' . absint( $target_post_id ) . '|' . strtolower( $anchor ) . '|' . sanitize_key( $source ) ), 0, 16 );
}

/**
 * Match target title against semantic terms.
 */
function igp_pro_match_internal_link_context( string $target_title, array $terms ): string {
	$needle = strtolower( $target_title );
	foreach ( $terms as $term ) {
		$term_clean = strtolower( igp_pro_internal_link_clean_text( $term ) );
		if ( '' !== $term_clean && false !== strpos( $term_clean, $needle ) ) {
			return wp_trim_words( $term, 18, '…' );
		}
	}
	return '';
}

/**
 * Collect existing internal URLs from rendered/post/approved content.
 */
function igp_pro_collect_existing_internal_link_urls( WP_Post $post, array $graph ): array {
	$urls = array();
	$content = (string) $post->post_content;
	if ( preg_match_all( '/href=["\']([^"\']+)["\']/i', $content, $matches ) ) {
		foreach ( $matches[1] as $url ) {
			if ( igp_pro_is_internal_url( $url ) ) {
				$urls[] = esc_url_raw( $url );
			}
		}
	}
	foreach ( igp_pro_get_approved_internal_links( $graph ) as $link ) {
		if ( ! empty( $link['url'] ) ) {
			$urls[] = esc_url_raw( (string) $link['url'] );
		}
	}
	return array_values( array_unique( array_filter( $urls ) ) );
}

/**
 * Determine whether URL is same-site.
 */
function igp_pro_is_internal_url( string $url ): bool {
	$host      = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	$link_host = wp_parse_url( $url, PHP_URL_HOST );
	return '' === (string) $link_host || strtolower( (string) $host ) === strtolower( (string) $link_host );
}

/**
 * Add warnings for repeated anchor text.
 */
function igp_pro_filter_internal_link_spam_patterns( array $opportunities ): array {
	$anchor_counts = array();
	foreach ( $opportunities as $opportunity ) {
		$anchor = strtolower( igp_pro_internal_link_clean_text( (string) ( $opportunity['anchor'] ?? '' ) ) );
		if ( '' === $anchor ) {
			continue;
		}
		$anchor_counts[ $anchor ] = ( $anchor_counts[ $anchor ] ?? 0 ) + 1;
	}
	foreach ( $opportunities as $index => $opportunity ) {
		$anchor = strtolower( igp_pro_internal_link_clean_text( (string) ( $opportunity['anchor'] ?? '' ) ) );
		if ( isset( $anchor_counts[ $anchor ] ) && $anchor_counts[ $anchor ] > 1 ) {
			$opportunities[ $index ]['warnings'][] = __( 'Repeated anchor text; review before approval.', 'igp-pro' );
			$opportunities[ $index ]['priority']   = 'low';
		}
	}
	return $opportunities;
}

/**
 * Return anchor duplication report.
 */
function igp_pro_get_anchor_duplication_report( array $opportunities ): array {
	$counts = array();
	foreach ( $opportunities as $opportunity ) {
		$anchor = strtolower( igp_pro_internal_link_clean_text( (string) ( $opportunity['anchor'] ?? '' ) ) );
		if ( '' !== $anchor ) {
			$counts[ $anchor ] = ( $counts[ $anchor ] ?? 0 ) + 1;
		}
	}
	$duplicates = array();
	foreach ( $counts as $anchor => $count ) {
		if ( $count > 1 ) {
			$duplicates[] = array( 'anchor' => $anchor, 'count' => $count );
		}
	}
	return array( 'duplicates' => $duplicates, 'has_duplicates' => ! empty( $duplicates ) );
}

/**
 * Return approximate orphan risk report.
 */
function igp_pro_get_orphan_risk_report( WP_Post $post ): array {
	$count = igp_pro_count_inbound_internal_links( $post );
	return array(
		'inbound_internal_links' => $count,
		'risk'                   => $count <= 0 ? 'high' : ( $count < 2 ? 'medium' : 'low' ),
		'summary'                => $count <= 0 ? __( 'No obvious inbound internal links found.', 'igp-pro' ) : sprintf( _n( '%d obvious inbound internal link found.', '%d obvious inbound internal links found.', $count, 'igp-pro' ), $count ),
	);
}

/**
 * Count inbound internal links by permalink/title references in bounded WP query.
 */
function igp_pro_count_inbound_internal_links( WP_Post $post ): int {
	$url   = get_permalink( $post );
	$title = get_the_title( $post );
	$count = 0;
	if ( ! is_string( $url ) ) {
		$url = '';
	}
	$query = new WP_Query(
		array(
			'post_type'           => array( 'page', 'post', 'tour', 'destination' ),
			'post_status'         => 'publish',
			'post__not_in'        => array( $post->ID ),
			'posts_per_page'      => 50,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	foreach ( $query->posts as $candidate ) {
		if ( ! $candidate instanceof WP_Post ) {
			continue;
		}
		$haystack = strtolower( (string) $candidate->post_content );
		if ( ( '' !== $url && false !== strpos( $haystack, strtolower( $url ) ) ) || ( '' !== $title && false !== strpos( $haystack, strtolower( $title ) ) ) ) {
			$count++;
		}
	}
	return $count;
}

/**
 * Approve selected internal link opportunities.
 *
 * @param int   $post_id Post ID.
 * @param array $opportunity_ids Opportunity IDs.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_approve_internal_link_opportunities( int $post_id, array $opportunity_ids ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return new WP_Error( 'igp_pro_internal_links_invalid_post', __( 'Invalid post ID.', 'igp-pro' ) );
	}

	$opportunity_ids = array_values( array_unique( array_filter( array_map( 'sanitize_key', $opportunity_ids ) ) ) );
	if ( empty( $opportunity_ids ) ) {
		return new WP_Error( 'igp_pro_internal_links_no_selection', __( 'Select at least one link opportunity.', 'igp-pro' ) );
	}

	$report = igp_pro_generate_internal_link_opportunities( $post_id, array( 'limit' => 50 ) );
	if ( is_wp_error( $report ) ) {
		return $report;
	}

	$available = array();
	foreach ( $report['opportunities'] as $opportunity ) {
		if ( in_array( $opportunity['status'] ?? 'suggested', array( 'approved', 'rejected' ), true ) ) {
			continue;
		}
		$available[ $opportunity['id'] ] = $opportunity;
	}

	$selected = array();
	foreach ( $opportunity_ids as $id ) {
		if ( isset( $available[ $id ] ) ) {
			$selected[] = $available[ $id ];
		}
	}

	if ( empty( $selected ) ) {
		return new WP_Error( 'igp_pro_internal_links_selection_unavailable', __( 'Selected opportunities are no longer available.', 'igp-pro' ) );
	}

	$graph = function_exists( 'igp_pro_load_content_graph' ) ? igp_pro_load_content_graph( $post_id ) : array();
	if ( is_wp_error( $graph ) || ! is_array( $graph ) ) {
		return new WP_Error( 'igp_pro_internal_links_graph_unavailable', __( 'Content Graph could not be loaded.', 'igp-pro' ) );
	}

	$snapshot_id = '';
	if ( function_exists( 'igp_create_snapshot' ) ) {
		$snapshot_id = igp_create_snapshot(
			'content_graph',
			$post_id,
			$graph,
			array(
				'actor_type'    => 'human',
				'source_module' => 'internal-linking',
				'reason'        => 'approve_internal_links',
			)
		);
		$snapshot_id = is_wp_error( $snapshot_id ) ? '' : (string) $snapshot_id;
	}

	if ( empty( $graph['seo'] ) || ! is_array( $graph['seo'] ) ) {
		$graph['seo'] = array();
	}
	if ( empty( $graph['seo']['internal_link_targets'] ) || ! is_array( $graph['seo']['internal_link_targets'] ) ) {
		$graph['seo']['internal_link_targets'] = array();
	}

	$existing = igp_pro_get_approved_internal_links( $graph );
	foreach ( $selected as $opportunity ) {
		$existing[] = array(
			'id'             => sanitize_key( (string) $opportunity['id'] ),
			'target_post_id' => absint( $opportunity['target_post_id'] ),
			'url'            => esc_url_raw( (string) $opportunity['url'] ),
			'anchor'         => sanitize_text_field( (string) $opportunity['anchor'] ),
			'label'          => sanitize_text_field( (string) $opportunity['label'] ),
			'source'         => sanitize_key( (string) $opportunity['source'] ),
			'context'        => sanitize_text_field( (string) ( $opportunity['context'] ?? '' ) ),
			'status'         => 'approved',
			'approved_at'    => gmdate( 'c' ),
		);
	}

	$graph['seo']['internal_link_targets'] = igp_pro_dedupe_approved_internal_links( $existing );
	$save = function_exists( 'igp_pro_save_content_graph' ) ? igp_pro_save_content_graph( $post_id, $graph ) : new WP_Error( 'igp_pro_internal_links_save_unavailable', __( 'Content Graph save service is unavailable.', 'igp-pro' ) );
	if ( is_wp_error( $save ) ) {
		return $save;
	}

	$rejected = array_diff( igp_pro_get_rejected_internal_link_ids( $post_id ), $opportunity_ids );
	igp_pro_update_rejected_internal_link_ids( $post_id, $rejected );

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => 'human',
				'operation'     => 'internal_links_approved',
				'object_type'   => 'post',
				'object_id'     => $post_id,
				'source_module' => 'internal-linking',
				'snapshot_id'   => $snapshot_id,
				'summary'       => sprintf( 'Approved %d internal link opportunity/opportunities.', count( $selected ) ),
			)
		);
	}

	return array( 'approved_count' => count( $selected ), 'snapshot_id' => $snapshot_id );
}

/**
 * Reject selected opportunities.
 *
 * @param int   $post_id Post ID.
 * @param array $opportunity_ids Opportunity IDs.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_reject_internal_link_opportunities( int $post_id, array $opportunity_ids ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return new WP_Error( 'igp_pro_internal_links_invalid_post', __( 'Invalid post ID.', 'igp-pro' ) );
	}
	$opportunity_ids = array_values( array_unique( array_filter( array_map( 'sanitize_key', $opportunity_ids ) ) ) );
	if ( empty( $opportunity_ids ) ) {
		return new WP_Error( 'igp_pro_internal_links_no_selection', __( 'Select at least one link opportunity.', 'igp-pro' ) );
	}
	$rejected = array_values( array_unique( array_merge( igp_pro_get_rejected_internal_link_ids( $post_id ), $opportunity_ids ) ) );
	igp_pro_update_rejected_internal_link_ids( $post_id, $rejected );
	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => 'human',
				'operation'     => 'internal_links_rejected',
				'object_type'   => 'post',
				'object_id'     => $post_id,
				'source_module' => 'internal-linking',
				'summary'       => sprintf( 'Rejected %d internal link opportunity/opportunities.', count( $opportunity_ids ) ),
			)
		);
	}
	return array( 'rejected_count' => count( $opportunity_ids ) );
}

/**
 * Deduplicate approved links by target/url/anchor.
 */
function igp_pro_dedupe_approved_internal_links( array $links ): array {
	$out  = array();
	$seen = array();
	foreach ( $links as $link ) {
		$normalized = igp_pro_normalize_internal_link_target( $link );
		if ( empty( $normalized ) ) {
			continue;
		}
		$key = strtolower( (string) $normalized['target_post_id'] . '|' . (string) $normalized['url'] . '|' . (string) $normalized['anchor'] );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$out[] = $normalized;
	}
	return $out;
}

/**
 * Render approved links visibly, never hidden/cloaked.
 */
function igp_pro_render_approved_internal_links( array $graph, array $context = array() ): string {
	$links = igp_pro_get_approved_internal_links( $graph );
	if ( empty( $links ) ) {
		return '';
	}

	$output = '<nav class="igp-block igp-block--approved-internal-links igp-approved-internal-links" aria-label="' . esc_attr__( 'Related internal links', 'igp-pro' ) . '">';
	$output .= '<h2 class="igp-approved-internal-links__heading">' . esc_html__( 'Related travel resources', 'igp-pro' ) . '</h2><ul>';
	foreach ( $links as $link ) {
		$output .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( (string) $link['url'] ), esc_html( (string) $link['anchor'] ) );
	}
	$output .= '</ul></nav>';
	return $output;
}

/**
 * Clean text for matching.
 */
function igp_pro_internal_link_clean_text( string $text ): string {
	$text = wp_strip_all_tags( html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) ) );
	$text = preg_replace( '/\s+/u', ' ', $text );
	return trim( (string) $text );
}

/**
 * Register admin-post handlers.
 */
function igp_pro_register_internal_linking_admin_handlers(): void {
	add_action( 'admin_post_igp_pro_approve_internal_links', 'igp_pro_handle_approve_internal_links' );
	add_action( 'admin_post_igp_pro_reject_internal_links', 'igp_pro_handle_reject_internal_links' );
}

/**
 * Handle approval form.
 */
function igp_pro_handle_approve_internal_links(): void {
	check_admin_referer( 'igp_pro_internal_links_action' );
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'seo' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to approve internal links.', 'igp-pro' ) );
	}
	$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
	$ids     = isset( $_POST['opportunity_ids'] ) && is_array( $_POST['opportunity_ids'] ) ? wp_unslash( $_POST['opportunity_ids'] ) : array();
	$result  = igp_pro_approve_internal_link_opportunities( $post_id, $ids );
	igp_pro_redirect_internal_link_action( $post_id, $result, 'approved' );
}

/**
 * Handle rejection form.
 */
function igp_pro_handle_reject_internal_links(): void {
	check_admin_referer( 'igp_pro_internal_links_action' );
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'seo' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to reject internal links.', 'igp-pro' ) );
	}
	$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
	$ids     = isset( $_POST['opportunity_ids'] ) && is_array( $_POST['opportunity_ids'] ) ? wp_unslash( $_POST['opportunity_ids'] ) : array();
	$result  = igp_pro_reject_internal_link_opportunities( $post_id, $ids );
	igp_pro_redirect_internal_link_action( $post_id, $result, 'rejected' );
}

/**
 * Redirect after link action.
 */
function igp_pro_redirect_internal_link_action( int $post_id, $result, string $success_key ): void {
	$args = array( 'page' => 'igp-pro-seo-performance', 'post_id' => $post_id, 'igp_internal_links' => '1' );
	if ( is_wp_error( $result ) ) {
		$args['internal-link-error'] = rawurlencode( $result->get_error_message() );
	} else {
		$args[ $success_key ] = '1';
	}
	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}
