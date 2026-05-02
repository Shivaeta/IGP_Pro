<?php
/**
 * Shared helpers for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

function igp_pro_path( string $relative_path = '' ): string {
	return IGP_PRO_PATH . ltrim( $relative_path, '/\\' );
}

function igp_pro_url( string $relative_path = '' ): string {
	return IGP_PRO_URL . ltrim( $relative_path, '/\\' );
}

function igp_pro_json_decode_array( string $json ) {
	$data = json_decode( $json, true );

	if ( JSON_ERROR_NONE !== json_last_error() ) {
		return new WP_Error(
			'igp_pro_invalid_json',
			sprintf(
				/* translators: %s: JSON parser error. */
				__( 'Invalid JSON: %s', 'igp-pro' ),
				json_last_error_msg()
			)
		);
	}

	if ( ! is_array( $data ) ) {
		return new WP_Error( 'igp_pro_invalid_json_shape', __( 'JSON payload must decode to an object or array.', 'igp-pro' ) );
	}

	return $data;
}

function igp_pro_to_string( $value ): string {
	if ( is_scalar( $value ) ) {
		return (string) $value;
	}

	return '';
}

function igp_pro_normalize_block_id( string $block_id ) {
	$block_id = sanitize_key( $block_id );

	if ( '' === $block_id ) {
		return new WP_Error( 'igp_pro_empty_block_id', __( 'Block ID cannot be empty.', 'igp-pro' ) );
	}

	return $block_id;
}

function igp_pro_block_id_to_wp_slug( string $block_id ): string {
	return str_replace( '_', '-', sanitize_key( $block_id ) );
}

function igp_pro_block_id_to_title( string $block_id ): string {
	return 'IGP ' . ucwords( str_replace( array( '-', '_' ), ' ', sanitize_key( $block_id ) ) );
}

function igp_pro_get_image_url( $image ): string {
	if ( is_array( $image ) && isset( $image['url'] ) ) {
		return esc_url_raw( igp_pro_to_string( $image['url'] ) );
	}

	return esc_url_raw( igp_pro_to_string( $image ) );
}

function igp_pro_get_image_alt( $image, string $fallback = '' ): string {
	if ( is_array( $image ) && isset( $image['alt'] ) ) {
		return sanitize_text_field( igp_pro_to_string( $image['alt'] ) );
	}

	return sanitize_text_field( $fallback );
}

/**
 * Normalize repeater/list values. Accepts arrays, JSON strings, and newline lists.
 *
 * @param mixed $items Raw value.
 * @return array
 */
function igp_pro_normalize_list( $items ): array {
	if ( is_string( $items ) ) {
		$decoded = json_decode( $items, true );
		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			$items = $decoded;
		} else {
			$items = preg_split( '/\r\n|\r|\n/', $items );
		}
	}

	if ( ! is_array( $items ) ) {
		return array();
	}

	$normalized = array();

	foreach ( $items as $item ) {
		if ( is_array( $item ) ) {
			$normalized[] = $item;
		} elseif ( is_scalar( $item ) && '' !== trim( (string) $item ) ) {
			$normalized[] = array( 'value' => trim( (string) $item ) );
		}
	}

	return $normalized;
}

/**
 * Normalize relationship values into integer post IDs.
 * Accepts comma/space/newline-separated strings, numeric arrays, Gutenberg arrays, and {id: n} arrays.
 *
 * @param mixed $value Raw relationship value.
 * @return int[]
 */
function igp_pro_normalize_post_ids( $value ): array {
	if ( is_string( $value ) ) {
		$decoded = json_decode( $value, true );
		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			$value = $decoded;
		} else {
			$value = preg_split( '/[^0-9]+/', $value );
		}
	}

	if ( is_numeric( $value ) ) {
		$value = array( $value );
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$ids = array();

	foreach ( $value as $item ) {
		if ( is_array( $item ) ) {
			if ( isset( $item['id'] ) ) {
				$item = $item['id'];
			} elseif ( isset( $item['ID'] ) ) {
				$item = $item['ID'];
			} else {
				foreach ( $item as $nested ) {
					if ( is_numeric( $nested ) ) {
						$ids[] = absint( $nested );
					}
				}
				continue;
			}
		}

		if ( is_string( $item ) && false !== strpos( $item, ',' ) ) {
			foreach ( preg_split( '/[^0-9]+/', $item ) as $nested ) {
				if ( is_numeric( $nested ) ) {
					$ids[] = absint( $nested );
				}
			}
			continue;
		}

		$id = absint( $item );
		if ( $id > 0 ) {
			$ids[] = $id;
		}
	}

	return array_values( array_unique( array_filter( $ids ) ) );
}

