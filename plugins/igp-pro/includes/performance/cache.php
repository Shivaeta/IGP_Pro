<?php
/**
 * Cache layer for IGP Pro blocks, page fragments, and external responses.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_CACHE_VERSION_OPTION' ) ) {
	define( 'IGP_PRO_CACHE_VERSION_OPTION', 'igp_pro_cache_version' );
}

/**
 * Register cache invalidation hooks.
 */
function igp_pro_register_cache_module(): void {
	add_action( 'save_post', 'igp_pro_cache_invalidate_for_post', 20, 3 );
	add_action( 'deleted_post', 'igp_pro_cache_invalidate_for_deleted_post' );
	add_action( 'created_term', 'igp_pro_cache_invalidate_for_term_change', 20, 3 );
	add_action( 'edited_term', 'igp_pro_cache_invalidate_for_term_change', 20, 3 );
	add_action( 'delete_term', 'igp_pro_cache_invalidate_for_term_change', 20, 3 );
	add_action( 'template_redirect', 'igp_pro_page_cache_maybe_serve', 0 );
}

/**
 * Return performance settings.
 */
function igp_pro_get_performance_settings(): array {
	$settings = get_option( 'igp_pro_performance_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	return wp_parse_args(
		$settings,
		array(
			'enable_block_cache' => 'yes',
			'enable_page_cache'  => 'yes',
			'block_cache_ttl'    => 3600,
			'page_cache_ttl'     => 900,
			'query_cache_ttl'    => 900,
			'cwv_cache_ttl'      => 43200,
		)
	);
}

/**
 * Return global cache version used for safe invalidation.
 */
function igp_pro_cache_get_version(): string {
	$version = get_option( IGP_PRO_CACHE_VERSION_OPTION, '' );
	if ( ! is_string( $version ) || '' === $version ) {
		$version = (string) time();
		update_option( IGP_PRO_CACHE_VERSION_OPTION, $version, false );
	}
	return $version;
}

/**
 * Invalidate IGP caches by rotating a version token.
 */
function igp_pro_cache_invalidate( string $reason = 'manual' ): void {
	update_option( IGP_PRO_CACHE_VERSION_OPTION, sprintf( '%s:%s', microtime( true ), sanitize_key( $reason ) ), false );
}

/**
 * Invalidate when content changes.
 */
function igp_pro_cache_invalidate_for_post( int $post_id, WP_Post $post, bool $update ): void {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( in_array( $post->post_type, array( 'post', 'page', 'tour', 'destination', 'igp_booking' ), true ) ) {
		igp_pro_cache_invalidate( 'save_' . $post->post_type );
	}
}

/**
 * Invalidate when content is deleted.
 */
function igp_pro_cache_invalidate_for_deleted_post( int $post_id ): void {
	igp_pro_cache_invalidate( 'deleted_post' );
}

/**
 * Invalidate query-derived blocks when taxonomy terms change.
 */
function igp_pro_cache_invalidate_for_term_change( int $term_id, int $tt_id = 0, string $taxonomy = '' ): void {
	if ( in_array( $taxonomy, array( 'tour_category', 'travel_region', 'destination_region', 'category', 'post_tag' ), true ) ) {
		igp_pro_cache_invalidate( 'term_' . $taxonomy );
	}
}

/**
 * Decide whether a block render may be cached.
 */
function igp_pro_cache_can_cache_block( string $block_id, array $data, array $context ): bool {
	$settings = igp_pro_get_performance_settings();
	if ( 'yes' !== (string) $settings['enable_block_cache'] ) {
		return false;
	}

	// Avoid confusing editor previews and admin-side ServerSideRender.
	if ( is_admin() ) {
		return false;
	}

	if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	$uncacheable = array( 'booking_panel' );
	if ( in_array( sanitize_key( $block_id ), $uncacheable, true ) ) {
		return false;
	}

	return true;
}

/**
 * Cache a block render through a supplied callback.
 *
 * @param string   $block_id Block ID.
 * @param array    $data     Block data.
 * @param array    $context  Render context.
 * @param callable $callback Render callback.
 * @return string
 */
