<?php
/**
 * Page-level heading policy helpers.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the page-level H1 when a raw Content Graph is rendered outside a theme
 * template that already provides the post title.
 *
 * @param array<string,mixed> $outline Outline data.
 * @return string
 */
function igp_pro_render_page_h1_from_outline( array $outline ): string {
	$h1 = isset( $outline['h1'] ) && is_array( $outline['h1'] ) ? $outline['h1'] : array();
	$text = trim( wp_strip_all_tags( (string) ( $h1['text'] ?? '' ) ) );
	if ( '' === $text ) {
		return '';
	}

	return '<h1 class="igp-page-title igp-page-title--' . esc_attr( sanitize_html_class( (string) ( $h1['source'] ?? 'policy' ) ) ) . '">' . esc_html( $text ) . '</h1>';
}

/**
 * Add page-level outline context to a block render context.
 *
 * @param array<string,mixed> $context Context.
 * @param array<string,mixed> $outline Outline data.
 * @param array<string,mixed> $section Section.
 * @return array<string,mixed>
 */
function igp_pro_apply_heading_policy_to_block_context( array $context, array $outline, array $section ): array {
	$h1 = isset( $outline['h1'] ) && is_array( $outline['h1'] ) ? $outline['h1'] : array();
	if ( 'hero_fallback' !== (string) ( $h1['source'] ?? '' ) ) {
		return $context;
	}

	$section_id = isset( $section['id'] ) ? sanitize_key( (string) $section['id'] ) : '';
	if ( '' !== $section_id && $section_id === sanitize_key( (string) ( $h1['section_id'] ?? '' ) ) ) {
		$context['igp_page_h1_block'] = true;
	}

	return $context;
}

/**
 * Determine whether rendered post content contains IGP output or IGP blocks.
 *
 * @param string  $content Rendered content.
 * @param WP_Post $post    Current post.
 * @return bool
 */
function igp_pro_content_has_igp_output( string $content, WP_Post $post ): bool {
	if ( false !== strpos( $content, 'data-igp-block=' ) || false !== strpos( $content, 'class="igp-block' ) || false !== strpos( $content, "class='igp-block" ) ) {
		return true;
	}

	$post_content = (string) $post->post_content;
	return false !== strpos( $post_content, '<!-- wp:igp-pro/' );
}

/**
 * Build page-level H1 markup for frontend content injection.
 *
 * Dynamic Gutenberg rendering calls each block individually, so the full
 * Content Graph renderer is not always responsible for the frontend page.
 * This helper supplies the required page-level H1 when the active theme does
 * not render one inside the content area.
 *
 * @param array<string,mixed> $outline Outline data.
 * @return string
 */
function igp_pro_render_content_page_h1_from_outline( array $outline ): string {
	$h1 = isset( $outline['h1'] ) && is_array( $outline['h1'] ) ? $outline['h1'] : array();
	$text = trim( wp_strip_all_tags( (string) ( $h1['text'] ?? '' ) ) );
	if ( '' === $text ) {
		return '';
	}

	$source = sanitize_html_class( str_replace( '.', '-', (string) ( $h1['source'] ?? 'policy' ) ) );
	if ( '' === $source ) {
		$source = 'policy';
	}

	return '<h1 class="igp-page-title igp-page-title--' . esc_attr( $source ) . '">' . esc_html( $text ) . '</h1>';
}

/**
 * Ensure IGP-rendered singular content has exactly one page-level H1.
 *
 * Themes vary: some output the post title outside `the_content`, while others
 * suppress it for block-first layouts. The Phase 8 policy cannot rely on the
 * Hero block for H1, so this filter injects the resolved page-level H1 when the
 * rendered content contains IGP blocks and does not already contain an H1.
 *
 * @param string $content Rendered post content.
 * @return string
 */
function igp_pro_ensure_content_has_page_h1( string $content ): string {
	if ( ! function_exists( 'igp_pro_semantic_outline_enabled' ) || ! igp_pro_semantic_outline_enabled() ) {
		return $content;
	}

	if ( is_admin() || is_feed() || is_preview() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	if ( preg_match( '/<h1\b/i', $content ) ) {
		return $content;
	}

	$post = get_post();
	if ( ! $post instanceof WP_Post || ! igp_pro_content_has_igp_output( $content, $post ) ) {
		return $content;
	}

	$graph = array();
	if ( function_exists( 'igp_pro_load_content_graph' ) ) {
		$stored_graph = igp_pro_load_content_graph( (int) $post->ID );
		if ( is_array( $stored_graph ) ) {
			$graph = $stored_graph;
		}
	}

	if ( empty( $graph ) && function_exists( 'igp_pro_recover_graph_from_post_content' ) ) {
		$parsed_graph = igp_pro_recover_graph_from_post_content( (int) $post->ID );
		if ( is_array( $parsed_graph ) && ! empty( $parsed_graph['sections'] ) ) {
			$graph = $parsed_graph;
		}
	}

	if ( empty( $graph ) ) {
		$graph = array(
			'version'  => 'v1',
			'sections' => array(),
		);
	}

	$outline = array(
		'h1' => function_exists( 'igp_pro_resolve_page_h1' ) ? igp_pro_resolve_page_h1( $graph, array( 'post_id' => (int) $post->ID ) ) : array(
			'text'   => get_the_title( $post ),
			'source' => 'post_title',
		),
	);

	$should_inject = apply_filters( 'igp_pro_should_inject_content_h1', true, $post, $content, $outline );
	if ( ! $should_inject ) {
		return $content;
	}

	$h1_html = igp_pro_render_content_page_h1_from_outline( $outline );
	if ( '' === $h1_html ) {
		return $content;
	}

	return $h1_html . "\n" . $content;
}

