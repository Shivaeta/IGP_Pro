<?php
/**
 * Download Brochure CTA block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_brochure_cta' ) ) {
	/**
	 * Render brochure CTA.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_brochure_cta( array $data ): string {
		$title           = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$description     = isset( $data['description'] ) ? trim( igp_pro_to_string( $data['description'] ) ) : '';
		$file_url        = esc_url( igp_pro_to_string( $data['file_url'] ?? '' ) );
		$button_label    = isset( $data['button_label'] ) ? trim( igp_pro_to_string( $data['button_label'] ) ) : '';
		$secondary_label = isset( $data['secondary_label'] ) ? trim( igp_pro_to_string( $data['secondary_label'] ) ) : '';
		$secondary_url   = esc_url( igp_pro_to_string( $data['secondary_url'] ?? '' ) );
		$form_note       = isset( $data['form_note'] ) ? trim( igp_pro_to_string( $data['form_note'] ) ) : '';

		ob_start();
		?>
		<div class="igp-pro-brochure-cta">
			<div class="igp-pro-brochure-cta__body">
				<?php if ( '' !== $title ) : ?><h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
				<?php if ( '' !== $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
				<?php if ( '' !== $form_note ) : ?><p class="igp-pro-brochure-cta__note"><?php echo esc_html( $form_note ); ?></p><?php endif; ?>
			</div>
			<div class="igp-pro-brochure-cta__actions">
				<?php if ( '' !== $file_url && '' !== $button_label ) : ?><a class="igp-pro-button" href="<?php echo esc_url( $file_url ); ?>" download><?php echo esc_html( $button_label ); ?></a><?php endif; ?>
				<?php if ( '' !== $secondary_url && '' !== $secondary_label ) : ?><a class="igp-pro-button igp-pro-button--secondary" href="<?php echo esc_url( $secondary_url ); ?>"><?php echo esc_html( $secondary_label ); ?></a><?php endif; ?>
			</div>
		</div>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_brochure_cta( $resolved_data ?? array() );
