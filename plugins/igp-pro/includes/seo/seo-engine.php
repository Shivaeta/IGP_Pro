<?php
/**
 * SEO engine for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register frontend SEO hooks.
 */
function igp_pro_register_seo_module(): void {
	add_action( 'wp_head', 'igp_pro_output_seo_head', 2 );
}

/**
 * Return SEO settings.
 */
function igp_pro_get_seo_settings(): array {
	$settings = get_option( 'igp_pro_seo_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	return wp_parse_args(
		$settings,
		array(
			'enable_meta'       => 'yes',
			'enable_open_graph' => 'yes',
			'enable_json_ld'    => 'yes',
			'organization_name' => get_bloginfo( 'name' ),
			'organization_logo' => '',
		)
	);
}

/**
 * Return sanitized graph-level SEO fields for direct and integration output.
 */
function igp_pro_get_graph_seo_fields( int $post_id ): array {
	$graph = function_exists( 'igp_pro_seo_get_content_graph' ) ? igp_pro_seo_get_content_graph( $post_id ) : array();
	$seo   = isset( $graph['seo'] ) && is_array( $graph['seo'] ) ? $graph['seo'] : array();

	return array(
		'h1'             => isset( $seo['h1'] ) ? sanitize_text_field( (string) $seo['h1'] ) : sanitize_text_field( (string) get_post_meta( $post_id, '_igp_seo_h1', true ) ),
		'title'          => isset( $seo['title'] ) ? sanitize_text_field( (string) $seo['title'] ) : sanitize_text_field( (string) get_post_meta( $post_id, '_igp_seo_title', true ) ),
		'description'    => isset( $seo['description'] ) ? sanitize_textarea_field( (string) $seo['description'] ) : sanitize_textarea_field( (string) get_post_meta( $post_id, IGP_PRO_META_DESCRIPTION_META_KEY, true ) ),
		'canonical_url'  => isset( $seo['canonical_url'] ) ? esc_url_raw( (string) $seo['canonical_url'] ) : esc_url_raw( (string) get_post_meta( $post_id, '_igp_seo_canonical_url', true ) ),
		'robots'         => isset( $seo['robots'] ) ? sanitize_text_field( (string) $seo['robots'] ) : sanitize_text_field( (string) get_post_meta( $post_id, '_igp_seo_robots', true ) ),
		'og_title'       => isset( $seo['og_title'] ) ? sanitize_text_field( (string) $seo['og_title'] ) : sanitize_text_field( (string) get_post_meta( $post_id, '_igp_seo_og_title', true ) ),
		'og_description' => isset( $seo['og_description'] ) ? sanitize_textarea_field( (string) $seo['og_description'] ) : sanitize_textarea_field( (string) get_post_meta( $post_id, '_igp_seo_og_description', true ) ),
		'og_image_id'    => isset( $seo['og_image_id'] ) ? absint( $seo['og_image_id'] ) : absint( get_post_meta( $post_id, '_igp_seo_og_image_id', true ) ),
	);
}

/**
 * Print SEO meta and JSON-LD for singular public content.
 */
