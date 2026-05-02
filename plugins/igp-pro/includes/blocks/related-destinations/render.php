<?php
/**
 * Related Destinations block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_related_destinations' ) ) {
	/**
	 * Render related destinations.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_related_destinations( array $data ): string {
		$title        = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : __( 'Related Destinations', 'igp-pro' );
		$limit        = igp_pro_int_range( $data['limit'] ?? 3, 3, 1, 12 );
		$layout       = igp_pro_enum( $data['layout'] ?? 'grid', array( 'grid', 'list' ), 'grid' );
		$show_excerpt = ! empty( $data['show_excerpt'] );
		$args         = array();
		$current_id   = get_the_ID() ? absint( get_the_ID() ) : 0;
		$query        = null;

		if ( $current_id > 0 && function_exists( 'igp_pro_get_related_destinations_query' ) ) {
			$query = igp_pro_get_related_destinations_query( $current_id, $limit );
		}

		if ( ! $query instanceof WP_Query ) {
			if ( function_exists( 'igp_pro_get_related_tax_query' ) ) {
				$tax_query = igp_pro_get_related_tax_query();
				if ( ! empty( $tax_query ) ) {
					$args['tax_query'] = $tax_query;
				}
			}

			if ( $current_id > 0 && 'destination' === get_post_type( $current_id ) ) {
				$args['post__not_in'] = array( $current_id );
			}

			$query = igp_pro_get_listing_query( 'destination', $limit, array(), $args );
		}

		ob_start();
		?>
		<section class="igp-pro-related igp-pro-related-destinations igp-pro-related--<?php echo esc_attr( $layout ); ?>">
			<?php if ( '' !== $title ) : ?><h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
			<div class="igp-pro-card-grid" data-layout="<?php echo esc_attr( $layout ); ?>">
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<?php echo igp_pro_render_post_card( get_post(), array( 'show_excerpt' => $show_excerpt ) ); ?>
					<?php endwhile; ?>
				<?php else : ?>
					<p class="igp-pro-empty-state"><?php esc_html_e( 'No related destinations found.', 'igp-pro' ); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_related_destinations( $resolved_data ?? array() );
