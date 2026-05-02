<?php
/**
 * Featured Listings block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_featured_listings' ) ) {
	function igp_pro_render_featured_listings( array $data ): string {
		$eyebrow      = isset( $data['eyebrow'] ) ? trim( igp_pro_to_string( $data['eyebrow'] ) ) : '';
		$title        = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$description  = isset( $data['description'] ) ? trim( igp_pro_to_string( $data['description'] ) ) : '';
		$post_type    = igp_pro_enum( $data['post_type'] ?? 'tour', array( 'tour', 'destination', 'both' ), 'tour' );
		$layout       = igp_pro_enum( $data['layout'] ?? 'grid', array( 'grid', 'list', 'slider' ), 'grid' );
		$columns      = igp_pro_int_range( $data['columns'] ?? 3, 3, 1, 4 );
		$limit        = igp_pro_int_range( $data['limit'] ?? 4, 4, 1, 12 );
		$show_excerpt = ! empty( $data['show_excerpt'] );
		$show_price   = ! empty( $data['show_price'] );
		$show_rating  = ! empty( $data['show_rating'] );
		$image_ratio  = igp_pro_enum( $data['image_ratio'] ?? 'landscape', array( 'landscape', 'portrait', 'square' ), 'landscape' );
		$variant      = igp_pro_enum( $data['variant'] ?? 'elevated', array( 'elevated', 'bordered', 'compact' ), 'elevated' );
		$ids          = igp_pro_normalize_post_ids( $data['items'] ?? array() );
		$query_type   = 'both' === $post_type ? array( 'tour', 'destination' ) : $post_type;
		$query_limit  = ! empty( $ids ) ? max( $limit, count( $ids ) ) : $limit;

		$query = igp_pro_get_listing_query( $query_type, $query_limit, $ids );

		ob_start();
		?>
		<section class="igp-pro-listing-block igp-pro-featured-listings igp-pro-featured-listings--<?php echo esc_attr( $layout ); ?> igp-pro-featured-listings--<?php echo esc_attr( $variant ); ?>" style="--igp-card-columns: <?php echo esc_attr( (string) $columns ); ?>;">
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
						echo igp_pro_render_post_card(
							get_post(),
							array(
								'show_excerpt' => $show_excerpt,
								'show_price'   => $show_price,
								'show_rating'  => $show_rating,
								'show_meta'    => true,
								'cta_label'    => __( 'View deal', 'igp-pro' ),
								'image_ratio'  => $image_ratio,
								'card_style'   => $variant,
							)
						);
						?>
					<?php endwhile; ?>
				<?php else : ?>
					<p class="igp-pro-empty-state"><?php esc_html_e( 'No featured listings found.', 'igp-pro' ); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_featured_listings( $resolved_data ?? array() );
