<?php
/**
 * Tabs block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_tabs' ) ) {
	/**
	 * Render tabs block.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_tabs( array $data ): string {
		$title = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$items = igp_pro_normalize_list( $data['items'] ?? array() );
		$uid   = wp_unique_id( 'igp-pro-tabs-' );

		ob_start();
		?>
		<section class="igp-pro-tabs" id="<?php echo esc_attr( $uid ); ?>">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<div class="igp-pro-tabs__nav" role="tablist">
				<?php foreach ( $items as $index => $item ) : ?>
					<?php $tab_title = isset( $item['tab_title'] ) ? trim( igp_pro_to_string( $item['tab_title'] ) ) : sprintf( __( 'Tab %d', 'igp-pro' ), $index + 1 ); ?>
					<a class="igp-pro-tabs__tab" href="#<?php echo esc_attr( $uid . '-' . $index ); ?>" role="tab"><?php echo esc_html( $tab_title ); ?></a>
				<?php endforeach; ?>
			</div>

			<div class="igp-pro-tabs__panels">
				<?php foreach ( $items as $index => $item ) : ?>
					<section class="igp-pro-tabs__panel" id="<?php echo esc_attr( $uid . '-' . $index ); ?>" role="tabpanel">
						<?php echo igp_pro_kses_content( $item['content'] ?? '' ); ?>
					</section>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_tabs( $resolved_data ?? array() );
