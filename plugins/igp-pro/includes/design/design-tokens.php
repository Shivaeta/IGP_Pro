<?php
/**
 * Design token service for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default plugin design tokens.
 *
 * @return array<string,array<string,string>>
 */
function igp_pro_get_default_design_tokens(): array {
	return array(
		'colors'     => array(
			'brand'       => '#fd4621',
			'brand_dark'  => '#d93412',
			'ink'         => '#111827',
			'muted'       => '#5b667a',
			'line'        => '#e8edf3',
			'soft'        => '#f6f8fb',
			'sand'        => '#fff6ef',
			'card'        => '#ffffff',
			'white'       => '#ffffff',
		),
		'typography' => array(
			'font_family'           => 'inherit',
			'heading_weight'        => '850',
			'body_weight'           => '500',
			'heading_letter_spacing'=> '-0.055em',
			'hero_size'             => 'clamp(44px, 7vw, 92px)',
			'section_heading_size'  => 'clamp(30px, 4vw, 54px)',
			'body_size'             => '16px',
		),
		'spacing'    => array(
			'section_y'        => 'clamp(44px, 7vw, 88px)',
			'block_gap'        => '24px',
			'card_padding'     => '24px',
			'button_padding_y' => '14px',
			'button_padding_x' => '24px',
		),
		'radius'     => array(
			'sm'   => '12px',
			'md'   => '20px',
			'lg'   => '32px',
			'pill' => '999px',
		),
		'shadow'     => array(
			'card' => '0 18px 45px rgba(17, 24, 39, .10)',
			'soft' => '0 10px 28px rgba(17, 24, 39, .08)',
		),
		'buttons'    => array(
			'radius'      => '999px',
			'font_weight' => '850',
		),
		'containers' => array(
			'narrow'  => '820px',
			'default' => '1040px',
			'wide'    => '1180px',
			'full'    => '100%',
		),
		'surfaces'   => array(
			'default'  => '#ffffff',
			'elevated' => '#ffffff',
			'soft'     => '#f6f8fb',
			'brand'    => '#fff6ef',
		),
	);
}

/**
 * Sanitize a design token map.
 *
 * @param mixed $tokens Raw token map.
 * @return array<string,array<string,string>>
 */
function igp_pro_sanitize_design_tokens( $tokens ): array {
	$tokens   = is_array( $tokens ) ? $tokens : array();
	$defaults = igp_pro_get_default_design_tokens();
	$result   = function_exists( 'igp_pro_validate_design_tokens_against_defaults' ) ? igp_pro_validate_design_tokens_against_defaults( $tokens, $defaults ) : array( 'tokens' => $defaults );

	return is_array( $result['tokens'] ?? null ) ? $result['tokens'] : $defaults;
}

/**
 * Validate tokens and return WP_Error on malformed submitted values.
 *
 * @param mixed $tokens Raw token map.
 * @return true|WP_Error
 */
function igp_pro_validate_design_tokens( $tokens ) {
	$tokens   = is_array( $tokens ) ? $tokens : array();
	$defaults = igp_pro_get_default_design_tokens();
	$result   = igp_pro_validate_design_tokens_against_defaults( $tokens, $defaults );

	if ( ! empty( $result['errors'] ) ) {
		return new WP_Error(
			'igp_pro_invalid_design_tokens',
			sprintf(
				/* translators: %s: comma-separated token names. */
				__( 'Invalid design token values: %s', 'igp-pro' ),
				implode( ', ', $result['errors'] )
			),
			array( 'tokens' => $result['errors'] )
		);
	}

	return true;
}

/**
 * Deep merge token maps, preserving known token categories.
 *
 * @param array<string,mixed> $base Base tokens.
 * @param array<string,mixed> $overrides Override tokens.
 * @return array<string,mixed>
 */
function igp_pro_merge_design_tokens( array $base, array $overrides ): array {
	foreach ( $overrides as $category => $values ) {
		if ( ! is_array( $values ) ) {
			continue;
		}

		if ( ! isset( $base[ $category ] ) || ! is_array( $base[ $category ] ) ) {
			$base[ $category ] = array();
		}

		foreach ( $values as $token => $value ) {
			$base[ $category ][ $token ] = $value;
		}
	}

	return $base;
}

/**
 * Resolve design tokens through the V2 cascade.
 *
 * Current phase supports default plugin tokens and active brand profile. Later
 * phases can pass template/page/block overrides through $context.
 *
 * @param array<string,mixed> $context Optional cascade overrides.
 * @return array<string,array<string,string>>
 */
function igp_pro_resolve_design_tokens( array $context = array() ): array {
	$tokens = igp_pro_get_default_design_tokens();

	if ( function_exists( 'igp_pro_get_active_brand_profile' ) ) {
		$profile = igp_pro_get_active_brand_profile();
		if ( is_array( $profile ) && isset( $profile['tokens'] ) && is_array( $profile['tokens'] ) ) {
			$tokens = igp_pro_merge_design_tokens( $tokens, $profile['tokens'] );
		}
	}

	foreach ( array( 'template_tokens', 'page_tokens', 'block_tokens' ) as $key ) {
		if ( isset( $context[ $key ] ) && is_array( $context[ $key ] ) ) {
			$tokens = igp_pro_merge_design_tokens( $tokens, $context[ $key ] );
		}
	}

	return igp_pro_sanitize_design_tokens( $tokens );
}
