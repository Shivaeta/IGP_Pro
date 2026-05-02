<?php
/**
 * Design token validation for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return a safe scalar CSS token string.
 *
 * @param mixed  $value   Raw value.
 * @param string $default Default value.
 * @return string
 */
function igp_pro_sanitize_token_string( $value, string $default = '' ): string {
	$value = is_scalar( $value ) ? trim( (string) $value ) : '';

	if ( '' === $value ) {
		return $default;
	}

	// Prevent CSS rule injection. Tokens are values only, never declarations.
	if ( preg_match( '/[{};]/', $value ) ) {
		return $default;
	}

	return sanitize_text_field( $value );
}

/**
 * Validate CSS color tokens.
 *
 * @param mixed  $value   Raw color.
 * @param string $default Default color.
 * @return string
 */
function igp_pro_sanitize_color_token( $value, string $default ): string {
	$value = igp_pro_sanitize_token_string( $value, $default );

	if ( preg_match( '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
		return strtolower( $value );
	}

	if ( preg_match( '/^rgba?\(\s*(?:\d{1,3}\s*,\s*){2}\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/', $value ) ) {
		return $value;
	}

	if ( preg_match( '/^hsla?\(\s*[-\d.]+(?:deg|rad|turn)?\s*,\s*[-\d.]+%\s*,\s*[-\d.]+%(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/', $value ) ) {
		return $value;
	}

	return $default;
}

/**
 * Validate CSS size-like values.
 *
 * @param mixed  $value   Raw size.
 * @param string $default Default size.
 * @return string
 */
function igp_pro_sanitize_size_token( $value, string $default ): string {
	$value = igp_pro_sanitize_token_string( $value, $default );

	$number_unit = '/^-?(?:\d+|\d*\.\d+)(?:px|rem|em|%|vw|vh|vmin|vmax|ch|ex|lh|rlh)?$/';
	$function    = '/^(?:min|max|clamp|calc)\([0-9a-zA-Z\s.,+\-*\/()%]+\)$/';

	if ( preg_match( $number_unit, $value ) || preg_match( $function, $value ) ) {
		return $value;
	}

	return $default;
}

/**
 * Validate typography token values.
 *
 * @param mixed  $value   Raw value.
 * @param string $default Default value.
 * @return string
 */
function igp_pro_sanitize_typography_token( $value, string $default ): string {
	$value = igp_pro_sanitize_token_string( $value, $default );

	if ( preg_match( '/^[a-zA-Z0-9\s,"\'\-_.()]+$/', $value ) ) {
		return $value;
	}

	return $default;
}

/**
 * Validate a CSS weight token.
 *
 * @param mixed  $value   Raw weight.
 * @param string $default Default weight.
 * @return string
 */
function igp_pro_sanitize_weight_token( $value, string $default ): string {
	$value = igp_pro_sanitize_token_string( $value, $default );

	if ( preg_match( '/^(?:[1-9]00|normal|bold|bolder|lighter)$/', $value ) ) {
		return $value;
	}

	return $default;
}

/**
 * Validate a box-shadow token.
 *
 * @param mixed  $value   Raw shadow.
 * @param string $default Default shadow.
 * @return string
 */
function igp_pro_sanitize_shadow_token( $value, string $default ): string {
	$value = igp_pro_sanitize_token_string( $value, $default );

	if ( 'none' === strtolower( $value ) ) {
		return 'none';
	}

	if ( preg_match( '/^[0-9a-zA-Z\s.,#()+\-\/]+$/', $value ) && preg_match( '/(?:px|rem|em)/', $value ) ) {
		return $value;
	}

	return $default;
}

/**
 * Validate tokens against a token schema.
 *
 * @param array<string,mixed> $tokens Raw tokens.
 * @param array<string,mixed> $defaults Defaults.
 * @return array{tokens:array<string,mixed>,errors:string[]}
 */
function igp_pro_validate_design_tokens_against_defaults( array $tokens, array $defaults ): array {
	$errors    = array();
	$sanitized = $defaults;

	foreach ( $defaults as $category => $category_defaults ) {
		if ( ! is_array( $category_defaults ) ) {
			continue;
		}

		$raw_category = isset( $tokens[ $category ] ) && is_array( $tokens[ $category ] ) ? $tokens[ $category ] : array();

		foreach ( $category_defaults as $token => $default ) {
			$raw_value = array_key_exists( $token, $raw_category ) ? $raw_category[ $token ] : $default;
			$value     = (string) $default;

			switch ( $category ) {
				case 'colors':
				case 'surfaces':
					$value = igp_pro_sanitize_color_token( $raw_value, (string) $default );
					break;
				case 'spacing':
				case 'radius':
				case 'containers':
					$value = igp_pro_sanitize_size_token( $raw_value, (string) $default );
					break;
				case 'shadow':
					$value = igp_pro_sanitize_shadow_token( $raw_value, (string) $default );
					break;
				case 'buttons':
					$value = in_array( $token, array( 'font_weight' ), true ) ? igp_pro_sanitize_weight_token( $raw_value, (string) $default ) : igp_pro_sanitize_size_token( $raw_value, (string) $default );
					break;
				case 'typography':
					if ( in_array( $token, array( 'heading_weight', 'body_weight' ), true ) ) {
						$value = igp_pro_sanitize_weight_token( $raw_value, (string) $default );
					} elseif ( false !== strpos( $token, 'size' ) || false !== strpos( $token, 'spacing' ) ) {
						$value = igp_pro_sanitize_size_token( $raw_value, (string) $default );
					} else {
						$value = igp_pro_sanitize_typography_token( $raw_value, (string) $default );
					}
					break;
				default:
					$value = igp_pro_sanitize_token_string( $raw_value, (string) $default );
			}

			if ( is_scalar( $raw_value ) && trim( (string) $raw_value ) !== $value ) {
				$errors[] = sprintf( '%s.%s', $category, $token );
			}

			$sanitized[ $category ][ $token ] = $value;
		}
	}

	return array(
		'tokens' => $sanitized,
		'errors' => array_values( array_unique( $errors ) ),
	);
}
