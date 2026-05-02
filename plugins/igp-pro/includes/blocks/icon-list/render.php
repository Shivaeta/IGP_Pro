<?php
/**
 * Icon List block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_icon_list' ) ) {
	/**
	 * Render icon list block.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_icon_list( array $data ): string {
		$title   = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$items   = igp_pro_normalize_list( $data['items'] ?? array() );
		$columns = igp_pro_int_range( $data['columns'] ?? 2, 2, 1, 4 );

		ob_start();
		?>
		<section class="igp-pro-icon-list igp-pro-icon-list--columns-<?php echo esc_attr( (string) $columns ); ?>">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<ul class="igp-pro-icon-list__items">
				<?php foreach ( $items as $item ) : ?>
					<?php if ( empty( $item['text'] ) && empty( $item['description'] ) ) { continue; } ?>
					<li class="igp-pro-icon-list__item">
						<?php if ( ! empty( $item['icon'] ) ) : ?><span class="igp-pro-icon-list__icon" aria-hidden="true"><?php echo esc_html( $item['icon'] ); ?></span><?php endif; ?>
						<span class="igp-pro-icon-list__body">
							<?php if ( ! empty( $item['text'] ) ) : ?><strong><?php echo esc_html( $item['text'] ); ?></strong><?php endif; ?>
							<?php if ( ! empty( $item['description'] ) ) : ?><span><?php echo igp_pro_kses_content( $item['description'] ); ?></span><?php endif; ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_icon_list( $resolved_data ?? array() );
