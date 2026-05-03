<?php
/**
 * Optional Rank Math bridge for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_RANK_MATH_BRIDGE_OPTION' ) ) {
	define( 'IGP_PRO_RANK_MATH_BRIDGE_OPTION', 'igp_pro_rank_math_bridge_settings' );
}

/**
 * Return default Rank Math bridge settings.
 */
function igp_pro_rank_math_bridge_default_settings(): array {
	return array(
		'mode'                => 'runtime',
		'sync_enabled'        => false,
		'provide_meta'        => true,
		'provide_open_graph'  => true,
		'provide_schema'      => true,
		'provide_breadcrumbs' => true,
		'provide_analysis'    => true,
	);
}

/**
 * Sanitize Rank Math bridge settings.
 *
 * @param mixed $settings Raw settings.
 */
function igp_pro_rank_math_bridge_sanitize_settings( $settings ): array {
	$settings = is_array( $settings ) ? $settings : array();
	$defaults = igp_pro_rank_math_bridge_default_settings();
	$mode     = isset( $settings['mode'] ) ? sanitize_key( (string) $settings['mode'] ) : $defaults['mode'];

	if ( ! in_array( $mode, array( 'runtime', 'sync' ), true ) ) {
		$mode = 'runtime';
	}

	$sanitized = $defaults;
	$sanitized['mode'] = $mode;
	foreach ( array( 'sync_enabled', 'provide_meta', 'provide_open_graph', 'provide_schema', 'provide_breadcrumbs', 'provide_analysis' ) as $key ) {
		if ( array_key_exists( $key, $settings ) ) {
			$sanitized[ $key ] = in_array( $settings[ $key ], array( true, 1, '1', 'yes', 'true', 'on' ), true );
		}
	}

	if ( 'sync' !== $sanitized['mode'] ) {
		$sanitized['sync_enabled'] = false;
	}

	return $sanitized;
}

/**
 * Return Rank Math bridge settings.
 */
function igp_pro_rank_math_bridge_get_settings(): array {
	return igp_pro_rank_math_bridge_sanitize_settings( get_option( IGP_PRO_RANK_MATH_BRIDGE_OPTION, array() ) );
}

/**
 * Persist Rank Math bridge settings.
 */
function igp_pro_rank_math_bridge_update_settings( array $settings ): array {
	$sanitized = igp_pro_rank_math_bridge_sanitize_settings( $settings );
	update_option( IGP_PRO_RANK_MATH_BRIDGE_OPTION, $sanitized, false );
	return $sanitized;
}

/**
 * Detect Rank Math safely without creating a hard dependency.
 */
function igp_pro_rank_math_is_active(): bool {
	if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) || function_exists( 'rank_math' ) ) {
		return true;
	}

	$plugin = 'seo-by-rank-math/rank-math.php';
	if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin ) ) {
		return true;
	}

	$active = (array) get_option( 'active_plugins', array() );
	if ( in_array( $plugin, $active, true ) ) {
		return true;
	}

	if ( is_multisite() ) {
		$network_active = (array) get_site_option( 'active_sitewide_plugins', array() );
		return isset( $network_active[ $plugin ] );
	}

	return false;
}

/**
 * Determine whether feature flag and runtime status allow the bridge to run.
 */
function igp_pro_rank_math_bridge_enabled(): bool {
	return function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_rank_math_bridge' );
}

/**
 * Whether Rank Math should own frontend SEO output for the current request.
 */
function igp_pro_rank_math_bridge_owns_frontend_output(): bool {
	return igp_pro_rank_math_bridge_enabled() && igp_pro_rank_math_is_active();
}

/**
 * Register bridge hooks.
 */
function igp_pro_register_rank_math_bridge(): void {
	if ( ! igp_pro_rank_math_bridge_enabled() ) {
		return;
	}

	add_action( 'admin_post_igp_pro_save_rank_math_bridge', 'igp_pro_rank_math_handle_save_settings' );
	add_action( 'admin_post_igp_pro_rank_math_sync_post', 'igp_pro_rank_math_handle_sync_post' );

	if ( is_admin() ) {
		add_action( 'enqueue_block_editor_assets', 'igp_pro_rank_math_enqueue_analysis_bridge', 40 );
	}

	if ( ! igp_pro_rank_math_is_active() ) {
		return;
	}

	$settings = igp_pro_rank_math_bridge_get_settings();

	if ( ! empty( $settings['provide_meta'] ) ) {
		add_filter( 'rank_math/frontend/title', 'igp_pro_rank_math_filter_title', 20 );
		add_filter( 'rank_math/frontend/description', 'igp_pro_rank_math_filter_description', 20 );
		add_filter( 'rank_math/frontend/canonical', 'igp_pro_rank_math_filter_canonical', 20 );
		add_filter( 'rank_math/frontend/robots', 'igp_pro_rank_math_filter_robots', 20 );
	}

	if ( ! empty( $settings['provide_open_graph'] ) ) {
		add_filter( 'rank_math/opengraph/facebook/title', 'igp_pro_rank_math_filter_og_title', 20 );
		add_filter( 'rank_math/opengraph/facebook/description', 'igp_pro_rank_math_filter_og_description', 20 );
		add_filter( 'rank_math/opengraph/facebook/image', 'igp_pro_rank_math_filter_og_image', 20 );
		add_filter( 'rank_math/opengraph/twitter/title', 'igp_pro_rank_math_filter_og_title', 20 );
		add_filter( 'rank_math/opengraph/twitter/description', 'igp_pro_rank_math_filter_og_description', 20 );
		add_filter( 'rank_math/opengraph/twitter/image', 'igp_pro_rank_math_filter_og_image', 20 );
	}

	if ( ! empty( $settings['provide_schema'] ) ) {
		add_filter( 'rank_math/json_ld', 'igp_pro_rank_math_filter_json_ld', 20, 2 );
	}

	if ( ! empty( $settings['provide_breadcrumbs'] ) ) {
		add_filter( 'rank_math/frontend/breadcrumb/items', 'igp_pro_rank_math_filter_breadcrumb_items', 20, 2 );
	}
}

