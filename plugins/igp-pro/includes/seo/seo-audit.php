<?php
/**
 * SEO health audit service for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run the structured SEO health audit for a post/page/tour/destination.
 *
 * @param int $post_id Post ID.
 * @return array<string,mixed>
 */
function igp_pro_run_seo_audit( int $post_id ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return array(
			'post_id' => $post_id,
			'title'   => '',
			'groups'  => array(
				igp_pro_seo_audit_group( 'status', __( 'Post status', 'igp-pro' ), array(
					igp_pro_seo_audit_check( 'post_found', 'status', 'fail', __( 'Post not found.', 'igp-pro' ) ),
				) ),
			),
			'checks'  => array( igp_pro_seo_audit_check( 'post_found', 'status', 'fail', __( 'Post not found.', 'igp-pro' ) ) ),
			'hints'   => array(),
			'score'   => 0,
		);
	}

	$graph = function_exists( 'igp_pro_seo_get_content_graph' ) ? igp_pro_seo_get_content_graph( $post_id ) : array( 'version' => 'v1', 'sections' => array() );
	if ( ! is_array( $graph ) ) {
		$graph = array( 'version' => 'v1', 'sections' => array() );
	}

	$permalink     = get_permalink( $post );
	$frontend_html = igp_pro_seo_audit_get_frontend_html( $post, $graph );
	$checks        = array();
	$groups        = array();

	$outline_checks = igp_pro_seo_audit_outline_checks( $post, $graph, $frontend_html );
	$groups[]       = igp_pro_seo_audit_group( 'outline', __( 'Outline and headings', 'igp-pro' ), $outline_checks );
	$checks         = array_merge( $checks, $outline_checks );

	$meta_checks = igp_pro_seo_audit_meta_checks( $post, $frontend_html );
	$groups[]    = igp_pro_seo_audit_group( 'meta', __( 'Meta title, description, canonical, and indexability', 'igp-pro' ), $meta_checks );
	$checks      = array_merge( $checks, $meta_checks );

	$schema_checks = igp_pro_seo_audit_schema_checks( $post, $graph, $frontend_html );
	$groups[]      = igp_pro_seo_audit_group( 'schema', __( 'Schema status', 'igp-pro' ), $schema_checks );
	$checks        = array_merge( $checks, $schema_checks );

	$image_checks = igp_pro_seo_audit_image_checks( $post, $graph, $frontend_html );
	$groups[]     = igp_pro_seo_audit_group( 'images', __( 'Image SEO and alt text', 'igp-pro' ), $image_checks );
	$checks       = array_merge( $checks, $image_checks );

	$link_checks = igp_pro_seo_audit_link_checks( $post, $frontend_html );
	$groups[]    = igp_pro_seo_audit_group( 'links', __( 'Internal links and orphan risk', 'igp-pro' ), $link_checks );
	$checks      = array_merge( $checks, $link_checks );

	$cwv_checks = igp_pro_seo_audit_cwv_checks( $post );
	$groups[]   = igp_pro_seo_audit_group( 'performance', __( 'CWV and cache summary', 'igp-pro' ), $cwv_checks );
	$checks     = array_merge( $checks, $cwv_checks );

	$rank_math_checks = igp_pro_seo_audit_integration_checks();
	$groups[]         = igp_pro_seo_audit_group( 'integrations', __( 'Optional SEO integrations', 'igp-pro' ), $rank_math_checks );
	$checks           = array_merge( $checks, $rank_math_checks );

	return array(
		'post_id'       => $post_id,
		'title'         => get_the_title( $post ),
		'permalink'     => is_string( $permalink ) ? $permalink : '',
		'groups'        => $groups,
		'checks'        => $checks,
		'hints'         => function_exists( 'igp_pro_get_internal_link_hints' ) ? igp_pro_get_internal_link_hints( $post ) : array(),
		'link_intel'    => function_exists( 'igp_pro_generate_internal_link_opportunities' ) ? igp_pro_generate_internal_link_opportunities( $post->ID, array( 'limit' => 8 ) ) : array(),
		'score'         => igp_pro_seo_audit_score( $checks ),
		'frontend_html' => array(
			'source' => (string) ( $frontend_html['source'] ?? 'none' ),
			'error'  => (string) ( $frontend_html['error'] ?? '' ),
		),
	);
}

