<?php
/**
 * Shared heading support for V2 semantic block rendering.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return allowed block-level heading tags.
 *
 * @return string[]
 */
function igp_pro_get_allowed_block_heading_levels(): array {
	return array( 'h2', 'h3', 'h4' );
}

/**
 * Determine whether the semantic outline subsystem is enabled.
 *
 * @return bool
 */
function igp_pro_semantic_outline_enabled(): bool {
	return function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_semantic_outline' );
}

/**
 * Normalize a heading object from V2 or legacy V1 block data.
 *
 * @param array<string,mixed> $data Block data.
 * @param string             $block_id Block ID.
 * @param array<string,mixed> $context Render/validation context.
 * @return array{text:string,level:string,eyebrow:string,visible:bool,source:string}
 */
function igp_pro_normalize_block_heading( array $data, string $block_id = '', array $context = array() ): array {
	$allowed = igp_pro_get_allowed_block_heading_levels();
	$heading = array(
		'text'    => '',
		'level'   => 'h2',
		'eyebrow' => '',
		'visible' => true,
		'source'  => 'default',
	);

	if ( isset( $data['heading'] ) && is_array( $data['heading'] ) ) {
		$heading['text']    = trim( igp_pro_to_string( $data['heading']['text'] ?? '' ) );
		$heading['level']   = sanitize_key( igp_pro_to_string( $data['heading']['level'] ?? 'h2' ) );
		$heading['eyebrow'] = trim( igp_pro_to_string( $data['heading']['eyebrow'] ?? '' ) );
		$heading['visible'] = array_key_exists( 'visible', $data['heading'] ) ? (bool) $data['heading']['visible'] : true;
		$heading['source']  = 'structured';
	} elseif ( isset( $data['heading'] ) && is_scalar( $data['heading'] ) ) {
		$heading['text']   = trim( igp_pro_to_string( $data['heading'] ) );
		$heading['source'] = 'legacy_heading';
	} elseif ( isset( $data['title'] ) && is_scalar( $data['title'] ) ) {
		$heading['text']   = trim( igp_pro_to_string( $data['title'] ) );
		$heading['source'] = 'legacy_title';
	}

	if ( '' === $heading['eyebrow'] && isset( $data['eyebrow'] ) && is_scalar( $data['eyebrow'] ) ) {
		$heading['eyebrow'] = trim( igp_pro_to_string( $data['eyebrow'] ) );
	}

	if ( ! in_array( $heading['level'], $allowed, true ) ) {
		$heading['level'] = 'h2';
	}

	return $heading;
}

/**
 * Convert V2 heading data into legacy keys used by V1 render callbacks.
 * This keeps existing render files backward-compatible while the central
 * renderer owns semantic heading policy.
 *
 * @param string              $block_id Block ID.
 * @param array<string,mixed> $data Block data.
 * @return array<string,mixed>
 */
function igp_pro_prepare_legacy_heading_render_data( string $block_id, array $data ): array {
	$heading = igp_pro_normalize_block_heading( $data, $block_id );
	$text    = $heading['visible'] ? $heading['text'] : '';

	if ( in_array( sanitize_key( $block_id ), array( 'hero', 'cta' ), true ) ) {
		$data['heading'] = $text;
	} else {
		$data['title'] = $text;
	}

	if ( '' !== $heading['eyebrow'] ) {
		$data['eyebrow'] = $heading['eyebrow'];
	}

	return $data;
}

/**
 * Validate a block heading object after schema/default resolution.
 *
 * @param string              $block_id Block ID.
 * @param array<string,mixed> $data Block data.
 * @param array<string,mixed> $schema Block schema.
 * @return true|WP_Error
 */
function igp_pro_validate_block_heading_data( string $block_id, array $data, array $schema = array() ) {
	if ( ! isset( $schema['fields']['heading'] ) || ! is_array( $schema['fields']['heading'] ) ) {
		return true;
	}

	if ( ! isset( $data['heading'] ) ) {
		if ( ! empty( $schema['fields']['heading']['required'] ) ) {
			return new WP_Error( 'igp_pro_heading_missing', __( 'Required heading object is missing.', 'igp-pro' ) );
		}
		return true;
	}

	if ( ! is_array( $data['heading'] ) ) {
		return new WP_Error( 'igp_pro_heading_invalid_shape', __( 'Heading must be a structured object.', 'igp-pro' ) );
	}

	$raw_level = isset( $data['heading']['level'] ) ? sanitize_key( (string) $data['heading']['level'] ) : 'h2';
	if ( ! in_array( $raw_level, igp_pro_get_allowed_block_heading_levels(), true ) ) {
		return new WP_Error( 'igp_pro_heading_invalid_level', __( 'Block heading level must be h2, h3, or h4.', 'igp-pro' ) );
	}

	$heading = igp_pro_normalize_block_heading( $data, $block_id );

	$is_required = ! empty( $schema['fields']['heading']['required'] )
		|| ! empty( $schema['fields']['heading']['fields']['text']['required'] );

	if ( $is_required && $heading['visible'] && '' === $heading['text'] ) {
		return new WP_Error( 'igp_pro_heading_required_empty', __( 'Visible required heading text cannot be empty.', 'igp-pro' ) );
	}

	return true;
}