/**
 * Return current post Rank Math SEO data.
 */
function igp_pro_rank_math_current_seo_data(): array {
	$post_id = function_exists( 'igp_pro_rank_math_get_current_post_id' ) ? igp_pro_rank_math_get_current_post_id() : 0;
	return $post_id > 0 && function_exists( 'igp_pro_rank_math_get_seo_data' ) ? igp_pro_rank_math_get_seo_data( $post_id ) : array();
}

function igp_pro_rank_math_filter_title( $title ): string {
	$data = igp_pro_rank_math_current_seo_data();
	return ! empty( $data['title'] ) ? (string) $data['title'] : (string) $title;
}

function igp_pro_rank_math_filter_description( $description ): string {
	$data = igp_pro_rank_math_current_seo_data();
	return ! empty( $data['description'] ) ? (string) $data['description'] : (string) $description;
}

function igp_pro_rank_math_filter_canonical( $canonical ): string {
	$data = igp_pro_rank_math_current_seo_data();
	return ! empty( $data['canonical'] ) ? (string) $data['canonical'] : (string) $canonical;
}

/**
 * Filter robots directives.
 *
 * @param array $robots Rank Math robots array.
 * @return array
 */
function igp_pro_rank_math_filter_robots( $robots ): array {
	$robots = is_array( $robots ) ? $robots : array();
	$data = igp_pro_rank_math_current_seo_data();
	return ! empty( $data['robots'] ) && is_array( $data['robots'] ) ? $data['robots'] : $robots;
}

function igp_pro_rank_math_filter_og_title( $title ): string {
	$data = igp_pro_rank_math_current_seo_data();
	return ! empty( $data['og_title'] ) ? (string) $data['og_title'] : (string) $title;
}

function igp_pro_rank_math_filter_og_description( $description ): string {
	$data = igp_pro_rank_math_current_seo_data();
	return ! empty( $data['og_description'] ) ? (string) $data['og_description'] : (string) $description;
}

function igp_pro_rank_math_filter_og_image( $image ) {
	$data = igp_pro_rank_math_current_seo_data();
	return ! empty( $data['og_image'] ) ? (string) $data['og_image'] : $image;
}

/**
 * Merge IGP JSON-LD into Rank Math's output.
 *
 * @param array $data    Rank Math JSON-LD data.
 * @param mixed $json_ld Rank Math JsonLD object/context.
 * @return array
 */
function igp_pro_rank_math_filter_json_ld( $data, $json_ld = null ): array {
	$data = is_array( $data ) ? $data : array();
	$post_id = function_exists( 'igp_pro_rank_math_get_current_post_id' ) ? igp_pro_rank_math_get_current_post_id() : 0;
	if ( $post_id <= 0 || ! function_exists( 'igp_pro_rank_math_merge_schema_graph' ) ) {
		return $data;
	}

	return igp_pro_rank_math_merge_schema_graph( $data, $post_id );
}

/**
 * Pass IGP breadcrumb data to Rank Math where supported.
 *
 * @param array $crumbs Rank Math breadcrumbs.
 * @param mixed $class  Rank Math breadcrumb object/context.
 * @return array
 */
function igp_pro_rank_math_filter_breadcrumb_items( $crumbs, $class = null ): array {
	$crumbs = is_array( $crumbs ) ? $crumbs : array();
	$post_id = function_exists( 'igp_pro_rank_math_get_current_post_id' ) ? igp_pro_rank_math_get_current_post_id() : 0;
	if ( $post_id <= 0 || ! function_exists( 'igp_pro_rank_math_map_breadcrumbs' ) ) {
		return $crumbs;
	}

	$mapped = igp_pro_rank_math_map_breadcrumbs( $post_id );
	return ! empty( $mapped ) ? $mapped : $crumbs;
}

/**
 * Enqueue Rank Math content-analysis bridge for the post editor when possible.
 */