/**
 * Create a normalized audit group.
 *
 * @param string $id Group ID.
 * @param string $label Group label.
 * @param array<int,array<string,mixed>> $checks Checks.
 * @return array<string,mixed>
 */
function igp_pro_seo_audit_group( string $id, string $label, array $checks ): array {
	return array(
		'id'     => sanitize_key( $id ),
		'label'  => $label,
		'checks' => $checks,
	);
}

/**
 * Create a normalized audit check.
 *
 * @param string $id Check ID.
 * @param string $group Group ID.
 * @param string $status pass|warn|fail|info.
 * @param string $label Label.
 * @param string $detail Detail.
 * @param array<string,mixed> $data Extra data.
 * @return array<string,mixed>
 */
function igp_pro_seo_audit_check( string $id, string $group, string $status, string $label, string $detail = '', array $data = array() ): array {
	$status = in_array( $status, array( 'pass', 'warn', 'fail', 'info' ), true ) ? $status : 'info';
	return array(
		'id'     => sanitize_key( $id ),
		'group'  => sanitize_key( $group ),
		'status' => $status,
		'label'  => $label,
		'detail' => $detail,
		'data'   => $data,
	);
}

/**
 * Compute a simple health score from checks.
 *
 * @param array<int,array<string,mixed>> $checks Checks.
 * @return int
 */
function igp_pro_seo_audit_score( array $checks ): int {
	$scored = 0;
	$total  = 0;

	foreach ( $checks as $check ) {
		$status = (string) ( $check['status'] ?? 'info' );
		if ( 'info' === $status ) {
			continue;
		}
		$total++;
		if ( 'pass' === $status ) {
			$scored += 2;
		} elseif ( 'warn' === $status ) {
			$scored += 1;
		}
	}

	if ( 0 === $total ) {
		return 0;
	}

	return (int) round( ( $scored / ( $total * 2 ) ) * 100 );
}

/**
 * Fetch frontend HTML for page-source-like checks. Falls back safely.
 *
 * @param WP_Post $post Post.
 * @param array<string,mixed> $graph Content Graph.
 * @return array{html:string,source:string,error:string}
 */
function igp_pro_seo_audit_get_frontend_html( WP_Post $post, array $graph ): array {
	$url = get_permalink( $post );
	if ( is_string( $url ) && '' !== $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 3,
				'headers'     => array(
					'X-IGP-Pro-Audit' => '1',
				),
			)
		);

		if ( ! is_wp_error( $response ) ) {
			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = (string) wp_remote_retrieve_body( $response );
			if ( $code >= 200 && $code < 400 && '' !== trim( $body ) ) {
				return array(
					'html'   => $body,
					'source' => 'frontend_http',
					'error'  => '',
				);
			}
		} else {
			$error = $response->get_error_message();
		}
	}

	$fallback = igp_pro_seo_audit_render_fallback_html( $post, $graph );
	return array(
		'html'   => $fallback,
		'source' => 'fallback_render',
		'error'  => isset( $error ) ? (string) $error : '',
	);
}

/**
 * Render a safe fallback HTML fragment for audits when HTTP fetch fails.
 *
 * @param WP_Post $post Post.
 * @param array<string,mixed> $graph Content Graph.
 * @return string
 */
function igp_pro_seo_audit_render_fallback_html( WP_Post $post, array $graph ): string {
	if ( function_exists( 'igp_pro_render_content_graph' ) && isset( $graph['sections'] ) && is_array( $graph['sections'] ) && ! empty( $graph['sections'] ) ) {
		$rendered = igp_pro_render_content_graph(
			$graph,
			array(
				'post_id'        => (int) $post->ID,
				'render_page_h1' => true,
			)
		);
		if ( is_string( $rendered ) && '' !== trim( $rendered ) ) {
			return $rendered;
		}
	}

	if ( function_exists( 'do_blocks' ) ) {
		return (string) do_blocks( (string) $post->post_content );
	}

	return (string) $post->post_content;
}

