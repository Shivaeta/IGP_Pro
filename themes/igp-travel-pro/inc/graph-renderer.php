<?php
/**
 * IGP Travel Pro exact visual rendering layer.
 *
 * This renderer intentionally does not call the IGP Pro block renderer for
 * frontend theme output. It reads the IGP Content Graph and maps schema fields
 * directly into the HTML grammar used by the supplied UI Block Variant Library.
 * Editor-side changes to style.variant and block fields therefore still flow
 * from IGP Pro, while final visual HTML is owned by the theme.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;

function igp_travel_pro_normalize_block_id( $block_id ): string {
	$block_id = is_string( $block_id ) ? $block_id : '';
	$block_id = strtolower( trim( $block_id ) );
	$block_id = str_replace( '_', '-', $block_id );
	return preg_replace( '/[^a-z0-9\-]/', '', $block_id ) ?: 'unknown';
}

function igp_travel_pro_get( array $array, string $path, $default = null ) {
	$current = $array;
	foreach ( explode( '.', $path ) as $part ) {
		if ( is_array( $current ) && array_key_exists( $part, $current ) ) {
			$current = $current[ $part ];
		} else {
			return $default;
		}
	}
	return $current;
}

function igp_travel_pro_text( $value, string $default = '' ): string {
	if ( is_array( $value ) || is_object( $value ) ) {
		return $default;
	}
	$value = trim( wp_strip_all_tags( (string) $value ) );
	return '' === $value ? $default : $value;
}

function igp_travel_pro_html( $value, string $default = '' ): string {
	if ( is_array( $value ) || is_object( $value ) ) {
		return $default;
	}
	$value = trim( (string) $value );
	return '' === $value ? $default : wp_kses_post( $value );
}

function igp_travel_pro_variant( array $data ): string {
	$variant = igp_travel_pro_get( $data, 'style.variant', 'default' );
	$variant = is_string( $variant ) ? strtolower( trim( $variant ) ) : 'default';
	$variant = str_replace( '_', '-', $variant );
	return preg_replace( '/[^a-z0-9\-]/', '', $variant ) ?: 'default';
}

function igp_travel_pro_style_classes( string $block_id, array $data ): string {
	$style = isset( $data['style'] ) && is_array( $data['style'] ) ? $data['style'] : array();
	$variant = igp_travel_pro_variant( $data );
	$density = isset( $style['density'] ) ? sanitize_html_class( (string) $style['density'] ) : 'comfortable';
	$theme = isset( $style['theme'] ) ? sanitize_html_class( (string) $style['theme'] ) : 'brand';
	$container = isset( $style['container'] ) ? sanitize_html_class( (string) $style['container'] ) : 'wide';
	$surface = isset( $style['surface'] ) ? sanitize_html_class( (string) $style['surface'] ) : 'default';
	$media = isset( $style['media_position'] ) ? sanitize_html_class( str_replace( '_', '-', (string) $style['media_position'] ) ) : 'auto';

	return implode(
		' ',
		array_map(
			'sanitize_html_class',
			array(
				'igp-block',
				'igp-block--' . $block_id,
				'igp-variant--' . $variant,
				'igp-density--' . $density,
				'igp-theme--' . $theme,
				'igp-container--' . $container,
				'igp-surface--' . $surface,
				'igp-media-position--' . $media,
			)
		)
	);
}

function igp_travel_pro_heading_data( array $data ): array {
	$heading = isset( $data['heading'] ) && is_array( $data['heading'] ) ? $data['heading'] : array();
	return array(
		'text'    => igp_travel_pro_text( $heading['text'] ?? '' ),
		'eyebrow' => igp_travel_pro_text( $heading['eyebrow'] ?? '' ),
		'visible' => array_key_exists( 'visible', $heading ) ? (bool) $heading['visible'] : true,
		'level'   => in_array( (string) ( $heading['level'] ?? 'h2' ), array( 'h2', 'h3', 'h4' ), true ) ? (string) $heading['level'] : 'h2',
	);
}

function igp_travel_pro_render_block_heading( array $data, string $description = '' ): string {
	$h = igp_travel_pro_heading_data( $data );
	if ( ! $h['visible'] || ( '' === $h['text'] && '' === $h['eyebrow'] && '' === $description ) ) {
		return '';
	}
	$level = tag_escape( $h['level'] );
	$html  = '<header class="block__header"><div>';
	if ( '' !== $h['eyebrow'] ) {
		$html .= '<p class="block__eyebrow kicker">' . esc_html( $h['eyebrow'] ) . '</p>';
	}
	if ( '' !== $h['text'] ) {
		$html .= '<' . $level . ' class="title-lg">' . esc_html( $h['text'] ) . '</' . $level . '>';
	}
	if ( '' !== $description ) {
		$html .= '<p class="text">' . esc_html( $description ) . '</p>';
	}
	$html .= '</div></header>';
	return $html;
}

function igp_travel_pro_render_inner_heading( array $data, string $class = 'title-lg' ): string {
	$h = igp_travel_pro_heading_data( $data );
	$html = '';
	if ( '' !== $h['eyebrow'] ) {
		$html .= '<span class="kicker">' . esc_html( $h['eyebrow'] ) . '</span>';
	}
	if ( '' !== $h['text'] ) {
		$html .= '<h3 class="' . esc_attr( $class ) . '">' . esc_html( $h['text'] ) . '</h3>';
	}
	return $html;
}

function igp_travel_pro_image_data( $image ): array {
	if ( is_numeric( $image ) && (int) $image > 0 ) {
		$url = wp_get_attachment_image_url( (int) $image, 'large' );
		$alt = get_post_meta( (int) $image, '_wp_attachment_image_alt', true );
		return array( 'url' => $url ?: '', 'alt' => igp_travel_pro_text( $alt ) );
	}
	if ( is_array( $image ) ) {
		$id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;
		$url = igp_travel_pro_text( $image['url'] ?? '' );
		if ( $id && '' === $url ) {
			$url = wp_get_attachment_image_url( $id, 'large' ) ?: '';
		}
		$alt = igp_travel_pro_text( $image['alt'] ?? '' );
		if ( $id && '' === $alt ) {
			$alt = igp_travel_pro_text( get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		}
		return array( 'url' => $url, 'alt' => $alt );
	}
	if ( is_string( $image ) ) {
		return array( 'url' => trim( $image ), 'alt' => '' );
	}
	return array( 'url' => '', 'alt' => '' );
}

function igp_travel_pro_render_media( $image, string $fallback_alt = '', string $extra_class = '' ): string {
	$img = igp_travel_pro_image_data( $image );
	$classes = trim( 'media ' . $extra_class );
	if ( '' === $img['url'] ) {
		return '<div class="' . esc_attr( $classes . ' media--placeholder' ) . '" aria-hidden="true"></div>';
	}
	$alt = '' !== $img['alt'] ? $img['alt'] : $fallback_alt;
	return '<div class="' . esc_attr( $classes ) . '"><img src="' . esc_url( $img['url'] ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async" /></div>';
}

function igp_travel_pro_actions( array $buttons, bool $dark = false ): string {
	$out = '';
	foreach ( $buttons as $i => $button ) {
		if ( ! is_array( $button ) ) {
			continue;
		}
		$label = igp_travel_pro_text( $button['label'] ?? '' );
		$url   = igp_travel_pro_text( $button['url'] ?? '' );
		if ( '' === $label ) {
			continue;
		}
		$class = 0 === $i ? 'btn' : 'btn btn-secondary';
		if ( $dark && 0 !== $i ) {
			$class .= ' btn-on-dark';
		}
		$out .= '<a class="' . esc_attr( $class ) . '" href="' . esc_url( '' === $url ? '#' : $url ) . '">' . esc_html( $label ) . '</a>';
	}
	return '' === $out ? '' : '<div class="actions">' . $out . '</div>';
}

function igp_travel_pro_badges( $badges ): string {
	if ( ! is_array( $badges ) || empty( $badges ) ) {
		return '';
	}
	$out = '';
	foreach ( $badges as $badge ) {
		$label = is_array( $badge ) ? igp_travel_pro_text( $badge['label'] ?? '' ) : igp_travel_pro_text( $badge );
		if ( '' !== $label ) {
			$out .= '<span class="chip">' . esc_html( $label ) . '</span>';
		}
	}
	return '' === $out ? '' : '<div class="badge-row">' . $out . '</div>';
}

function igp_travel_pro_li_list( $items, string $class = 'list' ): string {
	if ( ! is_array( $items ) || empty( $items ) ) {
		return '';
	}
	$out = '';
	foreach ( $items as $item ) {
		$text = is_array( $item ) ? igp_travel_pro_text( $item['text'] ?? $item['label'] ?? $item['item'] ?? $item['title'] ?? '' ) : igp_travel_pro_text( $item );
		if ( '' !== $text ) {
			$out .= '<li>' . esc_html( $text ) . '</li>';
		}
	}
	return '' === $out ? '' : '<ul class="' . esc_attr( $class ) . '">' . $out . '</ul>';
}

function igp_travel_pro_render_hero( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$sub = igp_travel_pro_text( $data['subheading'] ?? '' );
	$heading = igp_travel_pro_render_inner_heading( $data, 'title-lg' );
	$text = '' !== $sub ? '<p class="text">' . esc_html( $sub ) . '</p>' : '';
	$buttons = array();
	if ( isset( $data['cta'] ) && is_array( $data['cta'] ) ) {
		$buttons[] = $data['cta'];
	}
	if ( isset( $data['secondary_button'] ) && is_array( $data['secondary_button'] ) ) {
		$buttons[] = $data['secondary_button'];
	}
	$image = $data['background_image'] ?? array();
	$media = igp_travel_pro_render_media( $image, igp_travel_pro_get( $data, 'heading.text', '' ) );
	$actions_dark = in_array( $variant, array( 'default', 'full-width', 'centered-minimal' ), true );
	$actions = igp_travel_pro_actions( $buttons, $actions_dark );
	$search = '';
	if ( ! empty( $data['enable_search'] ) ) {
		$search = '<form class="hero-search" role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '"><input name="s" placeholder="Search tours or destinations" /><button class="btn" type="submit">' . esc_html__( 'Search', 'igp-travel-pro' ) . '</button></form>';
	}
	if ( 'image-left' === $variant ) {
		return '<section class="hero-split">' . $media . '<div class="hero-split-content">' . $heading . $text . $actions . '</div></section>';
	}
	if ( 'image-right' === $variant ) {
		return '<section class="hero-split"><div class="hero-split-content">' . $heading . $text . $actions . '</div>' . $media . '</section>';
	}
	if ( 'split-overlay' === $variant ) {
		return '<section class="hero-panel">' . $media . '<article class="hero-panel-card">' . $heading . $text . $actions . '</article></section>';
	}
	$style = 'full-width' === $variant ? ' style="min-height:660px;border-radius:0"' : '';
	$center = 'centered-minimal' === $variant ? ' center' : '';
	return '<section class="hero-bg' . esc_attr( $center ) . '"' . $style . '>' . $media . '<div class="hero-content' . esc_attr( $center ) . '">' . $heading . $text . $actions . $search . '</div></section>';
}

function igp_travel_pro_render_cta( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$sub = igp_travel_pro_text( $data['subheading'] ?? $data['description'] ?? '' );
	$head = igp_travel_pro_render_inner_heading( $data, in_array( $variant, array( 'inline', 'card' ), true ) ? 'title-sm' : 'title-lg' );
	$text = '' !== $sub ? '<p class="text">' . esc_html( $sub ) . '</p>' : '';
	$buttons = array();
	if ( isset( $data['button'] ) && is_array( $data['button'] ) ) {
		$buttons[] = $data['button'];
	}
	if ( isset( $data['secondary_button'] ) && is_array( $data['secondary_button'] ) ) {
		$buttons[] = $data['secondary_button'];
	}
	$badges = ! empty( $data['show_badges'] ) ? igp_travel_pro_badges( $data['badges'] ?? array() ) : '';
	if ( 'inline' === $variant ) {
		return '<section class="cta-inline"><div>' . $head . $text . '</div>' . igp_travel_pro_actions( $buttons ) . '</section>';
	}
	if ( 'banner' === $variant ) {
		return '<section class="cta-banner"><div>' . $head . $text . $badges . '</div>' . igp_travel_pro_actions( $buttons, true ) . '</section>';
	}
	if ( 'split' === $variant ) {
		return '<section class="cta-split"><div>' . $head . $text . igp_travel_pro_actions( $buttons ) . '</div><aside class="panel"><strong>' . esc_html__( 'Typical response', 'igp-travel-pro' ) . '</strong><p class="text">' . esc_html__( 'Within one business day. Urgent on-trip support remains separate.', 'igp-travel-pro' ) . '</p></aside></section>';
	}
	return '<section class="cta-card">' . $head . $text . $badges . igp_travel_pro_actions( $buttons ) . '</section>';
}

function igp_travel_pro_card_from_post( $post ): array {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$thumb_id = get_post_thumbnail_id( $post );
	return array(
		'title' => get_the_title( $post ),
		'excerpt' => has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 18 ),
		'url' => get_permalink( $post ),
		'image' => $thumb_id ? array( 'id' => $thumb_id ) : array(),
		'price' => get_post_meta( $post->ID, '_igp_price', true ),
		'rating' => get_post_meta( $post->ID, '_igp_rating', true ),
	);
}

function igp_travel_pro_query_items( string $post_type, int $limit ): array {
	$items = array();
	if ( ! post_type_exists( $post_type ) ) {
		return $items;
	}
	$posts = get_posts(
		array(
			'post_type' => $post_type,
			'posts_per_page' => max( 1, min( 12, $limit ) ),
			'post_status' => 'publish',
			'no_found_rows' => true,
		)
	);
	foreach ( $posts as $post ) {
		$items[] = igp_travel_pro_card_from_post( $post );
	}
	return $items;
}

function igp_travel_pro_normalize_card_items( array $data, string $post_type = 'post' ): array {
	$items = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();
	$normalized = array();
	foreach ( $items as $item ) {
		if ( is_numeric( $item ) ) {
			$item = igp_travel_pro_card_from_post( (int) $item );
		}
		if ( ! is_array( $item ) ) {
			continue;
		}
		$normalized[] = array(
			'title' => igp_travel_pro_text( $item['title'] ?? $item['name'] ?? $item['heading'] ?? '' ),
			'excerpt' => igp_travel_pro_text( $item['excerpt'] ?? $item['description'] ?? $item['text'] ?? '' ),
			'url' => igp_travel_pro_text( $item['url'] ?? $item['link'] ?? $item['permalink'] ?? '' ),
			'image' => $item['image'] ?? $item['background_image'] ?? $item['thumbnail'] ?? array(),
			'price' => igp_travel_pro_text( $item['price'] ?? '' ),
			'rating' => igp_travel_pro_text( $item['rating'] ?? '' ),
			'meta' => igp_travel_pro_text( $item['meta'] ?? $item['location'] ?? '' ),
		);
	}
	if ( empty( $normalized ) && 'query' === ( $data['source'] ?? '' ) ) {
		$normalized = igp_travel_pro_query_items( $post_type, absint( $data['limit'] ?? 6 ) ?: 6 );
	}
	if ( empty( $normalized ) ) {
		$normalized[] = array(
			'title' => __( 'Add content in IGP Pro', 'igp-travel-pro' ),
			'excerpt' => __( 'This reference-styled card will populate from query results or manual items.', 'igp-travel-pro' ),
			'url' => '#',
			'image' => array(),
			'price' => '',
			'rating' => '',
			'meta' => '',
		);
	}
	return $normalized;
}

function igp_travel_pro_render_listing_card( array $item, string $cta_label = 'View details' ): string {
	$title = igp_travel_pro_text( $item['title'] ?? '', __( 'Untitled', 'igp-travel-pro' ) );
	$excerpt = igp_travel_pro_text( $item['excerpt'] ?? '' );
	$url = igp_travel_pro_text( $item['url'] ?? '#', '#' );
	$meta = igp_travel_pro_text( $item['meta'] ?? '' );
	$price = igp_travel_pro_text( $item['price'] ?? '' );
	$rating = igp_travel_pro_text( $item['rating'] ?? '' );
	$meta_html = '';
	foreach ( array_filter( array( $meta, $price, $rating ) ) as $piece ) {
		$meta_html .= '<span>' . esc_html( $piece ) . '</span>';
	}
	return '<article class="listing-card">' . igp_travel_pro_render_media( $item['image'] ?? array(), $title ) . '<div class="listing-body"><h3 class="title-sm"><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></h3>' . ( '' !== $excerpt ? '<p class="text">' . esc_html( $excerpt ) . '</p>' : '' ) . ( '' !== $meta_html ? '<div class="listing-meta">' . $meta_html . '</div>' : '' ) . '<div class="actions"><a class="btn" href="' . esc_url( $url ) . '">' . esc_html( $cta_label ) . '</a></div></div></article>';
}

function igp_travel_pro_render_card_system( array $data, string $post_type = 'post', string $cta_label = 'View details' ): string {
	$variant = igp_travel_pro_variant( $data );
	$items = igp_travel_pro_normalize_card_items( $data, $post_type );
	$cards = '';
	foreach ( $items as $item ) {
		$cards .= igp_travel_pro_render_listing_card( $item, $cta_label );
	}
	if ( 'carousel-safe' === $variant ) {
		return '<section class="card-rail">' . $cards . '</section>';
	}
	if ( 'list' === $variant ) {
		return '<section class="result-list">' . $cards . '</section>';
	}
	if ( 'featured' === $variant ) {
		return '<section class="card-feature">' . $cards . '</section>';
	}
	$columns = absint( $data['columns'] ?? 3 );
	$class = 4 === $columns ? 'grid-4' : ( 2 === $columns ? 'grid-2' : 'grid-3' );
	return '<section class="card-grid ' . esc_attr( $class ) . '">' . $cards . '</section>';
}

function igp_travel_pro_render_gallery( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$images = isset( $data['images'] ) && is_array( $data['images'] ) ? $data['images'] : array();
	if ( empty( $images ) ) {
		$images[] = array( 'caption' => __( 'Add gallery images in IGP Pro.', 'igp-travel-pro' ), 'image' => array() );
	}
	$items = '';
	foreach ( $images as $image ) {
		$img = is_array( $image ) ? ( $image['image'] ?? $image ) : $image;
		$caption = is_array( $image ) ? igp_travel_pro_text( $image['caption'] ?? $image['alt'] ?? '' ) : '';
		$items .= '<figure class="gallery-item">' . igp_travel_pro_render_media( $img, $caption ) . ( '' !== $caption ? '<figcaption class="text">' . esc_html( $caption ) . '</figcaption>' : '' ) . '</figure>';
	}
	$class = 'gallery-default';
	if ( 'grid' === $variant ) {
		$class = 'gallery-grid';
	} elseif ( 'masonry-safe' === $variant ) {
		$class = 'gallery-masonry';
	} elseif ( 'slider-safe' === $variant ) {
		$class = 'gallery-slider';
	}
	return '<section class="' . esc_attr( $class ) . '">' . $items . '</section>';
}

function igp_travel_pro_render_rich_text( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$content = igp_travel_pro_html( $data['content'] ?? '', '<p>' . esc_html__( 'Add rich text content.', 'igp-travel-pro' ) . '</p>' );
	$heading = igp_travel_pro_render_inner_heading( $data, 'title-md' );
	if ( 'panel' === $variant ) {
		return '<article class="rich-panel">' . $heading . '<div class="rich">' . $content . '</div></article>';
	}
	if ( 'quote' === $variant ) {
		return '<article class="rich rich-quote"><blockquote>' . wp_kses_post( $content ) . '</blockquote></article>';
	}
	$class = 'lead' === $variant ? 'rich lead' : 'rich';
	return '<article class="' . esc_attr( $class ) . '">' . $heading . $content . '</article>';
}

function igp_travel_pro_render_itinerary( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$days = isset( $data['days'] ) && is_array( $data['days'] ) ? $data['days'] : array();
	if ( empty( $days ) ) {
		$days[] = array( 'day_title' => __( 'Day 1', 'igp-travel-pro' ), 'description' => __( 'Add itinerary details.', 'igp-travel-pro' ) );
	}
	$out = '';
	$i = 1;
	foreach ( $days as $day ) {
		$title = igp_travel_pro_text( $day['day_title'] ?? $day['title'] ?? sprintf( __( 'Day %d', 'igp-travel-pro' ), $i ) );
		$desc = igp_travel_pro_text( $day['description'] ?? $day['text'] ?? '' );
		$meta = array_filter( array( igp_travel_pro_text( $day['meals'] ?? '' ), igp_travel_pro_text( $day['stay'] ?? '' ) ) );
		$meta_html = '';
		foreach ( $meta as $piece ) {
			$meta_html .= '<span class="chip">' . esc_html( $piece ) . '</span>';
		}
		$out .= '<article class="day-card"><div class="day-num">D' . esc_html( (string) $i ) . '</div><div><h3 class="title-sm">' . esc_html( $title ) . '</h3>' . ( '' !== $desc ? '<p class="text">' . esc_html( $desc ) . '</p>' : '' ) . ( '' !== $meta_html ? '<div class="badge-row">' . $meta_html . '</div>' : '' ) . '</div></article>';
		$i++;
	}
	$class = in_array( $variant, array( 'cards', 'compact' ), true ) ? 'day-grid' : 'timeline';
	return '<section class="' . esc_attr( $class ) . '">' . $out . '</section>';
}

function igp_travel_pro_render_faq_like( array $data, string $block = 'faq' ): string {
	$variant = igp_travel_pro_variant( $data );
	$items = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();
	if ( empty( $items ) ) {
		$items[] = array( 'question' => __( 'Question', 'igp-travel-pro' ), 'answer' => __( 'Answer', 'igp-travel-pro' ) );
	}
	$out = '';
	foreach ( $items as $item ) {
		$q = igp_travel_pro_text( $item['question'] ?? $item['title'] ?? '' );
		$a = igp_travel_pro_html( $item['answer'] ?? $item['content'] ?? '' );
		$out .= '<details class="' . ( 'faq' === $block ? 'faq-item' : 'accordion-item' ) . '" open><summary>' . esc_html( $q ) . '</summary><p>' . wp_kses_post( $a ) . '</p></details>';
	}
	$class = 'faq' === $block ? 'faq-list' : 'accordion-list';
	if ( 'compact' === $variant ) {
		$class .= ' accordion-numbered';
	}
	if ( 'grouped' === $variant ) {
		return '<section class="grouped"><div class="panel"><h3 class="title-sm">' . esc_html__( 'Details', 'igp-travel-pro' ) . '</h3><div class="' . esc_attr( $class ) . '">' . $out . '</div></div></section>';
	}
	return '<section class="' . esc_attr( $class ) . '">' . $out . '</section>';
}

function igp_travel_pro_render_trust( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$rating = igp_travel_pro_text( $data['rating'] ?? '' );
	$source = igp_travel_pro_text( $data['source'] ?? '' );
	$head = igp_travel_pro_render_inner_heading( $data, 'title-md' );
	$items = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();
	$logos = isset( $data['logos'] ) && is_array( $data['logos'] ) ? $data['logos'] : array();
	if ( 'logo-strip' === $variant ) {
		$out = '';
		foreach ( $logos as $logo ) {
			$text = is_array( $logo ) ? igp_travel_pro_text( $logo['label'] ?? $logo['name'] ?? '' ) : igp_travel_pro_text( $logo );
			if ( '' !== $text ) {
				$out .= '<span class="logo-pill">' . esc_html( $text ) . '</span>';
			}
		}
		if ( '' === $out ) {
			$out = '<span class="logo-pill">' . esc_html__( 'Trusted Partner', 'igp-travel-pro' ) . '</span>';
		}
		return '<section class="trust-logo-row">' . $out . '</section>';
	}
	if ( 'testimonial-cards' === $variant ) {
		$out = '';
		foreach ( $items as $item ) {
			$quote = is_array( $item ) ? igp_travel_pro_text( $item['quote'] ?? $item['text'] ?? '' ) : igp_travel_pro_text( $item );
			$name = is_array( $item ) ? igp_travel_pro_text( $item['name'] ?? '' ) : '';
			$out .= '<article class="testimonial"><p class="text">' . esc_html( $quote ?: __( 'Add testimonial content.', 'igp-travel-pro' ) ) . '</p>' . ( '' !== $name ? '<strong>' . esc_html( $name ) . '</strong>' : '' ) . '</article>';
		}
		return '<section class="testimonial-grid">' . $out . '</section>';
	}
	if ( 'stats' === $variant ) {
		return '<section class="stat-band"><div><div class="metric">' . esc_html( $rating ?: '4.9' ) . '</div><p class="text">' . esc_html( $source ?: __( 'Verified guest reviews', 'igp-travel-pro' ) ) . '</p></div></section>';
	}
	return '<section class="trust-board"><div>' . $head . '</div><div><div class="metric">' . esc_html( $rating ?: '4.9/5' ) . '</div><p class="text">' . esc_html( $source ?: __( 'Verified guest reviews', 'igp-travel-pro' ) ) . '</p></div></section>';
}

function igp_travel_pro_render_pricing_summary( array $data ): string {
	$currency = igp_travel_pro_text( $data['currency'] ?? '₹', '₹' );
	$base = igp_travel_pro_text( $data['base_price'] ?? '' );
	$items = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();
	$note = igp_travel_pro_text( $data['note'] ?? '' );
	$list = igp_travel_pro_li_list( $items );
	return '<section class="summary-price"><div><span class="kicker">' . esc_html__( 'From', 'igp-travel-pro' ) . '</span><div class="price">' . esc_html( $base ? $currency . $base : $currency . ' —' ) . '</div>' . ( '' !== $note ? '<p class="text">' . esc_html( $note ) . '</p>' : '' ) . '</div><div>' . $list . '</div></section>';
}

function igp_travel_pro_render_package_tiers( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$tiers = isset( $data['tiers'] ) && is_array( $data['tiers'] ) ? $data['tiers'] : array();
	if ( empty( $tiers ) ) {
		$tiers[] = array( 'title' => __( 'Package tier', 'igp-travel-pro' ), 'price' => '—', 'duration' => '', 'features' => array( __( 'Add package features in IGP Pro.', 'igp-travel-pro' ) ) );
	}
	if ( 'comparison' === $variant ) {
		$html = '<section class="comparison"><div class="cell head">' . esc_html__( 'Feature', 'igp-travel-pro' ) . '</div>';
		foreach ( $tiers as $tier ) {
			$html .= '<div class="cell head">' . esc_html( igp_travel_pro_text( $tier['title'] ?? '' ) ) . '</div>';
		}
		$html .= '<div class="cell">' . esc_html__( 'Price', 'igp-travel-pro' ) . '</div>';
		foreach ( $tiers as $tier ) {
			$html .= '<div class="cell">' . esc_html( igp_travel_pro_text( $tier['price'] ?? '—' ) ) . '</div>';
		}
		return $html . '</section>';
	}
	$out = '';
	foreach ( $tiers as $i => $tier ) {
		$title = igp_travel_pro_text( $tier['title'] ?? $tier['name'] ?? '' );
		$price = igp_travel_pro_text( $tier['price'] ?? '' );
		$duration = igp_travel_pro_text( $tier['duration'] ?? '' );
		$features = $tier['features'] ?? array();
		$out .= '<article class="price-card' . ( 1 === $i ? ' highlight' : '' ) . '"><span class="kicker">' . esc_html( $duration ) . '</span><h3 class="title-sm">' . esc_html( $title ) . '</h3><div class="price">' . esc_html( $price ?: '—' ) . '</div>' . igp_travel_pro_li_list( $features ) . '</article>';
	}
	return '<section class="tier-cards">' . $out . '</section>';
}

function igp_travel_pro_render_map( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$address = igp_travel_pro_text( $data['address'] ?? '' );
	$embed = igp_travel_pro_text( $data['embed_url'] ?? '' );
	$link_label = igp_travel_pro_text( $data['link_label'] ?? __( 'Open map', 'igp-travel-pro' ) );
	$frame = '' !== $embed ? '<iframe class="map-frame" src="' . esc_url( $embed ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>' : '<div class="map-frame">' . esc_html__( 'Map location will appear here', 'igp-travel-pro' ) . '</div>';
	if ( 'wide' === $variant ) {
		return '<section class="map-wide">' . $frame . '</section>';
	}
	return '<section class="map-split"><div><span class="kicker">' . esc_html__( 'Location', 'igp-travel-pro' ) . '</span><h3 class="title-sm">' . esc_html( $address ?: __( 'Map location', 'igp-travel-pro' ) ) . '</h3>' . ( '' !== $address ? '<p class="text">' . esc_html( $address ) . '</p>' : '' ) . '<div class="actions"><a class="btn" href="' . esc_url( '' !== $embed ? $embed : '#' ) . '">' . esc_html( $link_label ) . '</a></div></div>' . $frame . '</section>';
}

function igp_travel_pro_render_icon_list( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$items = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();
	if ( empty( $items ) ) {
		$items = array(
			array( 'icon' => '✓', 'title' => __( 'Private guide', 'igp-travel-pro' ), 'text' => __( 'Licensed local expertise.', 'igp-travel-pro' ) ),
			array( 'icon' => '→', 'title' => __( 'Transfers', 'igp-travel-pro' ), 'text' => __( 'Door-to-door routing.', 'igp-travel-pro' ) ),
		);
	}
	$out = '';
	foreach ( $items as $item ) {
		$icon = igp_travel_pro_text( $item['icon'] ?? '✓' );
		$title = igp_travel_pro_text( $item['title'] ?? $item['label'] ?? '' );
		$text = igp_travel_pro_text( $item['text'] ?? $item['description'] ?? '' );
		if ( 'compact' === $variant ) {
			$out .= '<div class="cta-inline"><strong>' . esc_html( $title ) . '</strong><span class="chip">' . esc_html( $text ?: __( 'Included', 'igp-travel-pro' ) ) . '</span></div>';
		} else {
			$out .= '<article class="icon-card"><div class="icon-dot">' . esc_html( $icon ) . '</div><h3 class="title-sm">' . esc_html( $title ) . '</h3>' . ( '' !== $text ? '<p class="text">' . esc_html( $text ) . '</p>' : '' ) . '</article>';
		}
	}
	$class = 'compact' === $variant ? 'grid-2' : ( 'cards' === $variant ? 'grid-4' : 'icon-grid' );
	return '<section class="' . esc_attr( $class ) . '">' . $out . '</section>';
}

function igp_travel_pro_render_stats( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$items = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();
	if ( empty( $items ) ) {
		$items = array(
			array( 'value' => '250+', 'label' => __( 'Custom itineraries', 'igp-travel-pro' ) ),
			array( 'value' => '4.9', 'label' => __( 'Average rating', 'igp-travel-pro' ) ),
			array( 'value' => '24/7', 'label' => __( 'Support', 'igp-travel-pro' ) ),
		);
	}
	$out = '';
	foreach ( $items as $item ) {
		$value = igp_travel_pro_text( $item['value'] ?? $item['number'] ?? '' );
		$label = igp_travel_pro_text( $item['label'] ?? $item['description'] ?? '' );
		$out .= '<div class="stat-card"><div class="metric">' . esc_html( $value ) . '</div><p class="text">' . esc_html( $label ) . '</p></div>';
	}
	$class = 'strip' === $variant ? 'stat-band' : 'grid-4';
	return '<section class="' . esc_attr( $class ) . '">' . $out . '</section>';
}

function igp_travel_pro_render_tour_facts( array $data ): string {
	$facts = isset( $data['facts'] ) && is_array( $data['facts'] ) ? $data['facts'] : array();
	if ( empty( $facts ) ) {
		$facts[] = array( 'label' => __( 'Duration', 'igp-travel-pro' ), 'value' => __( 'Flexible', 'igp-travel-pro' ) );
	}
	$out = '';
	foreach ( $facts as $fact ) {
		$label = igp_travel_pro_text( $fact['label'] ?? $fact['title'] ?? '' );
		$value = igp_travel_pro_text( $fact['value'] ?? $fact['text'] ?? '' );
		$out .= '<article class="fact"><span class="fact-label">' . esc_html( $label ) . '</span><strong class="fact-value">' . esc_html( $value ) . '</strong></article>';
	}
	return '<section class="fact-grid">' . $out . '</section>';
}

function igp_travel_pro_render_inclusions_exclusions( array $data ): string {
	$inc = $data['inclusions'] ?? array();
	$exc = $data['exclusions'] ?? array();
	if ( empty( $inc ) ) {
		$inc = array( __( 'Add inclusions in IGP Pro.', 'igp-travel-pro' ) );
	}
	return '<section class="inclusion-grid"><article class="panel"><h3 class="title-sm">' . esc_html__( 'Included', 'igp-travel-pro' ) . '</h3>' . igp_travel_pro_li_list( $inc ) . '</article><article class="panel"><h3 class="title-sm">' . esc_html__( 'Not included', 'igp-travel-pro' ) . '</h3>' . igp_travel_pro_li_list( $exc, 'list minus' ) . '</article></section>';
}

function igp_travel_pro_render_route_timeline( array $data ): string {
	$stops = isset( $data['stops'] ) && is_array( $data['stops'] ) ? $data['stops'] : array();
	if ( empty( $stops ) ) {
		$stops[] = array( 'title' => __( 'Start', 'igp-travel-pro' ), 'description' => __( 'Add route stops in IGP Pro.', 'igp-travel-pro' ) );
	}
	$out = '';
	foreach ( $stops as $stop ) {
		$out .= '<article class="route-stop"><h3 class="title-sm">' . esc_html( igp_travel_pro_text( $stop['title'] ?? $stop['name'] ?? '' ) ) . '</h3><p class="text">' . esc_html( igp_travel_pro_text( $stop['description'] ?? $stop['text'] ?? '' ) ) . '</p></article>';
	}
	return '<section class="route-line">' . $out . '</section>';
}

function igp_travel_pro_render_best_time( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$summary = igp_travel_pro_text( $data['weather_summary'] ?? $data['intro'] ?? '' );
	$seasons = isset( $data['seasons'] ) && is_array( $data['seasons'] ) ? $data['seasons'] : array();
	if ( empty( $seasons ) ) {
		$seasons[] = array( 'title' => __( 'Best season', 'igp-travel-pro' ), 'description' => $summary ?: __( 'Add seasonal guidance in IGP Pro.', 'igp-travel-pro' ) );
	}
	$out = '';
	foreach ( $seasons as $season ) {
		$out .= '<article class="igp-pro-season-card panel"><h3 class="title-sm">' . esc_html( igp_travel_pro_text( $season['title'] ?? $season['season'] ?? '' ) ) . '</h3><p class="text">' . esc_html( igp_travel_pro_text( $season['description'] ?? $season['summary'] ?? '' ) ) . '</p></article>';
	}
	return '<section class="' . ( 'compact' === $variant ? 'season-cards' : 'season-layout' ) . '">' . $out . '</section>';
}

function igp_travel_pro_render_reviews_summary( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$avg = igp_travel_pro_text( $data['average_rating'] ?? '4.9' );
	$count = igp_travel_pro_text( $data['review_count'] ?? '' );
	$summary = igp_travel_pro_text( $data['summary'] ?? '' );
	$testimonials = isset( $data['testimonials'] ) && is_array( $data['testimonials'] ) ? $data['testimonials'] : array();
	$out = '<article class="panel"><div class="metric">' . esc_html( $avg ) . '</div><p class="text">' . esc_html( $count ? $count . ' reviews' : __( 'Verified guest reviews', 'igp-travel-pro' ) ) . '</p>' . ( '' !== $summary ? '<p class="text">' . esc_html( $summary ) . '</p>' : '' ) . '</article>';
	foreach ( $testimonials as $t ) {
		$out .= '<article class="testimonial"><p class="text">' . esc_html( igp_travel_pro_text( $t['quote'] ?? $t['text'] ?? '' ) ) . '</p></article>';
	}
	return '<section class="' . ( 'cards' === $variant ? 'review-cards' : 'review-dashboard' ) . '">' . $out . '</section>';
}

function igp_travel_pro_render_expert_box( array $data ): string {
	$name = igp_travel_pro_text( $data['name'] ?? __( 'Travel expert', 'igp-travel-pro' ) );
	$role = igp_travel_pro_text( $data['role'] ?? '' );
	$bio = igp_travel_pro_text( $data['bio'] ?? '' );
	$img = $data['image'] ?? array();
	$buttons = array( array( 'label' => igp_travel_pro_text( $data['cta_label'] ?? __( 'Ask expert', 'igp-travel-pro' ) ), 'url' => igp_travel_pro_text( $data['cta_url'] ?? '#' ) ) );
	return '<section class="expert-card">' . igp_travel_pro_render_media( $img, $name, 'avatar' ) . '<div><h3 class="title-sm">' . esc_html( $name ) . '</h3>' . ( '' !== $role ? '<span class="kicker">' . esc_html( $role ) . '</span>' : '' ) . ( '' !== $bio ? '<p class="text">' . esc_html( $bio ) . '</p>' : '' ) . igp_travel_pro_actions( $buttons ) . '</div></section>';
}

function igp_travel_pro_render_nearby_attractions( array $data ): string {
	$items = igp_travel_pro_normalize_card_items( $data, 'destination' );
	$out = '';
	foreach ( $items as $item ) {
		$out .= '<article class="nearby-mini"><h3 class="title-sm">' . esc_html( igp_travel_pro_text( $item['title'] ?? '' ) ) . '</h3><p class="text">' . esc_html( igp_travel_pro_text( $item['excerpt'] ?? '' ) ) . '</p></article>';
	}
	return '<section class="nearby-list">' . $out . '</section>';
}

function igp_travel_pro_render_departure_dates( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$dates = isset( $data['dates'] ) && is_array( $data['dates'] ) ? $data['dates'] : array();
	if ( empty( $dates ) ) {
		$dates[] = array( 'date' => __( 'Flexible dates', 'igp-travel-pro' ), 'status' => __( 'Available', 'igp-travel-pro' ), 'price' => '' );
	}
	if ( 'cards' === $variant || 'compact' === $variant ) {
		$out = '';
		foreach ( $dates as $row ) {
			$out .= '<article class="panel"><span class="chip">' . esc_html( igp_travel_pro_text( $row['status'] ?? '' ) ) . '</span><h3 class="title-sm">' . esc_html( igp_travel_pro_text( $row['date'] ?? $row['start_date'] ?? '' ) ) . '</h3><p class="text">' . esc_html( igp_travel_pro_text( $row['price'] ?? '' ) ) . '</p></article>';
		}
		return '<section class="date-cards">' . $out . '</section>';
	}
	$out = '<div class="date-row date-head"><div class="date-cell">' . esc_html__( 'Date', 'igp-travel-pro' ) . '</div><div class="date-cell">' . esc_html__( 'Status', 'igp-travel-pro' ) . '</div><div class="date-cell">' . esc_html__( 'Price', 'igp-travel-pro' ) . '</div><div class="date-cell">' . esc_html__( 'Seats', 'igp-travel-pro' ) . '</div><div class="date-cell">' . esc_html__( 'Action', 'igp-travel-pro' ) . '</div></div>';
	foreach ( $dates as $row ) {
		$out .= '<div class="date-row"><div class="date-cell">' . esc_html( igp_travel_pro_text( $row['date'] ?? $row['start_date'] ?? '' ) ) . '</div><div class="date-cell"><span class="chip">' . esc_html( igp_travel_pro_text( $row['status'] ?? '' ) ) . '</span></div><div class="date-cell">' . esc_html( igp_travel_pro_text( $row['price'] ?? '' ) ) . '</div><div class="date-cell">' . esc_html( igp_travel_pro_text( $row['seats'] ?? '' ) ) . '</div><div class="date-cell"><a class="btn" href="#">' . esc_html__( 'Enquire', 'igp-travel-pro' ) . '</a></div></div>';
	}
	return '<section class="date-table">' . $out . '</section>';
}

function igp_travel_pro_render_visa_requirements( array $data ): string {
	$requirements = isset( $data['requirements'] ) && is_array( $data['requirements'] ) ? $data['requirements'] : array();
	$documents = isset( $data['documents'] ) && is_array( $data['documents'] ) ? $data['documents'] : array();
	if ( empty( $requirements ) ) {
		$requirements = array( __( 'Add visa requirements in IGP Pro.', 'igp-travel-pro' ) );
	}
	return '<section class="visa-grid"><article class="panel"><h3 class="title-sm">' . esc_html__( 'Requirements', 'igp-travel-pro' ) . '</h3>' . igp_travel_pro_li_list( $requirements ) . '</article><article class="panel"><h3 class="title-sm">' . esc_html__( 'Documents', 'igp-travel-pro' ) . '</h3>' . igp_travel_pro_li_list( $documents ) . '</article></section>';
}

function igp_travel_pro_render_brochure_cta( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$description = igp_travel_pro_text( $data['description'] ?? '' );
	$button = array( 'label' => igp_travel_pro_text( $data['button_label'] ?? __( 'Download brochure', 'igp-travel-pro' ) ), 'url' => igp_travel_pro_text( $data['file_url'] ?? '#' ) );
	$head = igp_travel_pro_render_inner_heading( $data, 'title-md' );
	$body = '<div class="doc-icon">PDF</div><div>' . $head . ( '' !== $description ? '<p class="text">' . esc_html( $description ) . '</p>' : '' ) . '</div>' . igp_travel_pro_actions( array( $button ), true );
	if ( 'inline' === $variant ) {
		return '<section class="brochure-inline">' . $body . '</section>';
	}
	if ( 'card' === $variant ) {
		return '<section class="brochure-card">' . $body . '</section>';
	}
	return '<section class="brochure-banner">' . $body . '</section>';
}

function igp_travel_pro_render_sticky_booking_cta( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$title = igp_travel_pro_text( $data['cta_title'] ?? igp_travel_pro_get( $data, 'heading.text', __( 'Book this trip', 'igp-travel-pro' ) ) );
	$desc = igp_travel_pro_text( $data['description'] ?? '' );
	$price = igp_travel_pro_text( $data['price_from'] ?? '' );
	$currency = igp_travel_pro_text( $data['currency'] ?? '₹' );
	$buttons = array(
		array( 'label' => igp_travel_pro_text( $data['primary_label'] ?? __( 'Send enquiry', 'igp-travel-pro' ) ), 'url' => igp_travel_pro_text( $data['primary_url'] ?? '#' ) ),
		array( 'label' => igp_travel_pro_text( $data['secondary_label'] ?? '' ), 'url' => igp_travel_pro_text( $data['secondary_url'] ?? '#' ) ),
	);
	$content = '<div><span class="kicker">' . esc_html( $price ? __( 'From', 'igp-travel-pro' ) . ' ' . $currency . $price : __( 'Booking', 'igp-travel-pro' ) ) . '</span><h3 class="title-sm">' . esc_html( $title ) . '</h3>' . ( '' !== $desc ? '<p class="text">' . esc_html( $desc ) . '</p>' : '' ) . '</div>' . igp_travel_pro_actions( $buttons, true );
	if ( 'bottom-bar' === $variant ) {
		return '<section class="bottom-bar">' . $content . '</section>';
	}
	if ( 'side-card' === $variant ) {
		return '<section class="sticky-side"><div class="panel"><p class="text">' . esc_html( $desc ) . '</p></div><aside class="sticky-card">' . $content . '</aside></section>';
	}
	return '<section class="cta-inline">' . $content . '</section>';
}

function igp_travel_pro_render_breadcrumb( array $data ): string {
	$items = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();
	if ( empty( $items ) ) {
		$items = array( array( 'label' => __( 'Home', 'igp-travel-pro' ), 'url' => home_url( '/' ) ), array( 'label' => get_the_title(), 'url' => '' ) );
	}
	$out = '';
	foreach ( $items as $item ) {
		$label = is_array( $item ) ? igp_travel_pro_text( $item['label'] ?? $item['title'] ?? '' ) : igp_travel_pro_text( $item );
		$url = is_array( $item ) ? igp_travel_pro_text( $item['url'] ?? '' ) : '';
		$out .= '<li>' . ( '' !== $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>' : esc_html( $label ) ) . '</li>';
	}
	return '<ol class="breadcrumb' . ( 'compact' === igp_travel_pro_variant( $data ) ? ' compact' : '' ) . '">' . $out . '</ol>';
}

function igp_travel_pro_render_tabs( array $data ): string {
	$variant = igp_travel_pro_variant( $data );
	$items = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();
	if ( empty( $items ) ) {
		$items = array( array( 'tab_title' => __( 'Overview', 'igp-travel-pro' ), 'content' => __( 'Add tab content in IGP Pro.', 'igp-travel-pro' ) ) );
	}
	$nav = '';
	$panels = '';
	foreach ( $items as $i => $item ) {
		$title = igp_travel_pro_text( $item['tab_title'] ?? $item['title'] ?? '' );
		$content = igp_travel_pro_html( $item['content'] ?? '' );
		$nav .= '<button class="tab-btn' . ( 0 === $i ? ' active' : '' ) . '" type="button">' . esc_html( $title ) . '</button>';
		if ( 0 === $i ) {
			$panels .= '<article class="tab-panel"><h3 class="title-sm">' . esc_html( $title ) . '</h3><p class="text">' . wp_kses_post( $content ) . '</p></article>';
		}
	}
	$nav_class = in_array( $variant, array( 'pills', 'underline', 'boxed' ), true ) ? 'tab-nav pills' : 'tab-nav';
	$section_class = 'boxed' === $variant ? 'panel' : ( 'default' === $variant ? 'tabs-vertical' : 'tabs-pills' );
	return '<section class="' . esc_attr( $section_class ) . '"><nav class="' . esc_attr( $nav_class ) . '">' . $nav . '</nav>' . $panels . '</section>';
}

function igp_travel_pro_render_section_wrapper( array $section, array $context ): string {
	$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
	$variant = igp_travel_pro_variant( $data );
	$desc = igp_travel_pro_text( $data['description'] ?? '' );
	$children = isset( $section['children'] ) && is_array( $section['children'] ) ? $section['children'] : array();
	$content = '';
	foreach ( $children as $child ) {
		$content .= igp_travel_pro_render_section( $child, $context, true );
	}
	if ( '' === $content ) {
		$content = '<div class="panel"><p class="text">' . esc_html( $desc ?: __( 'Add nested blocks in IGP Pro.', 'igp-travel-pro' ) ) . '</p></div>';
	}
	$head = igp_travel_pro_render_inner_heading( $data, 'title-lg' );
	if ( 'band' === $variant ) {
		return '<section class="section-band">' . $head . ( '' !== $desc ? '<p class="text">' . esc_html( $desc ) . '</p>' : '' ) . '<div class="section-grid">' . $content . '</div></section>';
	}
	if ( 'split' === $variant ) {
		return '<section class="section-split"><aside class="section-split-intro">' . $head . ( '' !== $desc ? '<p class="text">' . esc_html( $desc ) . '</p>' : '' ) . '</aside><div>' . $content . '</div></section>';
	}
	return '<section class="section-grid">' . $content . '</section>';
}

function igp_travel_pro_render_block_body( string $block_id, array $section, array $context ): string {
	$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
	switch ( $block_id ) {
		case 'hero': return igp_travel_pro_render_hero( $data );
		case 'cta': return igp_travel_pro_render_cta( $data );
		case 'rich-text': return igp_travel_pro_render_rich_text( $data );
		case 'section-wrapper':
		case 'section': return igp_travel_pro_render_section_wrapper( $section, $context );
		case 'trust': return igp_travel_pro_render_trust( $data );
		case 'brochure-cta': return igp_travel_pro_render_brochure_cta( $data );
		case 'tour-cards': return igp_travel_pro_render_card_system( $data, 'tour', igp_travel_pro_text( $data['cta_label'] ?? __( 'Book a tour', 'igp-travel-pro' ) ) );
		case 'destination-cards': return igp_travel_pro_render_card_system( $data, 'destination', __( 'Explore', 'igp-travel-pro' ) );
		case 'featured-listings': return igp_travel_pro_render_card_system( $data, igp_travel_pro_text( $data['post_type'] ?? 'tour' ), __( 'View deal', 'igp-travel-pro' ) );
		case 'related-tours': return igp_travel_pro_render_card_system( $data, 'tour', __( 'View tour', 'igp-travel-pro' ) );
		case 'related-destinations': return igp_travel_pro_render_card_system( $data, 'destination', __( 'View destination', 'igp-travel-pro' ) );
		case 'gallery': return igp_travel_pro_render_gallery( $data );
		case 'nearby-attractions': return igp_travel_pro_render_nearby_attractions( $data );
		case 'expert-box': return igp_travel_pro_render_expert_box( $data );
		case 'reviews-summary': return igp_travel_pro_render_reviews_summary( $data );
		case 'best-time-to-visit': return igp_travel_pro_render_best_time( $data );
		case 'itinerary': return igp_travel_pro_render_itinerary( $data );
		case 'route-timeline': return igp_travel_pro_render_route_timeline( $data );
		case 'tour-facts': return igp_travel_pro_render_tour_facts( $data );
		case 'inclusions-exclusions': return igp_travel_pro_render_inclusions_exclusions( $data );
		case 'visa-requirements': return igp_travel_pro_render_visa_requirements( $data );
		case 'departure-dates': return igp_travel_pro_render_departure_dates( $data );
		case 'package-tiers': return igp_travel_pro_render_package_tiers( $data );
		case 'pricing-summary': return igp_travel_pro_render_pricing_summary( $data );
		case 'sticky-booking-cta': return igp_travel_pro_render_sticky_booking_cta( $data );
		case 'map': return igp_travel_pro_render_map( $data );
		case 'breadcrumb': return igp_travel_pro_render_breadcrumb( $data );
		case 'faq': return igp_travel_pro_render_faq_like( $data, 'faq' );
		case 'accordions': return igp_travel_pro_render_faq_like( $data, 'accordions' );
		case 'tabs': return igp_travel_pro_render_tabs( $data );
		case 'icon-list': return igp_travel_pro_render_icon_list( $data );
		case 'stats': return igp_travel_pro_render_stats( $data );
		default:
			return '<section class="panel"><p class="text">' . esc_html( sprintf( __( 'Unsupported IGP Travel Pro block: %s', 'igp-travel-pro' ), $block_id ) ) . '</p></section>';
	}
}

function igp_travel_pro_render_section( array $section, array $context = array(), bool $nested = false ): string {
	$raw_block = $section['block_id'] ?? $section['block'] ?? '';
	$block_id = igp_travel_pro_normalize_block_id( $raw_block );
	$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
	$variant = igp_travel_pro_variant( $data );
	$section_id = igp_travel_pro_text( $section['id'] ?? '' );
	$description = igp_travel_pro_text( $data['description'] ?? $data['intro'] ?? '' );
	$classes = 'block ' . igp_travel_pro_style_classes( $block_id, $data );
	$body = igp_travel_pro_render_block_body( $block_id, $section, $context );
	$heading = in_array( $block_id, array( 'hero', 'breadcrumb' ), true ) ? '' : igp_travel_pro_render_block_heading( $data, $description );
	if ( $nested ) {
		return $body;
	}
	return '<section class="' . esc_attr( $classes ) . '" id="' . esc_attr( $section_id ) . '" data-block="' . esc_attr( $block_id ) . '" data-variant="' . esc_attr( $variant ) . '">' . $heading . '<div class="variant-stack"><article class="variant variant--' . esc_attr( $variant ) . '" data-block="' . esc_attr( $block_id ) . '" data-variant="' . esc_attr( $variant ) . '"><div class="variant__body">' . $body . '</div></article></div></section>';
}

function igp_travel_pro_render_exact_graph( array $graph, array $context = array() ): string {
	$sections = isset( $graph['sections'] ) && is_array( $graph['sections'] ) ? $graph['sections'] : array();
	if ( empty( $sections ) ) {
		return '';
	}
	$out = '<div class="igp-travel-pro-render" data-renderer="theme-exact-reference">';
	foreach ( $sections as $section ) {
		if ( is_array( $section ) ) {
			$out .= igp_travel_pro_render_section( $section, $context );
		}
	}
	$out .= '</div>';
	return $out;
}