function igp_pro_rank_math_enqueue_analysis_bridge(): void {
	if ( ! igp_pro_rank_math_bridge_enabled() || ! igp_pro_rank_math_is_active() ) {
		return;
	}

	$settings = igp_pro_rank_math_bridge_get_settings();
	if ( empty( $settings['provide_analysis'] ) ) {
		return;
	}

	$post_id = 0;
	if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = absint( wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ( $post_id <= 0 ) {
		return;
	}

	$data = function_exists( 'igp_pro_rank_math_get_seo_data' ) ? igp_pro_rank_math_get_seo_data( $post_id ) : array();
	if ( empty( $data ) ) {
		return;
	}

	$handle = 'igp-pro-rank-math-analysis';
	$src    = IGP_PRO_URL . 'assets/js/admin-integrations.js';
	wp_register_script( $handle, $src, array( 'wp-hooks' ), IGP_PRO_VERSION, true );
	wp_localize_script(
		$handle,
		'igpRankMathBridge',
		array(
			'title'               => $data['title'] ?? '',
			'content'             => $data['analysis_content'] ?? '',
			'imageContent'        => $data['image_analysis_content'] ?? '',
			'linkAnalysisContent' => $data['link_analysis_content'] ?? '',
		)
	);
	wp_enqueue_script( $handle );
}

/**
 * Save bridge admin settings.
 */
function igp_pro_rank_math_handle_save_settings(): void {
	check_admin_referer( 'igp_pro_save_rank_math_bridge' );

	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'integrations' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to manage IGP integrations.', 'igp-pro' ) );
	}

	$raw = isset( $_POST['igp_pro_rank_math_bridge'] ) && is_array( $_POST['igp_pro_rank_math_bridge'] ) ? wp_unslash( $_POST['igp_pro_rank_math_bridge'] ) : array();
	igp_pro_rank_math_bridge_update_settings( $raw );

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => 'human',
				'operation'     => 'rank_math_bridge_settings_updated',
				'object_type'   => 'integration_settings',
				'object_id'     => 0,
				'source_module' => 'rank_math_bridge',
				'status'        => 'success',
				'summary'       => 'Rank Math bridge settings updated.',
			)
		);
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-pro-integrations', 'settings-updated' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Sync IGP SEO data into Rank Math meta only when explicitly enabled.
 */
function igp_pro_rank_math_handle_sync_post(): void {
	check_admin_referer( 'igp_pro_rank_math_sync_post' );

	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'integrations' ) : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to manage IGP integrations.', 'igp-pro' ) );
	}

	$post_id  = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
	$settings = igp_pro_rank_math_bridge_get_settings();
	$result   = new WP_Error( 'igp_pro_rank_math_sync_disabled', __( 'Rank Math sync mode is disabled.', 'igp-pro' ) );

	if ( $post_id > 0 && ! empty( $settings['sync_enabled'] ) && 'sync' === $settings['mode'] ) {
		$result = igp_pro_rank_math_sync_post_meta( $post_id );
	}

	$args = array( 'page' => 'igp-pro-integrations' );
	if ( is_wp_error( $result ) ) {
		$args['sync-error'] = rawurlencode( $result->get_error_message() );
	} else {
		$args['synced'] = '1';
	}

	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Write Rank Math-compatible post meta only for explicit sync mode.
 */
function igp_pro_rank_math_sync_post_meta( int $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return new WP_Error( 'igp_pro_rank_math_invalid_post', __( 'Invalid post ID.', 'igp-pro' ) );
	}

	$data = function_exists( 'igp_pro_rank_math_get_seo_data' ) ? igp_pro_rank_math_get_seo_data( $post_id ) : array();
	if ( empty( $data ) ) {
		return new WP_Error( 'igp_pro_rank_math_empty_data', __( 'No IGP SEO data is available to sync.', 'igp-pro' ) );
	}

	update_post_meta( $post_id, 'rank_math_title', $data['title'] ?? '' );
	update_post_meta( $post_id, 'rank_math_description', $data['description'] ?? '' );
	update_post_meta( $post_id, 'rank_math_canonical_url', $data['canonical'] ?? '' );
	update_post_meta( $post_id, 'rank_math_robots', array_keys( is_array( $data['robots'] ?? null ) ? $data['robots'] : array() ) );
	update_post_meta( $post_id, 'rank_math_facebook_title', $data['og_title'] ?? '' );
	update_post_meta( $post_id, 'rank_math_facebook_description', $data['og_description'] ?? '' );
	if ( ! empty( $data['og_image'] ) ) {
		update_post_meta( $post_id, 'rank_math_facebook_image', esc_url_raw( (string) $data['og_image'] ) );
	}

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => 'human',
				'operation'     => 'rank_math_meta_synced',
				'object_type'   => 'post',
				'object_id'     => $post_id,
				'source_module' => 'rank_math_bridge',
				'status'        => 'success',
				'summary'       => 'IGP SEO data synced into Rank Math post meta by explicit admin action.',
			)
		);
	}

	return true;
}