/**
 * Audit H1 count and heading hierarchy.
 *
 * @param WP_Post $post Post.
 * @param array<string,mixed> $graph Content Graph.
 * @param array<string,string> $frontend_html Frontend HTML data.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_seo_audit_outline_checks( WP_Post $post, array $graph, array $frontend_html ): array {
	$html     = (string) ( $frontend_html['html'] ?? '' );
	$source   = (string) ( $frontend_html['source'] ?? 'none' );
	$headings = igp_pro_seo_audit_extract_headings( $html );
	$h1_count = 0;
	foreach ( $headings as $heading ) {
		if ( 1 === (int) $heading['level'] ) {
			$h1_count++;
		}
	}

	if ( 1 === $h1_count ) {
		$h1_check = igp_pro_seo_audit_check(
			'h1_count',
			'outline',
			'pass',
			sprintf( __( 'H1 count: %d.', 'igp-pro' ), $h1_count ),
			sprintf( __( 'Checked using %s.', 'igp-pro' ), $source ),
			array( 'count' => $h1_count )
		);
	} elseif ( 0 === $h1_count && function_exists( 'igp_pro_resolve_page_h1' ) ) {
		$resolved = igp_pro_resolve_page_h1( $graph, array( 'post_id' => (int) $post->ID ) );
		$h1_check = igp_pro_seo_audit_check(
			'h1_count',
			'outline',
			'fail',
			__( 'H1 count: 0.', 'igp-pro' ),
			sprintf(
				__( 'Resolved H1 would be "%1$s" from %2$s, but no rendered H1 was detected in the checked HTML source.', 'igp-pro' ),
				(string) ( $resolved['text'] ?? '' ),
				(string) ( $resolved['source'] ?? 'none' )
			),
			array( 'count' => 0 )
		);
	} else {
		$h1_check = igp_pro_seo_audit_check(
			'h1_count',
			'outline',
			'fail',
			sprintf( __( 'H1 count: %d.', 'igp-pro' ), $h1_count ),
			__( 'IGP pages should render exactly one frontend H1.', 'igp-pro' ),
			array( 'count' => $h1_count )
		);
	}

	$hierarchy = igp_pro_seo_audit_heading_hierarchy_result( $headings, $graph );

	return array( $h1_check, $hierarchy );
}

/**
 * Extract h1-h6 tags from rendered HTML.
 *
 * @param string $html HTML.
 * @return array<int,array{level:int,text:string}>
 */
function igp_pro_seo_audit_extract_headings( string $html ): array {
	$headings = array();
	if ( '' === trim( $html ) ) {
		return $headings;
	}

	if ( preg_match_all( '/<h([1-6])\b[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $match[2] ) ) ?? '' );
			if ( '' === $text ) {
				continue;
			}
			$headings[] = array(
				'level' => (int) $match[1],
				'text'  => $text,
			);
		}
	}

	return $headings;
}

/**
 * Return heading hierarchy audit result.
 *
 * @param array<int,array{level:int,text:string}> $headings Headings.
 * @param array<string,mixed> $graph Content Graph.
 * @return array<string,mixed>
 */
function igp_pro_seo_audit_heading_hierarchy_result( array $headings, array $graph ): array {
	$previous = 0;
	foreach ( $headings as $heading ) {
		$level = (int) $heading['level'];
		if ( 0 === $previous ) {
			$previous = $level;
			continue;
		}
		if ( $level > $previous + 1 ) {
			return igp_pro_seo_audit_check(
				'heading_hierarchy',
				'outline',
				'warn',
				__( 'Heading hierarchy issue detected.', 'igp-pro' ),
				sprintf( __( 'Heading jumps from H%1$d to H%2$d near "%3$s".', 'igp-pro' ), $previous, $level, (string) $heading['text'] )
			);
		}
		$previous = $level;
	}

	if ( function_exists( 'igp_pro_validate_heading_hierarchy' ) ) {
		$validation = igp_pro_validate_heading_hierarchy( $graph );
		if ( is_wp_error( $validation ) ) {
			return igp_pro_seo_audit_check( 'heading_hierarchy', 'outline', 'fail', __( 'Content Graph heading hierarchy is invalid.', 'igp-pro' ), $validation->get_error_message() );
		}
	}

	return igp_pro_seo_audit_check( 'heading_hierarchy', 'outline', 'pass', __( 'Heading hierarchy has no detected jumps.', 'igp-pro' ), sprintf( __( '%d visible headings checked.', 'igp-pro' ), count( $headings ) ) );
}

