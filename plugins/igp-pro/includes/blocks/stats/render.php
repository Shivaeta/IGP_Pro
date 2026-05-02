<?php
/**
 * Stats / Highlights block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_stats' ) ) {
	/**
	 * Render stats block.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_stats( array $data ): string {
		$title   = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$items   = igp_pro_normalize_list( $data['items'] ?? array() );
		$columns = igp_pro_int_range( $data['columns'] ?? 4, 4, 1, 4 );

		ob_start();
		?>
		<section class="igp-pro-stats igp-pro-stats--columns-<?php echo esc_attr( (string) $columns ); ?>">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<div class="igp-pro-stats__items">
				<?php foreach ( $items as $item ) : ?>
					<?php if ( empty( $item['value'] ) && empty( $item['label'] ) ) { continue; } ?>
					<div class="igp-pro-stats__item">
						<?php if ( ! empty( $item['value'] ) ) : ?><strong class="igp-pro-stats__value"><?php echo esc_html( $item['value'] ); ?></strong><?php endif; ?>
						<?php if ( ! empty( $item['label'] ) ) : ?><span class="igp-pro-stats__label"><?php echo esc_html( $item['label'] ); ?></span><?php endif; ?>
						<?php if ( ! empty( $item['description'] ) ) : ?><p class="igp-pro-stats__description"><?php echo igp_pro_kses_content( $item['description'] ); ?></p><?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_stats( $resolved_data ?? array() );
