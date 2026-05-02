<?php
/**
 * Reviews Summary / Aggregate Trust block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_reviews_summary' ) ) {
	/**
	 * Render reviews summary.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_reviews_summary( array $data ): string {
		$title       = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$rating      = isset( $data['average_rating'] ) ? max( 0, min( 5, (float) $data['average_rating'] ) ) : 0;
		$count       = isset( $data['review_count'] ) ? absint( $data['review_count'] ) : 0;
		$source      = isset( $data['rating_source'] ) ? trim( igp_pro_to_string( $data['rating_source'] ) ) : '';
		$summary     = isset( $data['summary'] ) ? trim( igp_pro_to_string( $data['summary'] ) ) : '';
		$breakdown   = igp_pro_normalize_list( $data['breakdown'] ?? array() );
		$testimonials = igp_pro_normalize_list( $data['testimonials'] ?? array() );

		ob_start();
		?>
		<div class="igp-pro-reviews-summary">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<div class="igp-pro-reviews-summary__overview">
				<p class="igp-pro-reviews-summary__rating" aria-label="<?php echo esc_attr( sprintf( __( 'Average rating %.1f out of 5', 'igp-pro' ), $rating ) ); ?>">
					<strong><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></strong><span>/5</span>
				</p>
				<?php if ( $count > 0 || '' !== $source ) : ?>
					<p class="igp-pro-reviews-summary__source"><?php echo esc_html( trim( sprintf( _n( '%1$d review', '%1$d reviews', $count, 'igp-pro' ), $count ) . ( '' !== $source ? ' · ' . $source : '' ) ) ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $summary ) : ?>
					<p class="igp-pro-reviews-summary__summary"><?php echo esc_html( $summary ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $breakdown ) ) : ?>
				<dl class="igp-pro-reviews-summary__breakdown">
					<?php foreach ( $breakdown as $item ) : ?>
						<?php
						if ( ! is_array( $item ) ) {
							continue;
						}
						$label = trim( igp_pro_to_string( $item['label'] ?? '' ) );
						$value = isset( $item['rating'] ) ? max( 0, min( 5, (float) $item['rating'] ) ) : 0;
						if ( '' === $label ) {
							continue;
						}
						?>
						<div>
							<dt><?php echo esc_html( $label ); ?></dt>
							<dd><?php echo esc_html( number_format_i18n( $value, 1 ) ); ?>/5</dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>

			<?php if ( ! empty( $testimonials ) ) : ?>
				<div class="igp-pro-reviews-summary__testimonials">
					<?php foreach ( $testimonials as $item ) : ?>
						<?php
						if ( ! is_array( $item ) ) {
							continue;
						}
						$quote = trim( igp_pro_to_string( $item['quote'] ?? '' ) );
						if ( '' === $quote ) {
							continue;
						}
						$name = trim( igp_pro_to_string( $item['name'] ?? '' ) );
						$loc  = trim( igp_pro_to_string( $item['location'] ?? '' ) );
						?>
						<figure class="igp-pro-review-card">
							<blockquote><?php echo esc_html( $quote ); ?></blockquote>
							<?php if ( '' !== $name || '' !== $loc ) : ?>
								<figcaption><?php echo esc_html( trim( $name . ( '' !== $loc ? ', ' . $loc : '' ) ) ); ?></figcaption>
							<?php endif; ?>
						</figure>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_reviews_summary( $resolved_data ?? array() );