/**
 * Audit title, description, canonical, and indexability.
 *
 * @param WP_Post $post Post.
 * @param array<string,string> $frontend_html HTML data.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_seo_audit_meta_checks( WP_Post $post, array $frontend_html ): array {
	$post_id     = (int) $post->ID;
	$title       = function_exists( 'igp_pro_generate_seo_title' ) ? igp_pro_generate_seo_title( $post_id ) : get_the_title( $post );
	$description = function_exists( 'igp_pro_generate_meta_description' ) ? igp_pro_generate_meta_description( $post_id ) : '';
	$title_len   = function_exists( 'mb_strlen' ) ? mb_strlen( wp_strip_all_tags( $title ) ) : strlen( wp_strip_all_tags( $title ) );
	$desc_len    = function_exists( 'mb_strlen' ) ? mb_strlen( wp_strip_all_tags( $description ) ) : strlen( wp_strip_all_tags( $description ) );
	$permalink   = get_permalink( $post );
	$html        = (string) ( $frontend_html['html'] ?? '' );
	$checks      = array();

	$checks[] = igp_pro_seo_audit_check(
		'meta_title',
		'meta',
		$title_len >= 20 && $title_len <= 70 ? 'pass' : ( $title_len > 0 ? 'warn' : 'fail' ),
		sprintf( __( 'SEO title length: %d characters.', 'igp-pro' ), $title_len ),
		$title_len >= 20 && $title_len <= 70 ? __( 'Title length is within the recommended range.', 'igp-pro' ) : __( 'Recommended range is 20–70 characters.', 'igp-pro' )
	);

	$checks[] = igp_pro_seo_audit_check(
		'meta_description',
		'meta',
		$desc_len >= 80 && $desc_len <= 160 ? 'pass' : ( $desc_len > 0 ? 'warn' : 'fail' ),
		sprintf( __( 'Meta description length: %d characters.', 'igp-pro' ), $desc_len ),
		$desc_len >= 80 && $desc_len <= 160 ? __( 'Description length is within the recommended range.', 'igp-pro' ) : __( 'Recommended range is 80–160 characters.', 'igp-pro' )
	);

	$canonical_in_html = false !== stripos( $html, 'rel="canonical"' ) || false !== stripos( $html, "rel='canonical'" );
	$checks[]          = igp_pro_seo_audit_check(
		'canonical',
		'meta',
		is_string( $permalink ) && '' !== $permalink ? 'pass' : 'fail',
		is_string( $permalink ) && '' !== $permalink ? __( 'Canonical URL is available.', 'igp-pro' ) : __( 'Canonical URL is missing.', 'igp-pro' ),
		$canonical_in_html ? __( 'A canonical tag was detected in checked HTML.', 'igp-pro' ) : __( 'Canonical source resolves to the WordPress permalink; no duplicate tag is added by this audit.', 'igp-pro' ),
		array( 'canonical' => is_string( $permalink ) ? $permalink : '' )
	);

	$robots_noindex = false !== stripos( $html, 'noindex' );
	$blog_public    = (int) get_option( 'blog_public', 1 );
	$status         = 'publish' === get_post_status( $post ) && 1 === $blog_public && ! $robots_noindex ? 'pass' : 'warn';
	$detail         = array();
	if ( 'publish' !== get_post_status( $post ) ) {
		$detail[] = sprintf( __( 'Post status is %s.', 'igp-pro' ), get_post_status( $post ) );
	}
	if ( 1 !== $blog_public ) {
		$detail[] = __( 'WordPress discourages search engines from indexing the site.', 'igp-pro' );
	}
	if ( $robots_noindex ) {
		$detail[] = __( 'A noindex directive was detected in checked HTML.', 'igp-pro' );
	}
	$checks[] = igp_pro_seo_audit_check(
		'indexability',
		'meta',
		$status,
		__( 'Indexability status checked.', 'igp-pro' ),
		empty( $detail ) ? __( 'Post is published and no noindex signal was detected.', 'igp-pro' ) : implode( ' ', $detail )
	);

	return $checks;
}

/**
 * Audit schema generation and frontend status.
 *
 * @param WP_Post $post Post.
 * @param array<string,mixed> $graph Content Graph.
 * @param array<string,string> $frontend_html HTML data.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_seo_audit_schema_checks( WP_Post $post, array $graph, array $frontend_html ): array {
	unset( $graph );
	$json_ld = function_exists( 'igp_pro_generate_json_ld' ) ? igp_pro_generate_json_ld( (int) $post->ID ) : array();
	$valid   = is_array( $json_ld ) && isset( $json_ld['@context'], $json_ld['@graph'] ) && is_array( $json_ld['@graph'] ) && ! empty( $json_ld['@graph'] );
	$html    = (string) ( $frontend_html['html'] ?? '' );
	$count   = preg_match_all( '/<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>/i', $html );

	return array(
		igp_pro_seo_audit_check(
			'schema_graph',
			'schema',
			$valid ? 'pass' : 'fail',
			$valid ? __( 'Structured schema graph is available.', 'igp-pro' ) : __( 'Structured schema graph is missing or invalid.', 'igp-pro' ),
			sprintf( __( 'Detected %d JSON-LD script(s) in checked HTML.', 'igp-pro' ), (int) $count ),
			array( 'json_ld_scripts' => (int) $count )
		),
	);
}

/**
 * Audit image alt coverage.
 *
 * @param WP_Post $post Post.
 * @param array<string,mixed> $graph Content Graph.
 * @param array<string,string> $frontend_html HTML data.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_seo_audit_image_checks( WP_Post $post, array $graph, array $frontend_html ): array {
	$images = igp_pro_seo_audit_collect_images( $post, $graph, (string) ( $frontend_html['html'] ?? '' ) );
	$total  = count( $images );
	$bad    = 0;
	foreach ( $images as $image ) {
		$alt = isset( $image['alt'] ) ? trim( wp_strip_all_tags( (string) $image['alt'] ) ) : '';
		if ( '' === $alt || igp_pro_seo_audit_is_weak_alt( $alt ) ) {
			$bad++;
		}
	}

	if ( 0 === $total ) {
		$status = 'warn';
		$label  = __( 'No auditable images found.', 'igp-pro' );
		$detail = __( 'Pages with strong travel intent usually need at least one meaningful image or Open Graph image.', 'igp-pro' );
	} elseif ( 0 === $bad ) {
		$status = 'pass';
		$label  = sprintf( __( 'Image alt coverage: %1$d/%2$d acceptable.', 'igp-pro' ), $total, $total );
		$detail = __( 'No missing or weak alt text was detected.', 'igp-pro' );
	} else {
		$status = 'warn';
		$label  = sprintf( __( 'Image alt coverage issue: %1$d of %2$d image(s) missing or weak.', 'igp-pro' ), $bad, $total );
		$detail = __( 'Update featured image alt text and block image alt fields where appropriate.', 'igp-pro' );
	}

	return array(
		igp_pro_seo_audit_check( 'image_alt_coverage', 'images', $status, $label, $detail, array( 'total' => $total, 'weak_or_missing' => $bad ) ),
	);
}

/**
 * Collect image usage from featured image, graph image fields, OG image meta, and rendered HTML.
 *
 * @param WP_Post $post Post.
 * @param array<string,mixed> $graph Content Graph.
 * @param string $html HTML.
 * @return array<int,array<string,string>>
 */
