<?php
/**
 * Optional Link Whisper companion bridge for IGP Pro.
 *
 * Link Whisper remains optional and never owns IGP's structured link data.
 * Suggestions are mapped into reviewable IGP opportunities; approved links are
 * written only through the Content Graph approval workflow.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_LINK_WHISPER_BRIDGE_OPTION' ) ) {
	define( 'IGP_PRO_LINK_WHISPER_BRIDGE_OPTION', 'igp_pro_link_whisper_bridge_settings' );
}

/**
 * Default settings.
 */
function igp_pro_link_whisper_bridge_default_settings(): array {
	return array(
		'provide_projection'       => true,
		'map_suggestions'          => true,
		'allow_auto_insert'        => false,
		'store_approved_in_graph'  => true,
		'expose_analysis_filters'  => true,
	);
}

/**
 * Sanitize settings.
 *
 * @param mixed $settings Raw settings.
 */
function igp_pro_link_whisper_bridge_sanitize_settings( $settings ): array {
	$settings  = is_array( $settings ) ? $settings : array();
	$sanitized = igp_pro_link_whisper_bridge_default_settings();
	foreach ( array_keys( $sanitized ) as $key ) {
		if ( array_key_exists( $key, $settings ) ) {
			$sanitized[ $key ] = in_array( $settings[ $key ], array( true, 1, '1', 'yes', 'true', 'on' ), true );
		}
	}

	// Non-negotiable: blind auto-insertion is not supported by default.
	$sanitized['allow_auto_insert'] = false;
	return $sanitized;
}

/**
 * Return settings.
 */
function igp_pro_link_whisper_bridge_get_settings(): array {
	return igp_pro_link_whisper_bridge_sanitize_settings( get_option( IGP_PRO_LINK_WHISPER_BRIDGE_OPTION, array() ) );
}

/**
 * Update settings.
 */
function igp_pro_link_whisper_bridge_update_settings( array $settings ): array {
	$settings = igp_pro_link_whisper_bridge_sanitize_settings( $settings );
	update_option( IGP_PRO_LINK_WHISPER_BRIDGE_OPTION, $settings, false );
	return $settings;
}

/**
 * Detect Link Whisper safely.
 */
function igp_pro_link_whisper_is_active(): bool {
	if ( defined( 'WPIL_PLUGIN_VERSION' ) || defined( 'LINK_WHISPER_VERSION' ) || class_exists( 'Wpil_Base' ) || class_exists( 'LinkWhisper' ) ) {
		return true;
	}

	$active = (array) get_option( 'active_plugins', array() );
	foreach ( array( 'link-whisper/link-whisper.php', 'link-whisper-premium/link-whisper-premium.php' ) as $plugin ) {
		if ( in_array( $plugin, $active, true ) ) {
			return true;
		}
	}

	if ( is_multisite() ) {
		$network_active = (array) get_site_option( 'active_sitewide_plugins', array() );
		foreach ( array( 'link-whisper/link-whisper.php', 'link-whisper-premium/link-whisper-premium.php' ) as $plugin ) {
			if ( isset( $network_active[ $plugin ] ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Whether bridge feature flag is enabled.
 */
function igp_pro_link_whisper_bridge_enabled(): bool {
	return function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_link_whisper_bridge' );
}

/**
 * Register bridge hooks.
 */
function igp_pro_register_link_whisper_bridge(): void {
	if ( ! igp_pro_link_whisper_bridge_enabled() ) {
		return;
	}

	add_action( 'admin_post_igp_pro_save_link_whisper_bridge', 'igp_pro_link_whisper_handle_save_settings' );

	$settings = igp_pro_link_whisper_bridge_get_settings();
	if ( ! empty( $settings['expose_analysis_filters'] ) ) {
		add_filter( 'igp_pro_link_whisper_projection_content', 'igp_pro_link_whisper_filter_projection_content', 10, 2 );
		add_filter( 'igp_pro_link_whisper_analysis_payload', 'igp_pro_link_whisper_filter_analysis_payload', 10, 2 );
	}
}

/**
 * Projection filter callback.
 */
function igp_pro_link_whisper_filter_projection_content( $content, $post_id ): string {
	$post_id = absint( $post_id );
	if ( $post_id <= 0 || ! function_exists( 'igp_pro_link_whisper_get_content' ) ) {
		return is_scalar( $content ) ? (string) $content : '';
	}
	$projection = igp_pro_link_whisper_get_content( $post_id );
	return '' !== trim( $projection ) ? $projection : ( is_scalar( $content ) ? (string) $content : '' );
}

/**
 * Analysis payload filter callback.
 */
function igp_pro_link_whisper_filter_analysis_payload( $payload, $post_id ): array {
	$post_id = absint( $post_id );
	$payload = is_array( $payload ) ? $payload : array();
	if ( $post_id <= 0 || ! function_exists( 'igp_pro_link_whisper_get_analysis_payload' ) ) {
		return $payload;
	}
	return array_merge( $payload, igp_pro_link_whisper_get_analysis_payload( $post_id ) );
}

/**
 * Handle settings save.
 */
function igp_pro_link_whisper_handle_save_settings(): void {
	check_admin_referer( 'igp_pro_save_link_whisper_bridge' );
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'integrations' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to manage IGP integrations.', 'igp-pro' ) );
	}

	$raw = isset( $_POST['igp_pro_link_whisper_bridge'] ) && is_array( $_POST['igp_pro_link_whisper_bridge'] ) ? wp_unslash( $_POST['igp_pro_link_whisper_bridge'] ) : array();
	igp_pro_link_whisper_bridge_update_settings( $raw );

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => 'human',
				'operation'     => 'link_whisper_bridge_settings_updated',
				'object_type'   => 'integration_settings',
				'object_id'     => 0,
				'source_module' => 'link_whisper_bridge',
				'summary'       => 'Link Whisper bridge settings updated.',
			)
		);
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-integrations', 'settings-updated' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}
