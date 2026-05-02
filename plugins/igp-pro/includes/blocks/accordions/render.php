<?php
/**
 * Accordions block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_accordions' ) ) {
	/**
	 * Render accordions block.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_accordions( array $data ): string {
		$title = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$items = igp_pro_normalize_list( $data['items'] ?? array() );

		ob_start();
		?>
		<section class="igp-pro-accordions">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<div class="igp-pro-accordions__items">
				<?php foreach ( $items as $item ) : ?>
					<details class="igp-pro-accordions__item">
						<summary class="igp-pro-accordions__heading"><?php echo esc_html( $item['heading'] ?? '' ); ?></summary>
						<div class="igp-pro-accordions__content"><?php echo igp_pro_kses_content( $item['content'] ?? '' ); ?></div>
					</details>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_accordions( $resolved_data ?? array() );