function igp_pro_seo_audit_collect_images( WP_Post $post, array $graph, string $html ): array {
	$images = array();

	$thumb_id = get_post_thumbnail_id( $post );
	if ( $thumb_id ) {
		$images[] = array(
			'source' => 'featured_image',
			'id'     => (string) $thumb_id,
			'url'    => (string) wp_get_attachment_image_url( $thumb_id, 'full' ),
			'alt'    => (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ),
		);
	}

	$og_image_id = get_post_meta( (int) $post->ID, '_igp_seo_og_image_id', true );
	if ( $og_image_id ) {
		$images[] = array(
			'source' => 'og_image',
			'id'     => (string) absint( $og_image_id ),
			'url'    => (string) wp_get_attachment_image_url( absint( $og_image_id ), 'full' ),
			'alt'    => (string) get_post_meta( absint( $og_image_id ), '_wp_attachment_image_alt', true ),
		);
	}

	igp_pro_seo_audit_collect_graph_images_recursive( $graph, 'graph', $images );

	if ( preg_match_all( '/<img\b[^>]*>/i', $html, $matches ) ) {
		foreach ( $matches[0] as $img_tag ) {
			$alt = '';
			$url = '';
			if ( preg_match( '/\balt=["\']([^"\']*)["\']/i', $img_tag, $alt_match ) ) {
				$alt = html_entity_decode( (string) $alt_match[1], ENT_QUOTES, get_bloginfo( 'charset' ) );
			}
			if ( preg_match( '/\bsrc=["\']([^"\']*)["\']/i', $img_tag, $src_match ) ) {
				$url = esc_url_raw( (string) $src_match[1] );
			}
			$images[] = array(
				'source' => 'rendered_img',
				'id'     => '',
				'url'    => $url,
				'alt'    => $alt,
			);
		}
	}

	return igp_pro_seo_audit_dedupe_images( $images );
}

