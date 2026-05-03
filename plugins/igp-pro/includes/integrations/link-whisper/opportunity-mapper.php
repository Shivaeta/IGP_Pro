<?php
/**
 * Map Link Whisper/native suggestion shapes into IGP reviewable opportunities.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Map arbitrary external suggestions into IGP opportunity objects.
 *
 * This does not write to the Content Graph. Human review and approval remain
 * mandatory through igp_pro_approve_internal_link_opportunities().
 *
 * @param int   $source_post_id Source post ID.
 * @param array $suggestions    Raw suggestions from an external/link tool.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_link_whisper_map_suggestions( int $source_post_id, array $suggestions ): array {
	$source = get_post( $source_post_id );
	if ( ! $source instanceof WP_Post ) {
		return array();
	}

	$mapped = array();
	foreach ( $suggestions as $suggestion ) {
		if ( ! is_array( $suggestion ) ) {
			continue;
		}

		$target_id = absint( $suggestion['target_post_id'] ?? $suggestion['post_id'] ?? $suggestion['target_id'] ?? 0 );
		$url       = esc_url_raw( (string) ( $suggestion['url'] ?? $suggestion['target_url'] ?? '' ) );
		$anchor    = sanitize_text_field( (string) ( $suggestion['anchor'] ?? $suggestion['label'] ?? $suggestion['keyword'] ?? '' ) );

		if ( $target_id <= 0 && '' !== $url ) {
			$target_id = url_to_postid( $url );
		}

		if ( $target_id <= 0 || $target_id === $source_post_id ) {
			continue;
		}

		$target = get_post( $target_id );
		if ( ! $target instanceof WP_Post || 'publish' !== $target->post_status ) {
			continue;
		}

		if ( '' === $url ) {
			$permalink = get_permalink( $target_id );
			$url       = is_string( $permalink ) ? esc_url_raw( $permalink ) : '';
		}
		if ( '' === $anchor ) {
			$anchor = sanitize_text_field( get_the_title( $target_id ) );
		}
		if ( '' === $url || '' === $anchor ) {
			continue;
		}

		$id       = function_exists( 'igp_pro_internal_link_opportunity_id' ) ? igp_pro_internal_link_opportunity_id( $source_post_id, $target_id, $anchor, 'link-whisper' ) : 'igp-lw-' . substr( md5( $source_post_id . '|' . $target_id . '|' . strtolower( $anchor ) ), 0, 16 );
		$mapped[] = array(
			'id'             => $id,
			'source_post_id' => $source_post_id,
			'target_post_id' => $target_id,
			'target_type'    => (string) get_post_type( $target_id ),
			'target_title'   => get_the_title( $target_id ),
			'url'            => $url,
			'anchor'         => $anchor,
			'label'          => $anchor,
			'context'        => sanitize_text_field( (string) ( $suggestion['context'] ?? $suggestion['sentence'] ?? '' ) ),
			'source'         => 'link-whisper',
			'priority'       => 'normal',
			'status'         => 'suggested',
			'warnings'       => array(),
		);
	}

	return $mapped;
}

/**
 * Return reviewable opportunities from IGP native intelligence and mapped Link Whisper data where available.
 */
function igp_pro_link_whisper_get_reviewable_opportunities( int $post_id ): array {
	$native = function_exists( 'igp_pro_generate_internal_link_opportunities' ) ? igp_pro_generate_internal_link_opportunities( $post_id ) : array();
	if ( is_wp_error( $native ) ) {
		$native = array();
	}

	$opportunities = isset( $native['opportunities'] ) && is_array( $native['opportunities'] ) ? $native['opportunities'] : array();

	/**
	 * Allows a Link Whisper adapter or site-specific bridge to provide suggestions
	 * in a controlled, reviewable shape without writing to the Content Graph.
	 *
	 * @param array $suggestions Raw external suggestions.
	 * @param int   $post_id     Source post ID.
	 */
	$external = apply_filters( 'igp_pro_link_whisper_external_suggestions', array(), $post_id );
	if ( is_array( $external ) && ! empty( $external ) ) {
		$opportunities = array_merge( $opportunities, igp_pro_link_whisper_map_suggestions( $post_id, $external ) );
	}

	$seen = array();
	$out  = array();
	foreach ( $opportunities as $opportunity ) {
		if ( ! is_array( $opportunity ) || empty( $opportunity['id'] ) ) {
			continue;
		}
		$id = sanitize_key( (string) $opportunity['id'] );
		if ( isset( $seen[ $id ] ) ) {
			continue;
		}
		$seen[ $id ] = true;
		$out[]       = $opportunity;
	}

	return $out;
}