function igp_pro_int_range( $value, int $default, int $min, int $max ): int {
	$value = is_numeric( $value ) ? (int) $value : $default;
	return max( $min, min( $max, $value ) );
}

function igp_pro_enum( $value, array $allowed, string $default ): string {
	$value = sanitize_key( igp_pro_to_string( $value ) );
	return in_array( $value, $allowed, true ) ? $value : $default;
}

function igp_pro_kses_content( $value ): string {
	return wp_kses_post( igp_pro_to_string( $value ) );
}

function igp_pro_get_post_meta_first( int $post_id, array $keys, string $default = '' ): string {
	foreach ( $keys as $key ) {
		$value = get_post_meta( $post_id, $key, true );
		if ( '' !== igp_pro_to_string( $value ) ) {
			return igp_pro_to_string( $value );
		}
	}

	return $default;
}

function igp_pro_get_post_terms_label( WP_Post $post, array $taxonomies ): string {
	foreach ( $taxonomies as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$terms = get_the_terms( $post, $taxonomy );
		if ( is_array( $terms ) && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			return $terms[0]->name;
		}
	}

	return '';
}

function igp_pro_get_post_image_url( WP_Post $post, string $size = 'large' ): string {
	$url = get_the_post_thumbnail_url( $post, $size );
	return $url ? esc_url_raw( $url ) : '';
}

function igp_pro_get_post_card_data( WP_Post $post ): array {
	$post_type   = get_post_type( $post );
	$is_tour     = 'tour' === $post_type;
	$taxonomies  = $is_tour ? array( 'travel_region', 'tour_category', 'destination_region' ) : array( 'travel_region', 'destination_region' );
	$location    = igp_pro_get_post_meta_first( $post->ID, array( '_igp_location', 'igp_location', '_location', 'location' ) );
	$destination = igp_pro_get_post_meta_first( $post->ID, array( '_igp_destination', 'igp_destination', '_destination', 'destination' ) );

	if ( '' === $location ) {
		$location = '' !== $destination ? $destination : igp_pro_get_post_terms_label( $post, $taxonomies );
	}

	return array(
		'type'          => $post_type,
		'url'           => get_permalink( $post ),
		'title'         => get_the_title( $post ),
		'excerpt'       => has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 22 ),
		'image'         => igp_pro_get_post_image_url( $post ),
		'location'      => $location,
		'duration'      => igp_pro_get_post_meta_first( $post->ID, array( '_igp_duration', 'igp_duration', '_duration', 'duration' ) ),
		'group_size'    => igp_pro_get_post_meta_first( $post->ID, array( '_igp_group_size', 'igp_group_size', '_group_size', 'group_size' ) ),
		'price'         => igp_pro_get_post_meta_first( $post->ID, array( '_igp_price', 'igp_price', '_price', 'price' ) ),
		'regular_price' => igp_pro_get_post_meta_first( $post->ID, array( '_igp_regular_price', 'igp_regular_price', '_regular_price', 'regular_price' ) ),
		'rating'        => igp_pro_get_post_meta_first( $post->ID, array( '_igp_rating', 'igp_rating', '_rating', 'rating' ) ),
		'review_count'  => igp_pro_get_post_meta_first( $post->ID, array( '_igp_review_count', 'igp_review_count', '_review_count', 'review_count' ) ),
		'badge'         => igp_pro_get_post_meta_first( $post->ID, array( '_igp_badge', 'igp_badge', '_badge', 'badge' ) ),
	);
}

/**
 * Build a post query for listing blocks.
 *
 * @param string|array $post_type Post type(s).
 * @param int          $limit     Limit.
 * @param array        $ids       Optional IDs.
 * @param array        $extra     Extra args.
 * @return WP_Query
 */
function igp_pro_get_listing_query( $post_type, int $limit = 6, array $ids = array(), array $extra = array() ): WP_Query {
	$limit = max( 1, min( 24, $limit ) );
	$ids   = igp_pro_normalize_post_ids( $ids );

	$args = array_merge(
		array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		),
		$extra
	);

	if ( ! empty( $ids ) ) {
		$args['post__in'] = $ids;
		$args['orderby']  = 'post__in';
	}

	return new WP_Query( $args );
}

