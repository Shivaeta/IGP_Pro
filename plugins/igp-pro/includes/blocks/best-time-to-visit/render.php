<?php
/**
 * Best Time to Visit block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_best_time_to_visit' ) ) {
	/**
	 * Render best time to visit.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_best_time_to_visit( array $data ): string {
		$title      = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$intro      = isset( $data['intro'] ) ? trim( igp_pro_to_string( $data['intro'] ) ) : '';
		$best       = isset( $data['best_months'] ) ? trim( igp_pro_to_string( $data['best_months'] ) ) : '';
		$weather    = isset( $data['weather_summary'] ) ? trim( igp_pro_to_string( $data['weather_summary'] ) ) : '';
		$seasons    = igp_pro_normalize_list( $data['seasons'] ?? array() );
		$tips       = igp_pro_normalize_list( $data['tips'] ?? array() );

		ob_start();
		?>
		<div class="igp-pro-best-time-to-visit">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== $intro ) : ?><p class="igp-pro-best-time-to-visit__intro"><?php echo esc_html( $intro ); ?></p><?php endif; ?>

			<?php if ( '' !== $best || '' !== $weather ) : ?>
				<div class="igp-pro-best-time-to-visit__summary">
					<?php if ( '' !== $best ) : ?><p><strong><?php esc_html_e( 'Best months:', 'igp-pro' ); ?></strong> <?php echo esc_html( $best ); ?></p><?php endif; ?>
					<?php if ( '' !== $weather ) : ?><p><?php echo esc_html( $weather ); ?></p><?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $seasons ) ) : ?>
				<div class="igp-pro-best-time-to-visit__seasons">
					<?php foreach ( $seasons as $season ) : ?>
						<?php
						if ( ! is_array( $season ) ) {
							continue;
						}
						$name = trim( igp_pro_to_string( $season['name'] ?? '' ) );
						$months = trim( igp_pro_to_string( $season['months'] ?? '' ) );
						$temp = trim( igp_pro_to_string( $season['temperature'] ?? '' ) );
						$desc = trim( igp_pro_to_string( $season['description'] ?? '' ) );
						$best_for = trim( igp_pro_to_string( $season['best_for'] ?? '' ) );
						if ( '' === $name && '' === $desc ) {
							continue;
						}
						?>
						<article class="igp-pro-season-card">
							<?php if ( '' !== $name ) : ?><h3><?php echo esc_html( $name ); ?></h3><?php endif; ?>
							<?php if ( '' !== $months || '' !== $temp ) : ?><p class="igp-pro-season-card__meta"><?php echo esc_html( trim( $months . ( '' !== $temp ? ' · ' . $temp : '' ) ) ); ?></p><?php endif; ?>
							<?php if ( '' !== $desc ) : ?><p><?php echo esc_html( $desc ); ?></p><?php endif; ?>
							<?php if ( '' !== $best_for ) : ?><p><strong><?php esc_html_e( 'Best for:', 'igp-pro' ); ?></strong> <?php echo esc_html( $best_for ); ?></p><?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $tips ) ) : ?>
				<ul class="igp-pro-best-time-to-visit__tips">
					<?php foreach ( $tips as $tip ) : ?>
						<?php $tip_text = is_array( $tip ) ? trim( igp_pro_to_string( $tip['item'] ?? '' ) ) : trim( igp_pro_to_string( $tip ) ); ?>
						<?php if ( '' !== $tip_text ) : ?><li><?php echo esc_html( $tip_text ); ?></li><?php endif; ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_best_time_to_visit( $resolved_data ?? array() );
