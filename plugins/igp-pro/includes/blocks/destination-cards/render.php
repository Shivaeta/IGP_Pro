<?php
/**
 * Destination Cards block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_destination_cards' ) ) {
	function igp_pro_render_destination_cards( array $data ): string {
		$eyebrow     = isset( $data['eyebrow'] ) ? trim( igp_pro_to_string( $data['eyebrow'] ) ) : '';
		$title       = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$description = isset( $data['description'] ) ? trim( igp_pro_to_string( $data['description'] ) ) : '';
		$source      = igp_pro_enum( $data['source'] ?? 'query', array( 'manual', 'query' ), 'query' );
		$layout      = igp_pro_enum( $data['layout'] ?? 'grid', array( 'grid', 'slider', 'mosaic' ), 'grid' );
		$columns     = igp_pro_int_range( $data['columns'] ?? 4, 4, 2, 4 );
		$limit       = igp_pro_int_range( $data['limit'] ?? 6, 6, 1, 12 );
		$ids         = 'manual' === $source ? igp_pro_normalize_post_ids( $data['items'] ?? array() ) : array();
		$show_excerpt = ! empty( $data['show_excerpt'] );
		$show_count   = ! empty( $data['show_count'] );
		$image_ratio  = igp_pro_enum( $data['image_ratio'] ?? 'portrait', array( 'landscape', 'portrait', 'square' ), 'portrait' );
		$variant      = igp_pro_enum( $data['variant'] ?? 'overlay', array( 'elevated', 'overlay', 'bordered' ), 'overlay' );
		$query_limit  = 'manual' === $source && ! empty( $ids ) ? max( $limit, count( $ids ) ) : $limit;

		if ( 'manual' === $source && empty( $ids ) ) {
			return igp_pro_render_block_fallback( 'destination_cards', 'manual_items_missing' );
		}

		$query = igp_pro_get_listing_query( 'destination', $query_limit, $ids );

		ob_start();
		?>
		<section class="igp-pro-listing-block igp-pro-destination-cards igp-pro-destination-cards--<?php echo esc_attr( $layout ); ?> igp-pro-destination-cards--<?php echo esc_attr( $variant ); ?>" style="--igp-card-columns: <?php echo esc_attr( (string) $columns ); ?>;">
			<?php if ( '' !== $eyebrow || '' !== $title || '' !== $description ) : ?>
				<header class="igp-pro-block-header">
					<?php if ( '' !== $eyebrow ) : ?><p class="igp-pro-block-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
					<?php if ( '' !== $title ) : ?><h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
					<?php if ( '' !== $description ) : ?><p class="igp-pro-block-description"><?php echo esc_html( $description ); ?></p><?php endif; ?>
				</header>
			<?php endif; ?>

			<div class="igp-pro-card-grid igp-pro-card-grid--<?php echo esc_attr( $layout ); ?>" data-layout="<?php echo esc_attr( $layout ); ?>">
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<?php
						$post       = get_post();
						$tour_count = '';
						if ( $show_count && $post instanceof WP_Post ) {
							$tour_count = igp_pro_get_post_meta_first( $post->ID, array( '_igp_tour_count', 'igp_tour_count', 'tour_count' ) );
							if ( '' === $tour_count ) {
								$tour_count = igp_pro_get_post_meta_first( $post->ID, array( '_igp_package_count', 'igp_package_count', 'package_count' ) );
							}
						}
						echo igp_pro_render_post_card(
							$post,
							array(
								'show_excerpt'   => $show_excerpt,
								'show_location'  => false,
								'show_meta'      => false,
								'show_price'     => false,
								'show_rating'    => false,
								'cta_label'      => __( 'Explore', 'igp-pro' ),
								'image_ratio'    => $image_ratio,
								'card_style'     => $variant,
								'fallback_badge' => '' !== $tour_count ? sprintf( __( '%s Tours', 'igp-pro' ), $tour_count ) : '',
							)
						);
						?>
					<?php endwhile; ?>
				<?php else : ?>
					<p class="igp-pro-empty-state"><?php esc_html_e( 'No destinations found.', 'igp-pro' ); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_destination_cards( $resolved_data ?? array() );
