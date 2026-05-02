<?php
/**
 * Core Web Vitals integration for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return CWV settings.
 */
function igp_pro_get_cwv_settings(): array {
	$settings = get_option( 'igp_pro_cwv_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	return wp_parse_args(
		$settings,
		array(
			'api_key'          => '',
			'default_strategy' => 'mobile',
			'auto_fetch'       => 'no',
		)
	);
}

/**
 * Build a stable cache key for a PageSpeed URL + strategy request.
 */
function igp_pro_cwv_cache_key( string $url, string $strategy ): string {
	return md5( esc_url_raw( $url ) . '|' . sanitize_key( $strategy ) );
}

/**
 * Return cached CWV data if present.
 */
function igp_pro_cwv_get_cached_report( string $url, string $strategy ) {
	$key = igp_pro_cwv_cache_key( $url, $strategy );
	return get_transient( 'igp_pro_cwv_' . $key );
}

/**
 * Get a PageSpeed report, using cache by default.
 */
function igp_pro_cwv_get_report( string $url, string $strategy = 'mobile', bool $force = false ) {
	$url      = esc_url_raw( $url );
	$strategy = in_array( $strategy, array( 'mobile', 'desktop' ), true ) ? $strategy : 'mobile';

	if ( '' === $url ) {
		return new WP_Error( 'igp_pro_cwv_missing_url', __( 'A URL is required for Core Web Vitals checks.', 'igp-pro' ) );
	}

	$key = igp_pro_cwv_cache_key( $url, $strategy );
	if ( ! $force ) {
		$cached = get_transient( 'igp_pro_cwv_' . $key );
		if ( is_array( $cached ) ) {
			$cached['cache_status'] = 'hit';
			return $cached;
		}
	}

	$fetched = igp_pro_cwv_fetch_pagespeed( $url, $strategy );
	if ( is_wp_error( $fetched ) ) {
		$last_good = get_option( 'igp_pro_cwv_last_good_' . $key, array() );
		if ( is_array( $last_good ) && ! empty( $last_good ) ) {
			$last_good['cache_status'] = 'fallback_last_good';
			$last_good['warning']      = $fetched->get_error_message();
			return $last_good;
		}
		return $fetched;
	}

	$settings = function_exists( 'igp_pro_get_performance_settings' ) ? igp_pro_get_performance_settings() : array( 'cwv_cache_ttl' => 43200 );
	$ttl      = isset( $settings['cwv_cache_ttl'] ) ? absint( $settings['cwv_cache_ttl'] ) : 43200;
	$ttl      = max( 300, $ttl );

	set_transient( 'igp_pro_cwv_' . $key, $fetched, $ttl );
	update_option( 'igp_pro_cwv_last_good_' . $key, $fetched, false );

	$fetched['cache_status'] = 'miss';
	return $fetched;
}

/**
 * Fetch PageSpeed Insights data.
 */
function igp_pro_cwv_fetch_pagespeed( string $url, string $strategy ) {
	$settings = igp_pro_get_cwv_settings();
	$args     = array(
		'url'      => $url,
		'strategy' => $strategy,
		'category' => 'performance',
	);

	if ( '' !== (string) $settings['api_key'] ) {
		$args['key'] = (string) $settings['api_key'];
	}

	$request_url = add_query_arg( $args, 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed' );
	$response    = wp_remote_get(
		$request_url,
		array(
			'timeout' => 20,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'igp_pro_cwv_http_error', sprintf( __( 'PageSpeed request failed with HTTP %d.', 'igp-pro' ), $code ) );
	}

	$data = json_decode( $body, true );
	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
		return new WP_Error( 'igp_pro_cwv_invalid_json', __( 'PageSpeed returned invalid JSON.', 'igp-pro' ) );
	}

	return igp_pro_cwv_normalize_pagespeed_response( $data, $url, $strategy );
}

/**
 * Normalize PageSpeed data to the small payload needed by admin UI.
 */
function igp_pro_cwv_normalize_pagespeed_response( array $data, string $url, string $strategy ): array {
	$lighthouse = $data['lighthouseResult'] ?? array();
	$audits     = is_array( $lighthouse ) && isset( $lighthouse['audits'] ) && is_array( $lighthouse['audits'] ) ? $lighthouse['audits'] : array();
	$categories = is_array( $lighthouse ) && isset( $lighthouse['categories'] ) && is_array( $lighthouse['categories'] ) ? $lighthouse['categories'] : array();
	$perf_score = isset( $categories['performance']['score'] ) ? round( (float) $categories['performance']['score'] * 100 ) : null;

	$metrics = array();
	foreach ( array( 'largest-contentful-paint' => 'LCP', 'cumulative-layout-shift' => 'CLS', 'total-blocking-time' => 'TBT', 'first-contentful-paint' => 'FCP', 'speed-index' => 'Speed Index' ) as $audit_key => $label ) {
		if ( isset( $audits[ $audit_key ] ) && is_array( $audits[ $audit_key ] ) ) {
			$metrics[ $audit_key ] = array(
				'label'        => $label,
				'displayValue' => (string) ( $audits[ $audit_key ]['displayValue'] ?? '' ),
				'score'        => isset( $audits[ $audit_key ]['score'] ) ? $audits[ $audit_key ]['score'] : null,
			);
		}
	}

	return array(
		'url'          => $url,
		'strategy'     => $strategy,
		'fetched_at'   => current_time( 'mysql' ),
		'performance'  => $perf_score,
		'metrics'      => $metrics,
		'raw_id'       => (string) ( $data['id'] ?? '' ),
		'cache_status' => 'fresh',
	);
}
