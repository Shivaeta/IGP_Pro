<?php
/**
 * FAQ block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_faq' ) ) {
	/**
	 * Render FAQ block.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_faq( array $data ): string {
		$title = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$items = igp_pro_normalize_list( $data['items'] ?? array() );

		ob_start();
		?>
		<section class="igp-pro-faq">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<div class="igp-pro-faq__items">
				<?php foreach ( $items as $item ) : ?>
					<?php if ( empty( $item['question'] ) && empty( $item['answer'] ) ) { continue; } ?>
					<details class="igp-pro-faq__item">
						<summary class="igp-pro-faq__question"><?php echo esc_html( $item['question'] ?? '' ); ?></summary>
						<div class="igp-pro-faq__answer"><?php echo igp_pro_kses_content( $item['answer'] ?? '' ); ?></div>
					</details>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_faq( $resolved_data ?? array() );