/**
 * Recursively collect likely image objects from a graph.
 *
 * @param mixed $value Value.
 * @param string $path Path.
 * @param array<int,array<string,string>> $images Images.
 * @return void
 */
function igp_pro_seo_audit_collect_graph_images_recursive( $value, string $path, array &$images ): void {
	if ( ! is_array( $value ) ) {
		return;
	}

	$has_url = isset( $value['url'] ) && is_scalar( $value['url'] ) && '' !== trim( (string) $value['url'] );
	$has_id  = ( isset( $value['id'] ) || isset( $value['ID'] ) ) && ( is_numeric( $value['id'] ?? null ) || is_numeric( $value['ID'] ?? null ) );
	if ( $has_url || $has_id ) {
		$attachment_id = absint( $value['id'] ?? ( $value['ID'] ?? 0 ) );
		$images[]      = array(
			'source' => $path,
			'id'     => $attachment_id > 0 ? (string) $attachment_id : '',
			'url'    => $has_url ? esc_url_raw( (string) $value['url'] ) : ( $attachment_id > 0 ? (string) wp_get_attachment_image_url( $attachment_id, 'full' ) : '' ),
			'alt'    => isset( $value['alt'] ) ? (string) $value['alt'] : ( $attachment_id > 0 ? (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) : '' ),
		);
	}

	foreach ( $value as $key => $child ) {
		igp_pro_seo_audit_collect_graph_images_recursive( $child, $path . '.' . sanitize_key( (string) $key ), $images );
	}
}

/**
 * Remove duplicate images by ID or URL.
 *
 * @param array<int,array<string,string>> $images Images.
 * @return array<int,array<string,string>>
 */
