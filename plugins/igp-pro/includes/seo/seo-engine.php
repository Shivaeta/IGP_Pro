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

	$settings    = igp_pro_get_seo_settings();
	$title       = igp_pro_generate_seo_title( $post_id );
	$description = igp_pro_generate_meta_description( $post_id );
	$url         = get_permalink( $post_id );
	$graph       = function_exists( 'igp_pro_seo_get_content_graph' ) ? igp_pro_seo_get_content_graph( $post_id ) : array();
	$image       = function_exists( 'igp_pro_seo_get_primary_image' ) ? igp_pro_seo_get_primary_image( $post_id, $graph ) : '';
	$type        = 'tour' === $post->post_type ? 'product' : 'article';

	$printed = true;

	echo "\n<!-- IGP Pro SEO -->\n";

	if ( 'yes' === (string) $settings['enable_meta'] && '' !== $description ) {
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
	}

	if ( 'yes' === (string) $settings['enable_open_graph'] ) {
		printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
		if ( '' !== $description ) {
			printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
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
		printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $title ) );
		if ( '' !== $description ) {
			printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $description ) );
		}
	}

	if ( 'yes' === (string) $settings['enable_json_ld'] && function_exists( 'igp_pro_generate_json_ld' ) ) {
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
	$title = get_the_title( $post_id );
	return trim( wp_strip_all_tags( $title ) );
}

/**
 * Generate meta description derived from structured content.
 */
function igp_pro_generate_meta_description( int $post_id ): string {
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

/**
 * Produce technical SEO checks and internal-link hints for an admin-selected post.
 */
function igp_pro_run_seo_audit( int $post_id ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return array(
			'checks' => array( array( 'status' => 'fail', 'label' => __( 'Post not found.', 'igp-pro' ) ) ),
			'hints'  => array(),
		);
	}

	$description = igp_pro_generate_meta_description( $post_id );
	$json_ld     = function_exists( 'igp_pro_generate_json_ld' ) ? igp_pro_generate_json_ld( $post_id ) : array();
	$encoded     = wp_json_encode( $json_ld );
	$decoded     = is_string( $encoded ) ? json_decode( $encoded, true ) : null;
	$content     = (string) $post->post_content;
	$title_len   = strlen( wp_strip_all_tags( get_the_title( $post ) ) );
	$desc_len    = strlen( $description );

	$checks = array(
		array(
			'status' => $title_len >= 20 && $title_len <= 70 ? 'pass' : 'warn',
			'label'  => sprintf( __( 'Title length: %d characters.', 'igp-pro' ), $title_len ),
		),
		array(
			'status' => $desc_len >= 80 && $desc_len <= 160 ? 'pass' : ( $desc_len > 0 ? 'warn' : 'fail' ),
			'label'  => sprintf( __( 'Meta description length: %d characters.', 'igp-pro' ), $desc_len ),
		),
		array(
			'status' => is_array( $decoded ) && isset( $decoded['@context'], $decoded['@graph'] ) ? 'pass' : 'fail',
			'label'  => __( 'JSON-LD payload encodes and contains @context/@graph.', 'igp-pro' ),
		),
		array(
			'status' => has_post_thumbnail( $post ) || false !== strpos( $content, 'background_image' ) || false !== strpos( $content, 'wp:image' ) ? 'pass' : 'warn',
			'label'  => __( 'Primary image or image block detected.', 'igp-pro' ),
		),
		array(
			'status' => get_permalink( $post ) ? 'pass' : 'fail',
			'label'  => __( 'Canonical permalink is available.', 'igp-pro' ),
		),
	);

	return array(
		'checks' => $checks,
		'hints'  => igp_pro_get_internal_link_hints( $post ),
	);
}

/**
 * Produce lightweight internal-link hints from related content.
 */
function igp_pro_get_internal_link_hints( WP_Post $post ): array {
	$hints      = array();
	$post_types = 'tour' === $post->post_type ? array( 'destination', 'tour' ) : array( 'tour', 'destination' );
	$content    = strtolower( wp_strip_all_tags( (string) $post->post_content ) );

	$query = new WP_Query(
		array(
			'post_type'           => $post_types,
			'post_status'         => 'publish',
			'post__not_in'        => array( $post->ID ),
			'posts_per_page'      => 6,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	foreach ( $query->posts as $candidate ) {
		if ( ! $candidate instanceof WP_Post ) {
			continue;
		}
		$title = strtolower( get_the_title( $candidate ) );
		$url   = get_permalink( $candidate );
		if ( '' !== $title && false === strpos( $content, $title ) ) {
			$hints[] = array(
				'title' => get_the_title( $candidate ),
				'url'   => $url,
				'type'  => get_post_type( $candidate ),
			);
		}
	}

	return $hints;
}
