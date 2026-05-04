<?php
/**
 * Tour Cards block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_tour_cards' ) ) {
	function igp_pro_render_tour_cards( array $data ): string {
		$eyebrow      = isset( $data['eyebrow'] ) ? trim( igp_pro_to_string( $data['eyebrow'] ) ) : '';
		$title        = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$description  = isset( $data['description'] ) ? trim( igp_pro_to_string( $data['description'] ) ) : '';
		$source       = igp_pro_enum( $data['source'] ?? 'query', array( 'query', 'manual' ), 'query' );
		$layout       = igp_pro_enum( $data['layout'] ?? 'grid', array( 'grid', 'slider', 'list' ), 'grid' );
		$columns      = igp_pro_int_range( $data['columns'] ?? 3, 3, 1, 4 );
		$limit        = igp_pro_int_range( $data['limit'] ?? 6, 6, 1, 12 );
		$show_price   = ! empty( $data['show_price'] );
		$show_rating  = ! empty( $data['show_rating'] );
		$show_excerpt = ! empty( $data['show_excerpt'] );
		$show_meta    = ! empty( $data['show_meta'] );
		$cta_label    = isset( $data['cta_label'] ) ? trim( igp_pro_to_string( $data['cta_label'] ) ) : __( 'Book a tour', 'igp-pro' );
		$image_ratio  = igp_pro_enum( $data['image_ratio'] ?? 'landscape', array( 'landscape', 'portrait', 'square' ), 'landscape' );
		$variant      = igp_pro_enum( function_exists( 'igp_pro_get_legacy_visual_variant' ) ? igp_pro_get_legacy_visual_variant( 'tour_cards', $data, 'elevated' ) : 'elevated', array( 'elevated', 'bordered', 'compact' ), 'elevated' );
		$ids          = 'manual' === $source ? igp_pro_normalize_post_ids( $data['items'] ?? array() ) : array();
		$query_limit  = 'manual' === $source && ! empty( $ids ) ? max( $limit, count( $ids ) ) : $limit;
		$destination_ids = igp_pro_normalize_post_ids( $data['destination'] ?? array() );
		$extra       = array();

		$query = null;
		if ( empty( $ids ) && ! empty( $destination_ids ) && function_exists( 'igp_pro_get_tours_for_destinations' ) ) {
			$query = igp_pro_get_tours_for_destinations( $destination_ids, array( 'posts_per_page' => $query_limit ) );
		}

		if ( ! $query instanceof WP_Query ) {
			if ( empty( $ids ) && ! empty( $destination_ids ) ) {
				$extra['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => '_igp_destination_id',
						'value'   => $destination_ids,
						'compare' => 'IN',
					),
					array(
						'key'     => 'igp_destination_id',
						'value'   => $destination_ids,
						'compare' => 'IN',
					),
				);
			}

			$query = igp_pro_get_listing_query( 'tour', $query_limit, $ids, $extra );
		}

		ob_start();
		?>
		<section class="igp-pro-listing-block igp-pro-tour-cards igp-pro-tour-cards--<?php echo esc_attr( $layout ); ?> igp-pro-tour-cards--<?php echo esc_attr( $variant ); ?>" style="--igp-card-columns: <?php echo esc_attr( (string) $columns ); ?>;">
			<?php if ( '' !== $eyebrow || '' !== $title || '' !== $description ) : ?>
				<header class="igp-pro-block-header igp-pro-block-header--split">
					<div>
						<?php if ( '' !== $eyebrow ) : ?><p class="igp-pro-block-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
						<?php if ( '' !== $title ) : ?><h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
					</div>
					<?php if ( '' !== $description ) : ?><p class="igp-pro-block-description"><?php echo esc_html( $description ); ?></p><?php endif; ?>
				</header>
			<?php endif; ?>

			<div class="igp-pro-card-grid igp-pro-card-grid--<?php echo esc_attr( $layout ); ?>" data-layout="<?php echo esc_attr( $layout ); ?>">
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<?php
						echo igp_pro_render_post_card(
							get_post(),
							array(
								'show_excerpt' => $show_excerpt,
								'show_price'   => $show_price,
								'show_rating'  => $show_rating,
								'show_meta'    => $show_meta,
								'cta_label'    => $cta_label,
								'image_ratio'  => $image_ratio,
								'card_style'   => $variant,
							)
						);
						?>
					<?php endwhile; ?>
				<?php else : ?>
					<p class="igp-pro-empty-state"><?php esc_html_e( 'No tours found.', 'igp-pro' ); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_tour_cards( $resolved_data ?? array() );