function igp_pro_seo_audit_dedupe_images( array $images ): array {
	$seen   = array();
	$result = array();
	foreach ( $images as $image ) {
		$key = '' !== (string) ( $image['id'] ?? '' ) ? 'id:' . (string) $image['id'] : 'url:' . (string) ( $image['url'] ?? '' );
		if ( '' === $key || isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$result[]     = $image;
	}
	return $result;
}

/**
 * Determine if alt text is weak/generic.
 *
 * @param string $alt Alt text.
 * @return bool
 */
function igp_pro_seo_audit_is_weak_alt( string $alt ): bool {
	$alt = strtolower( trim( preg_replace( '/\s+/', ' ', $alt ) ?? '' ) );
	return in_array( $alt, array( '', 'image', 'photo', 'picture', 'img', 'banner', 'hero', 'tour image', 'destination image' ), true );
}

/**
 * Audit internal links and orphan risk.
 *
 * @param WP_Post $post Post.
 * @param array<string,string> $frontend_html HTML data.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_seo_audit_link_checks( WP_Post $post, array $frontend_html ): array {
	$html    = (string) ( $frontend_html['html'] ?? '' );
	$counts  = igp_pro_seo_audit_count_links( $html );
	$inbound = igp_pro_seo_audit_count_inbound_internal_links( $post );
	$checks  = array();

	$checks[] = igp_pro_seo_audit_check(
		'internal_links',
		'links',
		$counts['internal'] > 0 ? 'pass' : 'warn',
		sprintf( __( 'Internal links found: %d.', 'igp-pro' ), $counts['internal'] ),
		sprintf( __( 'Outbound links: %d. Total links: %d.', 'igp-pro' ), $counts['external'], $counts['total'] ),
		$counts
	);

	$checks[] = igp_pro_seo_audit_check(
		'orphan_risk',
		'links',
		$inbound > 0 ? 'pass' : 'warn',
		$inbound > 0 ? sprintf( __( 'Approximate inbound internal references: %d.', 'igp-pro' ), $inbound ) : __( 'Possible orphan risk: no inbound internal references found.', 'igp-pro' ),
		__( 'This is a lightweight approximation using stored post content and relationship data where available.', 'igp-pro' ),
		array( 'inbound' => $inbound )
	);

	return $checks;
}

/**
 * Count internal/external links in HTML.
 *
 * @param string $html HTML.
 * @return array{total:int,internal:int,external:int}
 */
function igp_pro_seo_audit_count_links( string $html ): array {
	$total    = 0;
	$internal = 0;
	$external = 0;
	$home     = wp_parse_url( home_url(), PHP_URL_HOST );

	if ( preg_match_all( '/<a\b[^>]*href=["\']([^"\']+)["\']/i', $html, $matches ) ) {
		foreach ( $matches[1] as $href ) {
			$href = trim( html_entity_decode( (string) $href, ENT_QUOTES, get_bloginfo( 'charset' ) ) );
			if ( '' === $href || '#' === $href || 0 === strpos( $href, 'mailto:' ) || 0 === strpos( $href, 'tel:' ) ) {
				continue;
			}
			$total++;
			$host = wp_parse_url( $href, PHP_URL_HOST );
			if ( empty( $host ) || $host === $home ) {
				$internal++;
			} else {
				$external++;
			}
		}
	}

	return array(
		'total'    => $total,
		'internal' => $internal,
		'external' => $external,
	);
}

/**
 * Approximate inbound internal references.
 *
 * @param WP_Post $post Post.
 * @return int
 */
function igp_pro_seo_audit_count_inbound_internal_links( WP_Post $post ): int {
	global $wpdb;

	$permalink = get_permalink( $post );
	$slug      = $post->post_name;
	$count     = 0;

	if ( is_string( $permalink ) && '' !== $permalink ) {
		$count += (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE ID <> %d AND post_status = 'publish' AND post_type IN ('post','page','tour','destination') AND post_content LIKE %s",
				(int) $post->ID,
				'%' . $wpdb->esc_like( $permalink ) . '%'
			)
		);
	}

	if ( '' !== $slug ) {
		$count += (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE ID <> %d AND post_status = 'publish' AND post_type IN ('post','page','tour','destination') AND post_content LIKE %s",
				(int) $post->ID,
				'%' . $wpdb->esc_like( $slug ) . '%'
			)
		);
	}

	if ( function_exists( 'igp_pro_get_relationships' ) ) {
		$relationship_keys = array( '_igp_primary_destination_id', '_igp_destination_ids', '_igp_route_stop_ids', '_igp_related_tour_ids', '_igp_related_destination_ids' );
		foreach ( $relationship_keys as $meta_key ) {
			$count += (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s",
					$meta_key,
					'%' . $wpdb->esc_like( (string) $post->ID ) . '%'
				)
			);
		}
	}

	return max( 0, $count );
}

