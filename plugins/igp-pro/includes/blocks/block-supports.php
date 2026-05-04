<?php
/**
 * Block support helpers for semantic wrappers and accessibility attributes.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the next unique semantic block instance key for this request.
 *
 * @param string $block_id Block ID.
 * @param array<string,mixed> $context Render context.
 * @return string
 */
function igp_pro_get_semantic_block_instance_id( string $block_id, array $context = array() ): string {
	static $counts = array();

	$section = isset( $context['section'] ) && is_array( $context['section'] ) ? $context['section'] : array();
	if ( ! empty( $section['id'] ) ) {
		return sanitize_html_class( 'igp-section-' . sanitize_key( (string) $section['id'] ) );
	}

	$key = sanitize_key( $block_id );
	if ( ! isset( $counts[ $key ] ) ) {
		$counts[ $key ] = 0;
	}
	$counts[ $key ]++;

	$post_id = 0;
	if ( isset( $context['post_id'] ) ) {
		$post_id = absint( $context['post_id'] );
	} elseif ( function_exists( 'get_the_ID' ) ) {
		$post_id = absint( get_the_ID() );
	}

	return sanitize_html_class( 'igp-section-' . $key . '-' . $post_id . '-' . $counts[ $key ] );
}

/**
 * Prepare semantic metadata for one block render.
 *
 * @param string              $block_id Block ID.
 * @param array<string,mixed> $data Block data.
 * @param array<string,mixed> $context Render context.
 * @return array<string,mixed>
 */
function igp_pro_prepare_semantic_block_context( string $block_id, array $data, array $context = array() ): array {
	if ( empty( $context['igp_section_id'] ) ) {
		$context['igp_section_id'] = igp_pro_get_semantic_block_instance_id( $block_id, $context );
	}

	if ( empty( $context['igp_heading_id'] ) ) {
		$context['igp_heading_id'] = sanitize_html_class( $context['igp_section_id'] . '-heading' );
	}

	$context['igp_block_id'] = sanitize_key( $block_id );

	return $context;
}

/**
 * Build HTML attributes from a key-value map.
 *
 * @param array<string,mixed> $attributes Attributes.
 * @return string
 */
