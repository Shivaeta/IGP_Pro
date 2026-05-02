<?php
/**
 * Itinerary block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_itinerary' ) ) {
	/**
	 * Render itinerary block.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_itinerary( array $data ): string {
		$title = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$days  = igp_pro_normalize_list( $data['days'] ?? array() );

		if ( empty( $days ) ) {
			return igp_pro_render_block_fallback( 'itinerary', 'days_missing' );
		}

		ob_start();
		?>
		<section class="igp-pro-itinerary">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<ol class="igp-pro-itinerary__days">
				<?php foreach ( $days as $index => $day ) : ?>
					<li class="igp-pro-itinerary__day">
						<h3 class="igp-pro-itinerary__day-title"><?php echo esc_html( $day['day_title'] ?? sprintf( __( 'Day %d', 'igp-pro' ), $index + 1 ) ); ?></h3>
						<?php if ( ! empty( $day['description'] ) ) : ?>
							<div class="igp-pro-itinerary__description"><?php echo igp_pro_kses_content( $day['description'] ); ?></div>
						<?php endif; ?>
						<?php if ( ! empty( $day['meals'] ) || ! empty( $day['stay'] ) ) : ?>
							<ul class="igp-pro-itinerary__meta">
								<?php if ( ! empty( $day['meals'] ) ) : ?><li><?php echo esc_html( $day['meals'] ); ?></li><?php endif; ?>
								<?php if ( ! empty( $day['stay'] ) ) : ?><li><?php echo esc_html( $day['stay'] ); ?></li><?php endif; ?>
							</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_itinerary( $resolved_data ?? array() );
