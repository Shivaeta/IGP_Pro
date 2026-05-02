<?php
/**
 * Related Tours block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_get_related_tax_query' ) ) {
	/**
	 * Get a same-region tax query for current post if available.
	 *
	 * @return array
	 */
	function igp_pro_get_related_tax_query(): array {
		$post_id = get_the_ID();

		if ( ! $post_id || ! taxonomy_exists( 'travel_region' ) ) {
			return array();
		}

		$terms = wp_get_post_terms( $post_id, 'travel_region', array( 'fields' => 'ids' ) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		return array(
			array(
				'taxonomy' => 'travel_region',
				'field'    => 'term_id',
				'terms'    => array_map( 'absint', $terms ),
			),
		);
	}
}

if ( ! function_exists( 'igp_pro_render_related_tours' ) ) {
	/**
	 * Render related tours.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_related_tours( array $data ): string {
		$title        = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : __( 'Related Tours', 'igp-pro' );
		$limit        = igp_pro_int_range( $data['limit'] ?? 3, 3, 1, 12 );
		$layout       = igp_pro_enum( $data['layout'] ?? 'grid', array( 'grid', 'list' ), 'grid' );
		$show_excerpt = ! empty( $data['show_excerpt'] );
		$args         = array();
		$tax_query    = igp_pro_get_related_tax_query();

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		if ( get_the_ID() && 'tour' === get_post_type( get_the_ID() ) ) {
			$args['post__not_in'] = array( get_the_ID() );
		}

		$query = igp_pro_get_listing_query( 'tour', $limit, array(), $args );

		ob_start();
		?>
		<section class="igp-pro-related igp-pro-related-tours igp-pro-related--<?php echo esc_attr( $layout ); ?>">
			<?php if ( '' !== $title ) : ?><h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
			<div class="igp-pro-card-grid" data-layout="<?php echo esc_attr( $layout ); ?>">
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<?php echo igp_pro_render_post_card( get_post(), array( 'show_excerpt' => $show_excerpt, 'show_price' => true, 'show_rating' => true ) ); ?>
					<?php endwhile; ?>
				<?php else : ?>
					<p class="igp-pro-empty-state"><?php esc_html_e( 'No related tours found.', 'igp-pro' ); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_related_tours( $resolved_data ?? array() );
