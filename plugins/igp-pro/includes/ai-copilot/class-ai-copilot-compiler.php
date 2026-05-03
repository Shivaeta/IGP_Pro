<?php
/**
 * AI Copilot compiler.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

class IGP_AI_Copilot_Compiler {
	/** Compile normalized draft into strict Content Graph result. */
	public static function compile( array $normalized_draft, array $context = array() ): array|WP_Error {
		$validation = class_exists( 'IGP_AI_Copilot_Draft_Validator' ) ? IGP_AI_Copilot_Draft_Validator::validate( $normalized_draft ) : new WP_Error( 'igp_ai_validator_missing', __( 'AI draft validator is unavailable.', 'igp-pro' ) );
		if ( is_wp_error( $validation ) ) { return $validation; }
		if ( empty( $validation['valid'] ) ) {
			return new WP_Error( 'igp_ai_draft_invalid', __( 'AI draft validation failed.', 'igp-pro' ), array( 'validation' => $validation ) );
		}

		$compile_context = array_merge( $context, array(
			'title' => (string) ( $normalized_draft['title'] ?? '' ),
			'content_type' => (string) ( $normalized_draft['content_type'] ?? '' ),
			'cta_goal' => (string) ( $normalized_draft['cta_goal'] ?? '' ),
			'ai_copilot' => true,
		) );

		$sections = array();
		$mapping_report = array();
		$warnings = isset( $validation['warnings'] ) && is_array( $validation['warnings'] ) ? $validation['warnings'] : array();
		$media_requirements = array();
		$total_confidence = 0.0;
		$count = 0;

		foreach ( (array) ( $normalized_draft['blocks'] ?? array() ) as $index => $ai_block ) {
			if ( ! is_array( $ai_block ) ) {
				return new WP_Error( 'igp_ai_invalid_block', __( 'AI block must be an object.', 'igp-pro' ) );
			}
			$mapping = class_exists( 'IGP_AI_Copilot_Block_Map' ) ? IGP_AI_Copilot_Block_Map::resolve_block( (string) ( $ai_block['block'] ?? '' ) ) : array( 'status' => 'unknown' );
			$mapping_report[] = array_merge( array( 'index' => (int) $index ), $mapping );
			if ( 'mapped' !== ( $mapping['status'] ?? '' ) ) {
				return new WP_Error( 'igp_ai_block_requires_review', __( 'Unknown or unavailable AI block cannot be compiled without manual mapping.', 'igp-pro' ), array( 'mapping_report' => $mapping_report ) );
			}

			$mapped = class_exists( 'IGP_AI_Copilot_Content_Mapper' ) ? IGP_AI_Copilot_Content_Mapper::map_block( $ai_block, $compile_context ) : new WP_Error( 'igp_ai_mapper_missing', __( 'AI content mapper is unavailable.', 'igp-pro' ) );
			if ( is_wp_error( $mapped ) ) { return $mapped; }
			foreach ( (array) ( $mapped['warnings'] ?? array() ) as $warning ) { $warnings[] = $warning; }
			$block_id = sanitize_key( (string) $mapped['block_id'] );
			$sections[] = array(
				'id' => 'ai_' . ( $index + 1 ) . '_' . $block_id,
				'block_id' => $block_id,
				'data' => isset( $mapped['data'] ) && is_array( $mapped['data'] ) ? $mapped['data'] : array(),
			);
			$media = self::media_requirement( $ai_block, (int) $index, $block_id );
			if ( null !== $media ) { $media_requirements[] = $media; }
			$total_confidence += (float) ( $mapping['confidence'] ?? 0 );
			$count++;
		}

		$graph = array( 'version' => 'v1', 'sections' => $sections );
		$graph_validation = function_exists( 'igp_pro_validate_content_graph' ) ? igp_pro_validate_content_graph( $graph ) : ( function_exists( 'igp_pro_validate_content_graph_payload' ) ? igp_pro_validate_content_graph_payload( $graph ) : true );
		if ( is_wp_error( $graph_validation ) ) { return $graph_validation; }

		return array(
			'content_graph' => $graph,
			'seo' => self::sanitize_seo( isset( $normalized_draft['seo'] ) && is_array( $normalized_draft['seo'] ) ? $normalized_draft['seo'] : array() ),
			'media_requirements' => $media_requirements,
			'relationship_hints' => self::relationship_hints( $normalized_draft ),
			'mapping_report' => $mapping_report,
			'validation' => $validation,
			'warnings' => $warnings,
			'confidence' => $count > 0 ? round( $total_confidence / $count, 2 ) : 0,
			'title' => sanitize_text_field( (string) ( $normalized_draft['title'] ?? '' ) ),
			'slug' => isset( $normalized_draft['slug'] ) ? sanitize_title( (string) $normalized_draft['slug'] ) : '',
			'content_type' => sanitize_key( (string) ( $normalized_draft['content_type'] ?? '' ) ),
		);
	}

	private static function media_requirement( array $block, int $index, string $block_id ): ?array {
		$media = isset( $block['media'] ) && is_array( $block['media'] ) ? $block['media'] : array();
		$prompt = self::read_string( $media, array( 'prompt', 'description', 'brief' ) );
		$alt = self::read_string( $media, array( 'alt', 'alt_text' ) );
		$url = self::read_string( $media, array( 'url', 'image_url' ) );
		if ( '' === $prompt && '' === $alt && '' === $url ) { return null; }
		return array( 'block_index' => $index, 'block_id' => $block_id, 'prompt' => sanitize_textarea_field( $prompt ), 'alt' => sanitize_text_field( $alt ), 'url' => esc_url_raw( $url ), 'status' => '' === $url ? 'pending_media' : 'provided_url', 'attachment_id' => null );
	}

	private static function relationship_hints( array $draft ): array {
		$out = array();
		foreach ( array( 'primary_destination', 'destination', 'audience', 'tone' ) as $field ) {
			if ( isset( $draft[ $field ] ) && is_scalar( $draft[ $field ] ) && '' !== trim( (string) $draft[ $field ] ) ) { $out[ $field ] = sanitize_text_field( (string) $draft[ $field ] ); }
		}
		if ( isset( $draft['relationships'] ) && is_array( $draft['relationships'] ) ) {
			foreach ( $draft['relationships'] as $key => $value ) {
				if ( is_scalar( $value ) ) { $out[ sanitize_key( (string) $key ) ] = sanitize_text_field( (string) $value ); }
				elseif ( is_array( $value ) ) { $out[ sanitize_key( (string) $key ) ] = array_map( 'sanitize_text_field', array_map( 'strval', array_filter( $value, 'is_scalar' ) ) ); }
			}
		}
		return $out;
	}

	private static function sanitize_seo( array $seo ): array {
		$out = array();
		foreach ( $seo as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( is_array( $value ) ) { $out[ $key ] = array_map( 'sanitize_text_field', array_map( 'strval', array_filter( $value, 'is_scalar' ) ) ); }
			elseif ( is_scalar( $value ) ) { $out[ $key ] = sanitize_textarea_field( (string) $value ); }
		}
		return $out;
	}

	private static function read_string( array $source, array $paths ): string { foreach ( $paths as $path ) { $v = $source; foreach ( explode( '.', $path ) as $part ) { if ( is_array( $v ) && array_key_exists( $part, $v ) ) { $v = $v[ $part ]; } else { $v = null; break; } } if ( is_scalar( $v ) && '' !== trim( (string) $v ) ) { return trim( (string) $v ); } } return ''; }
}