function igp_pro_output_seo_head(): void {
	static $printed = false;

	if ( $printed || ! is_singular() || is_admin() || is_feed() ) {
		return;
	}

	$post_id = absint( get_queried_object_id() );
	$post    = get_post( $post_id );
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array( 'post', 'page', 'tour', 'destination' ), true ) ) {
		return;
	}

	$rank_math_owns_meta   = function_exists( 'igp_pro_rank_math_bridge_owns_frontend_output' ) && igp_pro_rank_math_bridge_owns_frontend_output( 'meta' );
	$rank_math_owns_og     = function_exists( 'igp_pro_rank_math_bridge_owns_frontend_output' ) && igp_pro_rank_math_bridge_owns_frontend_output( 'open_graph' );
	$rank_math_owns_schema = function_exists( 'igp_pro_rank_math_bridge_owns_frontend_output' ) && igp_pro_rank_math_bridge_owns_frontend_output( 'schema' );

	$settings    = igp_pro_get_seo_settings();
	$title       = igp_pro_generate_seo_title( $post_id );
	$description = igp_pro_generate_meta_description( $post_id );
	$url         = get_permalink( $post_id );
	$canonical   = igp_pro_generate_canonical_url( $post_id );
	$robots      = igp_pro_generate_robots_directive( $post_id );
	$og_title    = igp_pro_generate_open_graph_title( $post_id, $title );
	$og_desc     = igp_pro_generate_open_graph_description( $post_id, $description );
	$graph       = function_exists( 'igp_pro_seo_get_content_graph' ) ? igp_pro_seo_get_content_graph( $post_id ) : array();
	$image       = igp_pro_generate_open_graph_image( $post_id, $graph );
	$type        = 'tour' === $post->post_type ? 'product' : 'article';

	$printed = true;

	echo "\n<!-- IGP Pro SEO -->\n";

	if ( 'yes' === (string) $settings['enable_meta'] && ! $rank_math_owns_meta ) {
		if ( '' !== $description ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
		}
		if ( '' !== $canonical ) {
			printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
		}
		if ( '' !== $robots ) {
			printf( '<meta name="robots" content="%s" />' . "\n", esc_attr( $robots ) );
		}
	}

	if ( 'yes' === (string) $settings['enable_open_graph'] && ! $rank_math_owns_og ) {
		printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $og_title ) );
		if ( '' !== $og_desc ) {
			printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $og_desc ) );
		}
		printf( '<meta property="og:type" content="%s" />' . "\n", esc_attr( $type ) );
		printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $url ) );
		printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
		if ( '' !== $image ) {
			printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
			printf( '<meta name="twitter:card" content="summary_large_image" />' . "\n" );
		} else {
			printf( '<meta name="twitter:card" content="summary" />' . "\n" );
		}
		printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $og_title ) );
		if ( '' !== $og_desc ) {
			printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $og_desc ) );
		}
	}

	if ( 'yes' === (string) $settings['enable_json_ld'] && ! $rank_math_owns_schema && function_exists( 'igp_pro_generate_json_ld' ) ) {
		$json_ld = igp_pro_generate_json_ld( $post_id );
		if ( ! empty( $json_ld ) ) {
			$encoded = wp_json_encode( $json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			if ( is_string( $encoded ) && '' !== $encoded ) {
				echo '<script type="application/ld+json" class="igp-pro-json-ld">' . $encoded . '</script>' . "\n";
			}
		}
	}
}

/**
 * Generate a page title for SEO contexts.
 */
function igp_pro_generate_seo_title( int $post_id ): string {
	$seo = function_exists( 'igp_pro_get_graph_seo_fields' ) ? igp_pro_get_graph_seo_fields( $post_id ) : array();
	if ( ! empty( $seo['title'] ) ) {
		return trim( wp_strip_all_tags( (string) $seo['title'] ) );
	}

	$title = get_the_title( $post_id );
	return trim( wp_strip_all_tags( $title ) );
}

/**
 * Generate meta description derived from structured content.
 */
