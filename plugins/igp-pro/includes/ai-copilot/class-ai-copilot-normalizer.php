<?php
/**
 * AI Copilot draft normalizer.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

class IGP_AI_Copilot_Normalizer {
	/** Normalize parsed YAML into deterministic draft shape. */
	public static function normalize( array $draft ): array|WP_Error {
		if ( empty( $draft ) ) {
			return new WP_Error( 'igp_ai_empty_draft', __( 'Parsed YAML draft is empty.', 'igp-pro' ) );
		}

		$normalized = self::normalize_value( $draft );
		if ( ! is_array( $normalized ) ) {
			return new WP_Error( 'igp_ai_invalid_draft_shape', __( 'AI YAML draft must be an object.', 'igp-pro' ) );
		}

		if ( isset( $normalized['content_type'] ) ) {
			$normalized['content_type'] = self::normalize_slug( (string) $normalized['content_type'] );
		}
		if ( isset( $normalized['version'] ) && is_string( $normalized['version'] ) && is_numeric( $normalized['version'] ) ) {
			$normalized['version'] = (int) $normalized['version'];
		}
		if ( isset( $normalized['cta_goal'] ) ) {
			$normalized['cta_goal'] = self::normalize_cta_intent( (string) $normalized['cta_goal'] );
		}
		if ( isset( $normalized['seo'] ) && is_array( $normalized['seo'] ) ) {
			$normalized['seo'] = self::normalize_seo( $normalized['seo'] );
		}

		if ( isset( $normalized['blocks'] ) && is_array( $normalized['blocks'] ) ) {
			$blocks = array();
			foreach ( $normalized['blocks'] as $index => $block ) {
				if ( is_array( $block ) ) {
					if ( isset( $block['block'] ) ) {
						$block['block'] = self::normalize_block_name( (string) $block['block'] );
					}
					if ( isset( $block['cta'] ) && is_array( $block['cta'] ) && isset( $block['cta']['intent'] ) ) {
						$block['cta']['intent'] = self::normalize_cta_intent( (string) $block['cta']['intent'] );
					}
					$blocks[] = $block;
				} else {
					$blocks[] = array(
						'block'   => '',
						'_raw'    => $block,
						'_status' => 'needs_review',
						'_warning' => sprintf( __( 'Block %d is not an object and requires review.', 'igp-pro' ), (int) $index ),
					);
				}
			}
			$normalized['blocks'] = $blocks;
		}

		return $normalized;
	}

	/** Recursively trim strings and preserve array order. */
	private static function normalize_value( $value ) {
		if ( is_string( $value ) ) {
			return trim( preg_replace( "/\r\n|\r/", "\n", $value ) ?? $value );
		}
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $key => $child ) {
				$out[ is_string( $key ) ? trim( $key ) : $key ] = self::normalize_value( $child );
			}
			return $out;
		}
		return $value;
	}

	private static function normalize_slug( string $value ): string {
		$value = strtolower( trim( $value ) );
		$value = str_replace( array( '-', ' ' ), '_', $value );
		return preg_replace( '/[^a-z0-9_]/', '', $value ) ?: '';
	}

	private static function normalize_block_name( string $value ): string {
		$value = strtolower( trim( $value ) );
		$value = str_replace( array( ' ', '/' ), '_', $value );
		$value = str_replace( '-', '_', $value );
		return preg_replace( '/[^a-z0-9_]/', '', $value ) ?: '';
	}

	private static function normalize_cta_intent( string $value ): string {
		$value = self::normalize_slug( $value );
		$map = array(
			'enquire' => 'enquiry',
			'inquiry' => 'enquiry',
			'enquiry' => 'enquiry',
			'book' => 'booking',
			'booking' => 'booking',
			'call' => 'call',
			'phone' => 'call',
			'contact' => 'contact',
			'quote' => 'quote',
			'whatsapp' => 'whatsapp',
			'download' => 'download',
			'brochure' => 'download',
			'learn_more' => 'learn_more',
		);
		return $map[ $value ] ?? $value;
	}

	private static function normalize_seo( array $seo ): array {
		if ( isset( $seo['secondary_keywords'] ) && is_string( $seo['secondary_keywords'] ) ) {
			$seo['secondary_keywords'] = array_values( array_filter( array_map( 'trim', preg_split( '/,|\n/', $seo['secondary_keywords'] ) ?: array() ) ) );
		}
		return $seo;
	}
}