function igp_pro_build_html_attributes( array $attributes ): string {
	$parts = array();
	foreach ( $attributes as $name => $value ) {
		$name = sanitize_key( (string) $name );
		if ( '' === $name || false === $value || null === $value ) {
			continue;
		}
		if ( true === $value ) {
			$parts[] = esc_attr( $name );
			continue;
		}
		$parts[] = sprintf( '%s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
	}
	return implode( ' ', $parts );
}


/**
 * Demote a block render file's outer <section> to <div> when the central
 * renderer owns the semantic section wrapper. This avoids nested semantic
 * sections while preserving legacy classes and layout CSS.
 *
 * @param string $html Rendered block HTML.
 * @return string
 */
function igp_pro_demote_outer_section_for_central_wrapper( string $html ): string {
	$trimmed = ltrim( $html );
	$prefix  = substr( $html, 0, strlen( $html ) - strlen( $trimmed ) );
	if ( ! preg_match( '/^<section\b/i', $trimmed ) ) {
		return $html;
	}

	$trimmed = preg_replace( '/^<section\b/i', '<div', $trimmed, 1 );
	$trimmed = preg_replace( '/<\/section>\s*$/i', '</div>', $trimmed, 1 );
	return $prefix . (string) $trimmed;
}

/**
 * Apply semantic wrapper, stable IDs, and heading policy to rendered block HTML.
 *
 * @param string              $block_id Block ID.
 * @param string              $html Rendered block HTML.
 * @param array<string,mixed> $data Semantic V2 block data.
 * @param array<string,mixed> $context Render context.
 * @return string
 */
function igp_pro_apply_semantic_block_wrapper( string $block_id, string $html, array $data, array $context = array() ): string {
	$semantic_enabled = function_exists( 'igp_pro_semantic_outline_enabled' ) && igp_pro_semantic_outline_enabled();
	$style_enabled    = function_exists( 'igp_pro_should_apply_block_style_support' ) ? igp_pro_should_apply_block_style_support( $block_id, $data ) : ( function_exists( 'igp_pro_smart_block_variants_enabled' ) && igp_pro_smart_block_variants_enabled() );

	if ( ! $semantic_enabled && ! $style_enabled ) {
		return $html;
	}

	$block_id = sanitize_key( $block_id );
	$context  = igp_pro_prepare_semantic_block_context( $block_id, $data, $context );
	$heading  = $semantic_enabled && function_exists( 'igp_pro_normalize_block_heading' ) ? igp_pro_normalize_block_heading( $data, $block_id, $context ) : array( 'text' => '', 'level' => 'h2', 'eyebrow' => '', 'visible' => false );
	$section_id = sanitize_html_class( (string) $context['igp_section_id'] );
	$heading_id = sanitize_html_class( (string) $context['igp_heading_id'] );
	$has_heading = ! empty( $heading['visible'] ) && '' !== trim( (string) $heading['text'] );
	$heading_tag = ! empty( $heading['level'] ) ? strtolower( (string) $heading['level'] ) : 'h2';
	$heading_tag = in_array( $heading_tag, array( 'h2', 'h3', 'h4' ), true ) ? $heading_tag : 'h2';

	$body = igp_pro_demote_outer_section_for_central_wrapper( $html );
	$matched_existing_heading = false;

	if ( '' !== trim( (string) $heading['text'] ) ) {
		$needle = trim( wp_strip_all_tags( (string) $heading['text'] ) );
		$body = preg_replace_callback(
			'/<h([1-6])\b([^>]*)>(.*?)<\/h\1>/is',
			static function ( array $matches ) use ( $needle, $has_heading, $heading_tag, $heading_id, &$matched_existing_heading ) {
				if ( $matched_existing_heading ) {
					return $matches[0];
				}

				$current_text = trim( html_entity_decode( wp_strip_all_tags( $matches[3] ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
				if ( '' !== $needle && $current_text !== $needle ) {
					return $matches[0];
				}

				$matched_existing_heading = true;
				if ( ! $has_heading ) {
					return '';
				}

				$attrs = preg_replace( '/\s+id=("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', (string) $matches[2] );
				$attrs = preg_replace( '/\s+class=("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', (string) $attrs );
				$attrs = trim( (string) $attrs );
				$attrs = '' !== $attrs ? ' ' . $attrs : '';

				return sprintf(
					'<%1$s id="%2$s" class="igp-block__heading igp-block__heading--%1$s"%3$s>%4$s</%1$s>',
					esc_html( $heading_tag ),
					esc_attr( $heading_id ),
					$attrs,
					$matches[3]
				);
			},
			$body,
			1
		);
	}

	if ( $has_heading && ! $matched_existing_heading ) {
		$header  = '<header class="igp-block__header">';
		if ( '' !== trim( (string) $heading['eyebrow'] ) ) {
			$header .= '<p class="igp-block__eyebrow">' . esc_html( (string) $heading['eyebrow'] ) . '</p>';
		}
		$header .= sprintf(
			'<%1$s id="%2$s" class="igp-block__heading igp-block__heading--%1$s">%3$s</%1$s>',
			esc_html( $heading_tag ),
			esc_attr( $heading_id ),
			esc_html( (string) $heading['text'] )
		);
		$header .= '</header>';
		$body = $header . $body;
	}

	$block_class_suffix = function_exists( 'igp_pro_style_css_slug' ) ? igp_pro_style_css_slug( $block_id ) : str_replace( '_', '-', sanitize_key( $block_id ) );
	$classes = array(
		'igp-block',
		'igp-block--' . $block_class_suffix,
	);

	if ( $semantic_enabled ) {
		$classes[] = 'igp-semantic-block';
	}

	if ( $style_enabled ) {
		$classes[] = 'igp-styled-block';
	}

	if ( $style_enabled && function_exists( 'igp_pro_get_block_style_classes' ) ) {
		$classes = array_merge( $classes, igp_pro_get_block_style_classes( $block_id, $data ) );
	}

	$attributes = array(
		'id'              => $section_id,
		'class'           => implode( ' ', array_map( 'sanitize_html_class', $classes ) ),
		'data-igp-block'  => $block_id,
	);

	if ( $has_heading ) {
		$attributes['aria-labelledby'] = $heading_id;
	}

	return '<section ' . igp_pro_build_html_attributes( $attributes ) . '>' . $body . '</section>';
}
