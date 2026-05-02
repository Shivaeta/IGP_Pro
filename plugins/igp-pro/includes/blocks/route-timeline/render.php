<?php
/**
 * Route / Stops Timeline block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_route_timeline' ) ) {
	/**
	 * Render route timeline.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_route_timeline( array $data ): string {
		$title = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$intro = isset( $data['intro'] ) ? trim( igp_pro_to_string( $data['intro'] ) ) : '';
		$stops = igp_pro_normalize_list( $data['stops'] ?? array() );

		ob_start();
		?>
		<div class="igp-pro-route-timeline">
			<?php if ( '' !== $title ) : ?><h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
			<?php if ( '' !== $intro ) : ?><p class="igp-pro-route-timeline__intro"><?php echo esc_html( $intro ); ?></p><?php endif; ?>

			<?php if ( ! empty( $stops ) ) : ?>
				<ol class="igp-pro-route-timeline__list">
					<?php foreach ( $stops as $stop ) : ?>
						<?php
						if ( ! is_array( $stop ) ) {
							continue;
						}
						$day = trim( igp_pro_to_string( $stop['day'] ?? '' ) );
						$name = trim( igp_pro_to_string( $stop['title'] ?? '' ) );
						$location = trim( igp_pro_to_string( $stop['location'] ?? '' ) );
						$duration = trim( igp_pro_to_string( $stop['duration'] ?? '' ) );
						$desc = trim( igp_pro_to_string( $stop['description'] ?? '' ) );
						$highlights = igp_pro_normalize_list( $stop['highlights'] ?? array() );
						if ( '' === $name && '' === $desc ) {
							continue;
						}
						?>
						<li class="igp-pro-route-timeline__stop">
							<?php if ( '' !== $day ) : ?><p class="igp-pro-route-timeline__day"><?php echo esc_html( $day ); ?></p><?php endif; ?>
							<?php if ( '' !== $name ) : ?><h3><?php echo esc_html( $name ); ?></h3><?php endif; ?>
							<?php if ( '' !== $location || '' !== $duration ) : ?><p class="igp-pro-route-timeline__meta"><?php echo esc_html( trim( $location . ( '' !== $duration ? ' · ' . $duration : '' ) ) ); ?></p><?php endif; ?>
							<?php if ( '' !== $desc ) : ?><p><?php echo esc_html( $desc ); ?></p><?php endif; ?>
							<?php if ( ! empty( $highlights ) ) : ?>
								<ul>
									<?php foreach ( $highlights as $highlight ) : ?>
										<?php $highlight_text = is_array( $highlight ) ? trim( igp_pro_to_string( $highlight['item'] ?? '' ) ) : trim( igp_pro_to_string( $highlight ) ); ?>
										<?php if ( '' !== $highlight_text ) : ?><li><?php echo esc_html( $highlight_text ); ?></li><?php endif; ?>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</div>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_route_timeline( $resolved_data ?? array() );
