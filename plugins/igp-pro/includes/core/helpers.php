<?php
/**
 * Shared helpers for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve a plugin-relative path.
 *
 * @param string $relative_path Relative path inside the plugin.
 * @return string
 */
function igp_pro_path( string $relative_path = '' ): string {
	return IGP_PRO_PATH . ltrim( $relative_path, '/\\' );
}

/**
 * Resolve a plugin-relative URL.
 *
 * @param string $relative_path Relative URL inside the plugin.
 * @return string
 */
function igp_pro_url( string $relative_path = '' ): string {
	return IGP_PRO_URL . ltrim( $relative_path, '/\\' );
}

/**
 * Decode JSON safely.
 *
 * @param string $json JSON string.
 * @return array|WP_Error
 */
function igp_pro_json_decode_array( string $json ) {
	$data = json_decode( $json, true );

	if ( JSON_ERROR_NONE !== json_last_error() ) {
		return new WP_Error(
			'igp_pro_invalid_json',
			sprintf(
				/* translators: %s: JSON parser error. */
				__( 'Invalid JSON: %s', 'igp-pro' ),
				json_last_error_msg()
			)
		);
	}

	if ( ! is_array( $data ) ) {
		return new WP_Error( 'igp_pro_invalid_json_shape', __( 'JSON payload must decode to an object or array.', 'igp-pro' ) );
	}

	return $data;
}

/**
 * Return a string from a mixed value.
 *
 * @param mixed $value Value to normalize.
 * @return string
 */
function igp_pro_to_string( $value ): string {
	if ( is_scalar( $value ) ) {
		return (string) $value;
	}

	return '';
}

/**
 * Validate and normalize a block ID.
 *
 * @param string $block_id Block ID.
 * @return string|WP_Error
 */
function igp_pro_normalize_block_id( string $block_id ) {
	$block_id = sanitize_key( $block_id );

	if ( '' === $block_id ) {
		return new WP_Error( 'igp_pro_empty_block_id', __( 'Block ID cannot be empty.', 'igp-pro' ) );
	}

	return $block_id;
}

/**
 * Extract an image URL from a schema image field value.
 *
 * @param mixed $image Image field value.
 * @return string
 */
function igp_pro_get_image_url( $image ): string {
	if ( is_array( $image ) && isset( $image['url'] ) ) {
		return esc_url_raw( igp_pro_to_string( $image['url'] ) );
	}

	return esc_url_raw( igp_pro_to_string( $image ) );
}

/**
 * Extract an image alt string from a schema image field value.
 *
 * @param mixed  $image    Image field value.
 * @param string $fallback Fallback alt text.
 * @return string
 */
function igp_pro_get_image_alt( $image, string $fallback = '' ): string {
	if ( is_array( $image ) && isset( $image['alt'] ) ) {
		return sanitize_text_field( igp_pro_to_string( $image['alt'] ) );
	}

	return sanitize_text_field( $fallback );
}