/**
 * Audit CWV/cache summary without triggering heavy external fetch.
 *
 * @param WP_Post $post Post.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_seo_audit_cwv_checks( WP_Post $post ): array {
	$checks   = array();
	$settings = function_exists( 'igp_pro_get_performance_settings' ) ? igp_pro_get_performance_settings() : array();
	$permalink = get_permalink( $post );
	$strategy  = 'mobile';
	$cwv       = null;

	if ( function_exists( 'igp_pro_get_cwv_settings' ) ) {
		$cwv_settings = igp_pro_get_cwv_settings();
		$strategy     = isset( $cwv_settings['default_strategy'] ) && 'desktop' === (string) $cwv_settings['default_strategy'] ? 'desktop' : 'mobile';
	}

	if ( is_string( $permalink ) && '' !== $permalink && function_exists( 'igp_pro_cwv_get_cached_report' ) ) {
		$cwv = igp_pro_cwv_get_cached_report( $permalink, $strategy );
	}

	if ( is_array( $cwv ) ) {
		$score = isset( $cwv['performance'] ) ? (int) $cwv['performance'] : 0;
		$checks[] = igp_pro_seo_audit_check(
			'cwv_summary',
			'performance',
			$score >= 75 ? 'pass' : ( $score > 0 ? 'warn' : 'info' ),
			sprintf( __( 'Cached CWV performance score: %s.', 'igp-pro' ), $score > 0 ? (string) $score : '—' ),
			sprintf( __( 'Strategy: %s. Fetched at: %s.', 'igp-pro' ), (string) ( $cwv['strategy'] ?? $strategy ), (string) ( $cwv['fetched_at'] ?? __( 'unknown', 'igp-pro' ) ) )
		);
	} else {
		$checks[] = igp_pro_seo_audit_check( 'cwv_summary', 'performance', 'info', __( 'No cached CWV report is available yet.', 'igp-pro' ), __( 'Use the Core Web Vitals / PageSpeed panel to fetch data; the audit does not call external APIs automatically.', 'igp-pro' ) );
	}

	$checks[] = igp_pro_seo_audit_check(
		'cache_summary',
		'performance',
		'yes' === (string) ( $settings['enable_block_cache'] ?? 'yes' ) ? 'pass' : 'warn',
		__( 'IGP cache settings checked.', 'igp-pro' ),
		sprintf(
			__( 'Block cache: %1$s. Page cache: %2$s. CWV cache TTL: %3$s seconds.', 'igp-pro' ),
			(string) ( $settings['enable_block_cache'] ?? 'yes' ),
			(string) ( $settings['enable_page_cache'] ?? 'yes' ),
			(string) ( $settings['cwv_cache_ttl'] ?? 43200 )
		)
	);

	return $checks;
}

/**
 * Report optional integration status without requiring external plugins.
 *
 * @return array<int,array<string,mixed>>
 */
function igp_pro_seo_audit_integration_checks(): array {
	$rank_math_active = defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) || function_exists( 'rank_math' );
	return array(
		igp_pro_seo_audit_check(
			'optional_integrations',
			'integrations',
			'info',
			$rank_math_active ? __( 'Rank Math appears active; IGP audit remains independent.', 'igp-pro' ) : __( 'Rank Math not detected; IGP audit runs without external SEO plugins.', 'igp-pro' ),
			__( 'Phase 8.4 reports audit data only and does not create duplicate Rank Math output.', 'igp-pro' )
		),
	);
}

/**
 * Produce lightweight internal-link hints from related content.
 *
 * @param WP_Post $post Post.
 * @return array<int,array<string,string>>
 */
function igp_pro_get_internal_link_hints( WP_Post $post ): array {
	if ( function_exists( 'igp_pro_generate_internal_link_opportunities' ) ) {
		$report = igp_pro_generate_internal_link_opportunities( $post->ID, array( 'limit' => 6 ) );
		if ( is_array( $report ) && ! empty( $report['opportunities'] ) ) {
			$hints = array();
			foreach ( $report['opportunities'] as $opportunity ) {
				if ( ! is_array( $opportunity ) || 'suggested' !== ( $opportunity['status'] ?? 'suggested' ) ) {
					continue;
				}
				$hints[] = array(
					'title' => (string) ( $opportunity['target_title'] ?? $opportunity['anchor'] ?? '' ),
					'url'   => (string) ( $opportunity['url'] ?? '' ),
					'type'  => (string) ( $opportunity['source'] ?? $opportunity['target_type'] ?? 'internal' ),
				);
			}
			return $hints;
		}
	}

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
		if ( '' !== $title && false === strpos( $content, $title ) && is_string( $url ) ) {
			$hints[] = array(
				'title' => get_the_title( $candidate ),
				'url'   => $url,
				'type'  => (string) get_post_type( $candidate ),
			);
		}
	}

	return $hints;
}