function igp_pro_cache_block_render( string $block_id, array $data, array $context, callable $callback ): string {
	if ( ! igp_pro_cache_can_cache_block( $block_id, $data, $context ) ) {
		return (string) call_user_func( $callback );
	}

	$settings = igp_pro_get_performance_settings();
	$ttl      = max( 60, absint( $settings['block_cache_ttl'] ) );
	$key      = igp_pro_cache_build_block_key( $block_id, $data, $context );
	$cached   = get_transient( $key );

	if ( is_string( $cached ) && '' !== $cached ) {
		return $cached . "\n<!-- IGP Pro block cache: hit -->";
	}

	$output = (string) call_user_func( $callback );

	if ( '' !== trim( $output ) && false === strpos( $output, 'igp-pro-block--fallback' ) ) {
		set_transient( $key, $output, $ttl );
	}

	return $output . "\n<!-- IGP Pro block cache: miss -->";
}

/**
 * Build a versioned block cache key.
 */
function igp_pro_cache_build_block_key( string $block_id, array $data, array $context ): string {
	$post_id = 0;
	if ( isset( $context['post_id'] ) ) {
		$post_id = absint( $context['post_id'] );
	}
	if ( $post_id <= 0 ) {
		$post_id = absint( get_queried_object_id() );
	}

	$section = isset( $context['section'] ) && is_array( $context['section'] ) ? $context['section'] : array();
	$outline = isset( $context['outline'] ) && is_array( $context['outline'] ) ? $context['outline'] : array();
	$h1      = isset( $outline['h1'] ) && is_array( $outline['h1'] ) ? $outline['h1'] : array();

	$payload = array(
		'v'                 => igp_pro_cache_get_version(),
		'post_id'           => $post_id,
		'block'             => sanitize_key( $block_id ),
		'data'              => $data,
		'content'           => isset( $context['content'] ) ? (string) $context['content'] : '',
		'section_id'        => isset( $section['id'] ) ? sanitize_key( (string) $section['id'] ) : '',
		'section_schema'    => isset( $section['schema_version'] ) ? sanitize_text_field( (string) $section['schema_version'] ) : '',
		'depth'             => isset( $context['depth'] ) ? absint( $context['depth'] ) : 0,
		'children_html'     => isset( $context['children_html'] ) ? md5( (string) $context['children_html'] ) : '',
		'igp_section_id'    => isset( $context['igp_section_id'] ) ? sanitize_key( (string) $context['igp_section_id'] ) : '',
		'igp_heading_id'    => isset( $context['igp_heading_id'] ) ? sanitize_key( (string) $context['igp_heading_id'] ) : '',
		'page_h1_block'     => ! empty( $context['igp_page_h1_block'] ),
		'outline_h1_text'   => isset( $h1['text'] ) ? trim( wp_strip_all_tags( (string) $h1['text'] ) ) : '',
		'outline_h1_source' => isset( $h1['source'] ) ? sanitize_key( (string) $h1['source'] ) : '',
		'outline_h1_section'=> isset( $h1['section_id'] ) ? sanitize_key( (string) $h1['section_id'] ) : '',
	);

	return 'igp_pro_block_' . md5( wp_json_encode( $payload ) ?: serialize( $payload ) );
}

/**
 * Cache an arbitrary external API response.
 */
function igp_pro_cache_external_response( string $namespace, string $key, $value, int $ttl ) {
	$transient = 'igp_pro_ext_' . sanitize_key( $namespace ) . '_' . md5( $key );
	set_transient( $transient, $value, max( 60, $ttl ) );
	return $value;
}

/**
 * Fetch an arbitrary external API response from cache.
 */
function igp_pro_get_cached_external_response( string $namespace, string $key ) {
	$transient = 'igp_pro_ext_' . sanitize_key( $namespace ) . '_' . md5( $key );
	return get_transient( $transient );
}

/**
 * Cache a query-derived value.
 */
