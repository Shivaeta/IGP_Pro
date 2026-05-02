<?php
/**
 * Pricing Summary block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_pricing_summary' ) ) {
	/**
	 * Render pricing summary.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_pricing_summary( array $data ): string {
		$title      = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$currency   = isset( $data['currency'] ) ? trim( igp_pro_to_string( $data['currency'] ) ) : '';
		$base_price = isset( $data['base_price'] ) ? trim( igp_pro_to_string( $data['base_price'] ) ) : '';
		$items      = igp_pro_normalize_list( $data['items'] ?? array() );
		$note       = isset( $data['note'] ) ? trim( igp_pro_to_string( $data['note'] ) ) : '';

		ob_start();
		?>
		<section class="igp-pro-pricing-summary">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== $base_price ) : ?>
				<p class="igp-pro-pricing-summary__base"><span><?php echo esc_html( $currency ); ?></span><?php echo esc_html( $base_price ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $items ) ) : ?>
				<dl class="igp-pro-pricing-summary__items">
					<?php foreach ( $items as $item ) : ?>
						<?php if ( empty( $item['label'] ) && empty( $item['value'] ) ) { continue; } ?>
						<div class="igp-pro-pricing-summary__item">
							<dt><?php echo esc_html( $item['label'] ?? '' ); ?></dt>
							<dd><?php echo esc_html( $item['value'] ?? '' ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>

			<?php if ( '' !== $note ) : ?>
				<p class="igp-pro-pricing-summary__note"><?php echo esc_html( $note ); ?></p>
			<?php endif; ?>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_pricing_summary( $resolved_data ?? array() );