function igp_pro_generate_meta_description( int $post_id ): string {
	$seo = function_exists( 'igp_pro_get_graph_seo_fields' ) ? igp_pro_get_graph_seo_fields( $post_id ) : array();
	if ( ! empty( $seo['description'] ) ) {
		return igp_pro_seo_trim_description( (string) $seo['description'] );
	}

	$manual = function_exists( 'igp_pro_load_meta_description' ) ? igp_pro_load_meta_description( $post_id ) : (string) get_post_meta( $post_id, '_igp_pro_meta_description', true );
	if ( '' !== trim( $manual ) ) {
		return igp_pro_seo_trim_description( $manual );
	}

	$graph = function_exists( 'igp_pro_seo_get_content_graph' ) ? igp_pro_seo_get_content_graph( $post_id ) : array( 'sections' => array() );
	$text  = function_exists( 'igp_pro_seo_first_section_text' ) ? igp_pro_seo_first_section_text(
		$graph,
		array( 'hero', 'section', 'cta', 'tour_cards', 'destination_cards', 'faq', 'itinerary' ),
		array( 'subheading', 'description', 'heading', 'title' )
	) : '';

	if ( '' === $text ) {
		$post = get_post( $post_id );
		if ( $post instanceof WP_Post ) {
			if ( has_excerpt( $post ) ) {
				$text = get_the_excerpt( $post );
			} else {
				$text = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
			}
		}
	}

	return igp_pro_seo_trim_description( $text );
}

/**
 * Generate a canonical URL for direct SEO output.
 */
function igp_pro_generate_canonical_url( int $post_id ): string {
	$seo = function_exists( 'igp_pro_get_graph_seo_fields' ) ? igp_pro_get_graph_seo_fields( $post_id ) : array();
	if ( ! empty( $seo['canonical_url'] ) ) {
		return esc_url_raw( (string) $seo['canonical_url'] );
	}

	return esc_url_raw( (string) get_permalink( $post_id ) );
}

/**
 * Generate robots directives for direct SEO output.
 */
function igp_pro_generate_robots_directive( int $post_id ): string {
	$seo = function_exists( 'igp_pro_get_graph_seo_fields' ) ? igp_pro_get_graph_seo_fields( $post_id ) : array();
	if ( ! empty( $seo['robots'] ) ) {
		return sanitize_text_field( (string) $seo['robots'] );
	}

	return 'publish' === get_post_status( $post_id ) && (int) get_option( 'blog_public', 1 ) === 1 ? 'index,follow' : 'noindex,nofollow';
}

/**
 * Generate Open Graph title.
 */
function igp_pro_generate_open_graph_title( int $post_id, string $fallback = '' ): string {
	$seo = function_exists( 'igp_pro_get_graph_seo_fields' ) ? igp_pro_get_graph_seo_fields( $post_id ) : array();
	return ! empty( $seo['og_title'] ) ? trim( wp_strip_all_tags( (string) $seo['og_title'] ) ) : $fallback;
}

/**
 * Generate Open Graph description.
 */
function igp_pro_generate_open_graph_description( int $post_id, string $fallback = '' ): string {
	$seo = function_exists( 'igp_pro_get_graph_seo_fields' ) ? igp_pro_get_graph_seo_fields( $post_id ) : array();
	return ! empty( $seo['og_description'] ) ? igp_pro_seo_trim_description( (string) $seo['og_description'] ) : $fallback;
}

/**
 * Generate Open Graph image URL.
 */
function igp_pro_generate_open_graph_image( int $post_id, array $graph = array() ): string {
	$seo = function_exists( 'igp_pro_get_graph_seo_fields' ) ? igp_pro_get_graph_seo_fields( $post_id ) : array();
	if ( ! empty( $seo['og_image_id'] ) ) {
		$image = (string) wp_get_attachment_image_url( absint( $seo['og_image_id'] ), 'full' );
		if ( '' !== $image ) {
			return esc_url_raw( $image );
		}
	}

	return function_exists( 'igp_pro_seo_get_primary_image' ) ? igp_pro_seo_get_primary_image( $post_id, $graph ) : '';
}

/**
 * Trim meta description to a safe search-snippet length.
 */
function igp_pro_seo_trim_description( string $text ): string {
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) ?? '' );
	if ( '' === $text ) {
		return '';
	}
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > 160 ) {
		$text = mb_substr( $text, 0, 157 ) . '…';
	} elseif ( strlen( $text ) > 160 ) {
		$text = substr( $text, 0, 157 ) . '…';
	}
	return $text;
}