function igp_pro_render_post_card( WP_Post $post, array $settings = array() ): string {
	$settings = wp_parse_args(
		$settings,
		array(
			'show_excerpt'     => true,
			'show_price'       => false,
			'show_rating'      => false,
			'show_meta'        => true,
			'show_location'    => true,
			'show_duration'    => true,
			'show_group_size'  => true,
			'cta_label'        => __( 'View details', 'igp-pro' ),
			'image_ratio'      => 'landscape',
			'card_style'       => 'elevated',
			'fallback_badge'   => '',
			'compact'          => false,
		)
	);

	$data        = igp_pro_get_post_card_data( $post );
	$image_ratio = igp_pro_enum( $settings['image_ratio'], array( 'landscape', 'portrait', 'square' ), 'landscape' );
	$card_style  = igp_pro_enum( $settings['card_style'], array( 'elevated', 'bordered', 'overlay', 'compact' ), 'elevated' );
	$badge       = '' !== $data['badge'] ? $data['badge'] : igp_pro_to_string( $settings['fallback_badge'] );
	$rating      = trim( $data['rating'] );
	$reviews     = trim( $data['review_count'] );

	ob_start();
	?>
	<article class="igp-pro-card igp-pro-card--<?php echo esc_attr( $data['type'] ); ?> igp-pro-card--<?php echo esc_attr( $card_style ); ?> igp-pro-card--ratio-<?php echo esc_attr( $image_ratio ); ?>">
		<a class="igp-pro-card__media-link" href="<?php echo esc_url( $data['url'] ); ?>" aria-label="<?php echo esc_attr( $data['title'] ); ?>">
			<figure class="igp-pro-card__media">
				<?php if ( '' !== $data['image'] ) : ?>
					<img class="igp-pro-card__image" src="<?php echo esc_url( $data['image'] ); ?>" alt="<?php echo esc_attr( $data['title'] ); ?>" loading="lazy" decoding="async">
				<?php else : ?>
					<span class="igp-pro-card__image igp-pro-card__image--placeholder" aria-hidden="true"></span>
				<?php endif; ?>
				<?php if ( '' !== $badge ) : ?>
					<span class="igp-pro-card__badge"><?php echo esc_html( $badge ); ?></span>
				<?php endif; ?>
			</figure>
		</a>

		<div class="igp-pro-card__body">
			<?php if ( (bool) $settings['show_rating'] && '' !== $rating ) : ?>
				<div class="igp-pro-card__rating" aria-label="<?php echo esc_attr( sprintf( __( 'Rating %s', 'igp-pro' ), $rating ) ); ?>">
					<span aria-hidden="true">★</span>
					<strong><?php echo esc_html( $rating ); ?></strong>
					<?php if ( '' !== $reviews ) : ?><span><?php echo esc_html( sprintf( _n( '(%s review)', '(%s reviews)', absint( $reviews ), 'igp-pro' ), $reviews ) ); ?></span><?php endif; ?>
				</div>
			<?php endif; ?>

			<h3 class="igp-pro-card__title"><a href="<?php echo esc_url( $data['url'] ); ?>"><?php echo esc_html( $data['title'] ); ?></a></h3>

			<?php if ( (bool) $settings['show_location'] && '' !== $data['location'] ) : ?>
				<p class="igp-pro-card__location"><span aria-hidden="true">⌖</span><?php echo esc_html( $data['location'] ); ?></p>
			<?php endif; ?>

			<?php if ( (bool) $settings['show_excerpt'] && '' !== $data['excerpt'] ) : ?>
				<p class="igp-pro-card__excerpt"><?php echo esc_html( wp_trim_words( $data['excerpt'], 18 ) ); ?></p>
			<?php endif; ?>

			<?php if ( (bool) $settings['show_meta'] && ( '' !== $data['duration'] || '' !== $data['group_size'] ) ) : ?>
				<ul class="igp-pro-card__meta" aria-label="<?php esc_attr_e( 'Tour facts', 'igp-pro' ); ?>">
					<?php if ( (bool) $settings['show_duration'] && '' !== $data['duration'] ) : ?><li><span aria-hidden="true">⏱</span><?php echo esc_html( $data['duration'] ); ?></li><?php endif; ?>
					<?php if ( (bool) $settings['show_group_size'] && '' !== $data['group_size'] ) : ?><li><span aria-hidden="true">👥</span><?php echo esc_html( $data['group_size'] ); ?></li><?php endif; ?>
				</ul>
			<?php endif; ?>

			<div class="igp-pro-card__footer">
				<?php if ( (bool) $settings['show_price'] && '' !== $data['price'] ) : ?>
					<p class="igp-pro-card__price">
						<span><?php esc_html_e( 'From', 'igp-pro' ); ?></span>
						<?php if ( '' !== $data['regular_price'] ) : ?><del><?php echo esc_html( $data['regular_price'] ); ?></del><?php endif; ?>
						<strong><?php echo esc_html( $data['price'] ); ?></strong>
					</p>
				<?php endif; ?>
				<a class="igp-pro-card__cta" href="<?php echo esc_url( $data['url'] ); ?>"><?php echo esc_html( igp_pro_to_string( $settings['cta_label'] ) ); ?></a>
			</div>
		</div>
	</article>
	<?php
	return trim( ob_get_clean() );
}
