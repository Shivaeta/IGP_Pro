<?php
/**
 * AI Copilot draft validator.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

class IGP_AI_Copilot_Draft_Validator {
	/** Validate normalized draft. */
	public static function validate( array $normalized_draft ): array|WP_Error {
		$errors = array();
		$warnings = array();
		$contract = function_exists( 'igp_ai_copilot_get_yaml_contract' ) ? igp_ai_copilot_get_yaml_contract() : array();
		$content_types = isset( $contract['supported_content_types'] ) && is_array( $contract['supported_content_types'] ) ? $contract['supported_content_types'] : array();

		$safety = self::scan_safety( $normalized_draft );
		if ( is_wp_error( $safety ) ) {
			$errors[] = self::issue( $safety->get_error_code(), $safety->get_error_message(), (string) ( $safety->get_error_data()['field'] ?? 'draft' ) );
		}

		if ( ! isset( $normalized_draft['version'] ) || 1 !== (int) $normalized_draft['version'] ) {
			$errors[] = self::issue( 'igp_ai_unsupported_version', __( 'Supported YAML version 1 is required.', 'igp-pro' ), 'version' );
		}
		$content_type = isset( $normalized_draft['content_type'] ) ? (string) $normalized_draft['content_type'] : '';
		if ( '' === $content_type || ! in_array( $content_type, $content_types, true ) ) {
			$errors[] = self::issue( 'igp_ai_unsupported_content_type', __( 'Supported content_type is required.', 'igp-pro' ), 'content_type' );
		}
		if ( empty( $normalized_draft['title'] ) || ! is_scalar( $normalized_draft['title'] ) || '' === trim( (string) $normalized_draft['title'] ) ) {
			$errors[] = self::issue( 'igp_ai_title_required', __( 'Title is required.', 'igp-pro' ), 'title' );
		}
		if ( empty( $normalized_draft['blocks'] ) || ! is_array( $normalized_draft['blocks'] ) ) {
			$errors[] = self::issue( 'igp_ai_blocks_required', __( 'A non-empty blocks array is required.', 'igp-pro' ), 'blocks' );
		}

		if ( isset( $normalized_draft['seo'] ) ) {
			if ( ! is_array( $normalized_draft['seo'] ) ) {
				$errors[] = self::issue( 'igp_ai_seo_invalid', __( 'SEO must be an object of plain text fields.', 'igp-pro' ), 'seo' );
			} else {
				foreach ( $normalized_draft['seo'] as $key => $value ) {
					if ( is_array( $value ) ) {
						foreach ( $value as $i => $item ) {
							if ( ! is_scalar( $item ) ) {
								$errors[] = self::issue( 'igp_ai_seo_plain_text_required', __( 'SEO fields must be plain text.', 'igp-pro' ), 'seo.' . $key . '.' . $i );
							}
						}
					} elseif ( ! is_scalar( $value ) && null !== $value ) {
						$errors[] = self::issue( 'igp_ai_seo_plain_text_required', __( 'SEO fields must be plain text.', 'igp-pro' ), 'seo.' . $key );
					}
				}
			}
		}

		foreach ( (array) ( $normalized_draft['blocks'] ?? array() ) as $index => $block ) {
			$field_base = 'blocks.' . $index;
			if ( ! is_array( $block ) ) {
				$errors[] = self::issue( 'igp_ai_block_object_required', __( 'Every block must be an object.', 'igp-pro' ), $field_base );
				continue;
			}
			if ( empty( $block['block'] ) || ! is_scalar( $block['block'] ) ) {
				$errors[] = self::issue( 'igp_ai_block_name_required', __( 'Every block requires a block name.', 'igp-pro' ), $field_base . '.block' );
				continue;
			}

			$mapping = class_exists( 'IGP_AI_Copilot_Block_Map' ) ? IGP_AI_Copilot_Block_Map::resolve_block( (string) $block['block'] ) : array( 'status' => 'unknown' );
			if ( 'mapped' !== ( $mapping['status'] ?? '' ) ) {
				$errors[] = self::issue( 'igp_ai_block_requires_review', __( 'Unknown or unavailable blocks require manual review before compile/save.', 'igp-pro' ), $field_base . '.block' );
				continue;
			}

			$block_id = (string) ( $mapping['block_id'] ?? '' );
			self::validate_block_requirements( $block_id, $block, $field_base, $errors, $warnings );
		}

		return array(
			'valid'    => empty( $errors ),
			'errors'   => $errors,
			'warnings' => $warnings,
		);
	}

	private static function validate_block_requirements( string $block_id, array $block, string $field_base, array &$errors, array &$warnings ): void {
		if ( 'faq' === $block_id ) {
			$items = self::first_array( $block, array( 'items', 'questions' ) );
			if ( empty( $items ) ) {
				$errors[] = self::issue( 'igp_ai_faq_items_required', __( 'FAQ blocks require question/answer items.', 'igp-pro' ), $field_base . '.items' );
			} else {
				$count = 0;
				foreach ( $items as $i => $item ) {
					$q = is_array( $item ) ? self::first_string( $item, array( 'question', 'q', 'title' ) ) : '';
					$a = is_array( $item ) ? self::first_string( $item, array( 'answer', 'a', 'text', 'description' ) ) : '';
					if ( '' === $q || '' === $a ) {
						$errors[] = self::issue( 'igp_ai_faq_question_answer_required', __( 'Each FAQ item requires question and answer.', 'igp-pro' ), $field_base . '.items.' . $i );
					} else {
						$count++;
					}
				}
				if ( $count > 0 && $count < 5 ) {
					$warnings[] = self::issue( 'igp_ai_faq_low_count', __( 'FAQ block has fewer than 5 questions.', 'igp-pro' ), $field_base . '.items' );
				}
			}
		}
		if ( 'itinerary' === $block_id ) {
			$items = self::first_array( $block, array( 'items', 'days' ) );
			if ( empty( $items ) ) {
				$errors[] = self::issue( 'igp_ai_itinerary_items_required', __( 'Itinerary blocks require items.', 'igp-pro' ), $field_base . '.items' );
			}
		}
		if ( 'cta' === $block_id ) {
			$label = self::first_string( $block, array( 'cta.label', 'label', 'cta_label' ) );
			$intent = self::first_string( $block, array( 'cta.intent', 'intent' ) );
			if ( '' === $label && '' === $intent ) {
				$errors[] = self::issue( 'igp_ai_cta_label_or_intent_required', __( 'CTA blocks require a label or intent.', 'igp-pro' ), $field_base . '.cta' );
			}
		}
		if ( isset( $block['media'] ) ) {
			if ( ! is_array( $block['media'] ) ) {
				$errors[] = self::issue( 'igp_ai_media_object_required', __( 'Media must be an object with prompt, alt, or URL.', 'igp-pro' ), $field_base . '.media' );
			} else {
				foreach ( $block['media'] as $key => $value ) {
					if ( ! is_scalar( $value ) && null !== $value ) {
						$errors[] = self::issue( 'igp_ai_media_safe_scalar_required', __( 'Media fields must be safe scalar values. Do not provide blobs or attachment IDs.', 'igp-pro' ), $field_base . '.media.' . $key );
					}
					if ( in_array( (string) $key, array( 'attachment_id', 'id' ), true ) && '' !== (string) $value ) {
						$warnings[] = self::issue( 'igp_ai_media_attachment_ignored', __( 'AI-provided attachment IDs are ignored; use media prompt/alt or reviewed URLs.', 'igp-pro' ), $field_base . '.media.' . $key );
					}
				}
			}
		}
	}

	private static function scan_safety( $value, string $field = 'draft' ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $child ) {
				$result = self::scan_safety( $child, $field . '.' . (string) $key );
				if ( is_wp_error( $result ) ) { return $result; }
			}
			return true;
		}
		if ( is_string( $value ) ) {
			$patterns = array(
				'igp_ai_php_rejected' => '/<\?(?:php|=)?/i',
				'igp_ai_script_rejected' => '/<\s*\/?\s*script\b/i',
				'igp_ai_inline_event_rejected' => '/\son[a-z0-9_:-]+\s*=/i',
				'igp_ai_protocol_rejected' => '/(?:javascript|vbscript|data|file|phar)\s*:/i',
				'igp_ai_html_rejected' => '/<\/?(?:iframe|object|embed|form|input|button|link|meta|style|svg|math|img|video|audio)\b/i',
			);
			foreach ( $patterns as $code => $pattern ) {
				if ( preg_match( $pattern, $value ) ) {
					return new WP_Error( $code, __( 'Unsafe AI draft content was rejected.', 'igp-pro' ), array( 'field' => $field ) );
				}
			}
		}
		return true;
	}

	private static function first_array( array $source, array $paths ): array {
		foreach ( $paths as $path ) {
			$value = self::read_path( $source, $path );
			if ( is_array( $value ) ) { return $value; }
		}
		return array();
	}

	private static function first_string( array $source, array $paths ): string {
		foreach ( $paths as $path ) {
			$value = self::read_path( $source, $path );
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) { return trim( (string) $value ); }
		}
		return '';
	}

	private static function read_path( array $source, string $path ) {
		$value = $source;
		foreach ( explode( '.', $path ) as $part ) {
			if ( is_array( $value ) && array_key_exists( $part, $value ) ) { $value = $value[ $part ]; } else { return null; }
		}
		return $value;
	}

	private static function issue( string $code, string $message, string $field ): array {
		return array( 'code' => sanitize_key( $code ), 'message' => $message, 'field' => $field );
	}
}