function igp_pro_cache_query_result( string $key, $value, int $ttl = 900 ) {
	$versioned = 'igp_pro_query_' . md5( igp_pro_cache_get_version() . ':' . $key );
	set_transient( $versioned, $value, max( 60, $ttl ) );
	return $value;
}

/**
 * Read a cached query-derived value.
 */
function igp_pro_get_cached_query_result( string $key ) {
	$versioned = 'igp_pro_query_' . md5( igp_pro_cache_get_version() . ':' . $key );
	return get_transient( $versioned );
}


/**
 * Decide whether the current full page may be cached.
 *
 * Page cache deliberately excludes transactional and personalized routes.
 *
 * @return bool
 */
function igp_pro_page_cache_can_cache_current_request(): bool {
	$settings = igp_pro_get_performance_settings();
	if ( 'yes' !== (string) $settings['enable_page_cache'] ) {
		return false;
	}

	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	if ( is_user_logged_in() || 'GET' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) ) {
		return false;
	}

	if ( is_preview() || is_feed() || is_search() || is_404() || is_trackback() ) {
		return false;
	}

	foreach ( array( 'igp_pro_checkout', 'igp_pro_confirmation', 'key', 'nocache', 's' ) as $unsafe_key ) {
		if ( isset( $_GET[ $unsafe_key ] ) ) {
			return false;
		}
	}

	if ( is_singular( 'tour' ) || is_singular( 'igp_booking' ) ) {
		return false;
	}

	$post = get_post();
	if ( $post instanceof WP_Post ) {
		$content = (string) $post->post_content;
		if ( false !== strpos( $content, 'igp_booking_panel' ) || false !== strpos( $content, 'igp-pro/booking-panel' ) ) {
			return false;
		}
	}

	return is_singular( array( 'page', 'post', 'destination' ) ) || is_post_type_archive( array( 'tour', 'destination' ) ) || is_tax( array( 'tour_category', 'travel_region', 'destination_region' ) );
}

/**
 * Build a page cache key for the current request.
 *
 * @return string
 */
function igp_pro_page_cache_build_key(): string {
	$scheme = is_ssl() ? 'https' : 'http';
	$host   = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '';
	$uri    = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

	return 'igp_pro_page_' . md5( igp_pro_cache_get_version() . '|' . $scheme . '://' . $host . $uri );
}

/**
 * Serve or start buffering a cacheable page.
 */
function igp_pro_page_cache_maybe_serve(): void {
	if ( ! igp_pro_page_cache_can_cache_current_request() ) {
		return;
	}

	$key    = igp_pro_page_cache_build_key();
	$cached = get_transient( $key );

	if ( is_string( $cached ) && '' !== $cached ) {
		echo "<!-- IGP Pro page cache: hit -->\n" . $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	$GLOBALS['igp_pro_page_cache_key'] = $key;
	ob_start( 'igp_pro_page_cache_capture' );
}

/**
 * Capture, store, and return page HTML.
 *
 * @param string $html Full HTML.
 * @return string
 */
function igp_pro_page_cache_capture( string $html ): string {
	$key = isset( $GLOBALS['igp_pro_page_cache_key'] ) ? (string) $GLOBALS['igp_pro_page_cache_key'] : '';
	if ( '' === $key || '' === trim( $html ) ) {
		return $html;
	}

	$status = function_exists( 'http_response_code' ) ? (int) http_response_code() : 200;
	if ( $status >= 400 ) {
		return $html;
	}

	// Never store pages that ended up containing dynamic booking/checkout state.
	foreach ( array( 'igp-pro-booking-panel', 'igp-checkout-page', 'igp_pro_booking', 'data-igp-booking' ) as $marker ) {
		if ( false !== strpos( $html, $marker ) ) {
			return $html . "\n<!-- IGP Pro page cache: skipped dynamic -->";
		}
	}

	$settings = igp_pro_get_performance_settings();
	$ttl      = max( 60, absint( $settings['page_cache_ttl'] ) );
	set_transient( $key, $html, $ttl );

	return $html . "\n<!-- IGP Pro page cache: miss -->";
}
