<?php
/**
 * Brand profile storage for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_BRAND_PROFILES_OPTION' ) ) {
	define( 'IGP_PRO_BRAND_PROFILES_OPTION', 'igp_pro_brand_profiles' );
}

if ( ! defined( 'IGP_PRO_ACTIVE_BRAND_PROFILE_OPTION' ) ) {
	define( 'IGP_PRO_ACTIVE_BRAND_PROFILE_OPTION', 'igp_pro_active_brand_profile' );
}

/**
 * Sanitize a brand profile ID.
 *
 * @param string $profile_id Raw profile ID.
 * @return string
 */
function igp_pro_sanitize_brand_profile_id( string $profile_id ): string {
	$profile_id = sanitize_key( $profile_id );
	return '' !== $profile_id ? $profile_id : 'default';
}

/**
 * Return a normalized brand profile.
 *
 * @param mixed $profile Raw profile.
 * @return array<string,mixed>
 */
function igp_pro_sanitize_brand_profile( $profile ): array {
	$profile    = is_array( $profile ) ? $profile : array();
	$name       = isset( $profile['name'] ) ? sanitize_text_field( (string) $profile['name'] ) : '';
	$profile_id = isset( $profile['profile_id'] ) ? igp_pro_sanitize_brand_profile_id( (string) $profile['profile_id'] ) : sanitize_key( $name );

	if ( '' === $profile_id ) {
		$profile_id = 'default';
	}

	if ( '' === $name ) {
		$name = ucwords( str_replace( '-', ' ', $profile_id ) );
	}

	$tokens = isset( $profile['tokens'] ) && is_array( $profile['tokens'] ) ? igp_pro_sanitize_design_tokens( $profile['tokens'] ) : igp_pro_get_default_design_tokens();

	return array(
		'profile_id' => $profile_id,
		'name'       => $name,
		'tokens'     => $tokens,
		'created_at' => isset( $profile['created_at'] ) ? sanitize_text_field( (string) $profile['created_at'] ) : gmdate( 'c' ),
		'updated_at' => gmdate( 'c' ),
	);
}

/**
 * Get all brand profiles.
 *
 * @return array<string,array<string,mixed>>
 */
function igp_pro_get_brand_profiles(): array {
	$stored = get_option( IGP_PRO_BRAND_PROFILES_OPTION, array() );
	$stored = is_array( $stored ) ? $stored : array();
	$result = array();

	foreach ( $stored as $profile_id => $profile ) {
		$profile = igp_pro_sanitize_brand_profile( $profile );
		$result[ $profile['profile_id'] ] = $profile;
	}

	return $result;
}

/**
 * Get a brand profile by ID.
 *
 * @param string $profile_id Profile ID.
 * @return array<string,mixed>|null
 */
function igp_pro_get_brand_profile( string $profile_id ): ?array {
	$profile_id = igp_pro_sanitize_brand_profile_id( $profile_id );
	$profiles   = igp_pro_get_brand_profiles();

	return $profiles[ $profile_id ] ?? null;
}

/**
 * Get the active brand profile.
 *
 * @return array<string,mixed>|null
 */
function igp_pro_get_active_brand_profile(): ?array {
	$active = get_option( IGP_PRO_ACTIVE_BRAND_PROFILE_OPTION, '' );
	$active = is_string( $active ) ? igp_pro_sanitize_brand_profile_id( $active ) : '';

	if ( '' === $active ) {
		return null;
	}

	return igp_pro_get_brand_profile( $active );
}

/**
 * Save a brand profile.
 *
 * @param array<string,mixed> $profile Raw profile.
 * @return string|WP_Error Profile ID or error.
 */
function igp_pro_save_brand_profile( array $profile ) {
	$validation = igp_pro_validate_design_tokens( $profile['tokens'] ?? array() );
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$profiles = igp_pro_get_brand_profiles();
	$profile  = igp_pro_sanitize_brand_profile( $profile );

	if ( isset( $profiles[ $profile['profile_id'] ]['created_at'] ) ) {
		$profile['created_at'] = $profiles[ $profile['profile_id'] ]['created_at'];
	}

	$profiles[ $profile['profile_id'] ] = $profile;
	update_option( IGP_PRO_BRAND_PROFILES_OPTION, $profiles, false );

	if ( function_exists( 'igp_pro_invalidate_brand_css_cache' ) ) {
		igp_pro_invalidate_brand_css_cache();
	}

	return $profile['profile_id'];
}

/**
 * Set active brand profile.
 *
 * @param string $profile_id Profile ID.
 * @return bool|WP_Error
 */
function igp_pro_set_active_brand_profile( string $profile_id ) {
	$profile_id = igp_pro_sanitize_brand_profile_id( $profile_id );

	if ( null === igp_pro_get_brand_profile( $profile_id ) ) {
		return new WP_Error( 'igp_pro_brand_profile_missing', __( 'Brand profile does not exist.', 'igp-pro' ) );
	}

	$updated = update_option( IGP_PRO_ACTIVE_BRAND_PROFILE_OPTION, $profile_id, false );

	if ( function_exists( 'igp_pro_invalidate_brand_css_cache' ) ) {
		igp_pro_invalidate_brand_css_cache();
	}

	return $updated || get_option( IGP_PRO_ACTIVE_BRAND_PROFILE_OPTION ) === $profile_id;
}

/**
 * Return active profile ID.
 *
 * @return string
 */
function igp_pro_get_active_brand_profile_id(): string {
	$active = get_option( IGP_PRO_ACTIVE_BRAND_PROFILE_OPTION, '' );
	return is_string( $active ) ? igp_pro_sanitize_brand_profile_id( $active ) : '';
}
