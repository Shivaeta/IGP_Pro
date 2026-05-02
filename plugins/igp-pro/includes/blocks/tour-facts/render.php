<?php
/**
 * Tour Facts / Quick Info block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_tour_facts' ) ) {
	/**
	 * Render tour facts.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_tour_facts( array $data ): string {
		$title = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$intro = isset( $data['intro'] ) ? trim( igp_pro_to_string( $data['intro'] ) ) : '';
		$facts = igp_pro_normalize_list( $data['facts'] ?? array() );

		ob_start();
		?>
		<div class="igp-pro-tour-facts">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== $intro ) : ?>
				<p class="igp-pro-tour-facts__intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $facts ) ) : ?>
				<dl class="igp-pro-tour-facts__grid">
					<?php foreach ( $facts as $fact ) : ?>
						<?php
						if ( ! is_array( $fact ) ) {
							continue;
						}
						$label = trim( igp_pro_to_string( $fact['label'] ?? '' ) );
						$value = trim( igp_pro_to_string( $fact['value'] ?? '' ) );
						$icon  = trim( igp_pro_to_string( $fact['icon'] ?? '' ) );
						$note  = trim( igp_pro_to_string( $fact['note'] ?? '' ) );
						if ( '' === $label && '' === $value ) {
							continue;
						}
						?>
						<div class="igp-pro-tour-facts__item">
							<?php if ( '' !== $icon ) : ?>
								<span class="igp-pro-tour-facts__icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
							<?php endif; ?>
							<dt><?php echo esc_html( $label ); ?></dt>
							<dd>
								<span class="igp-pro-tour-facts__value"><?php echo esc_html( $value ); ?></span>
								<?php if ( '' !== $note ) : ?>
									<span class="igp-pro-tour-facts__note"><?php echo esc_html( $note ); ?></span>
								<?php endif; ?>
							</dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
		</div>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_tour_facts( $resolved_data ?? array() );
