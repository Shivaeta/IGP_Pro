<?php
/**
 * Sticky Booking CTA block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_sticky_booking_cta' ) ) {
	/**
	 * Render sticky booking CTA without heavy frontend JavaScript.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_sticky_booking_cta( array $data ): string {
		$title           = isset( $data['cta_title'] ) ? trim( igp_pro_to_string( $data['cta_title'] ) ) : ( isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '' );
		$description     = isset( $data['description'] ) ? trim( igp_pro_to_string( $data['description'] ) ) : '';
		$price_from      = isset( $data['price_from'] ) ? trim( igp_pro_to_string( $data['price_from'] ) ) : '';
		$currency        = isset( $data['currency'] ) ? trim( igp_pro_to_string( $data['currency'] ) ) : '';
		$primary_label   = isset( $data['primary_label'] ) ? trim( igp_pro_to_string( $data['primary_label'] ) ) : '';
		$primary_url     = esc_url( igp_pro_to_string( $data['primary_url'] ?? '' ) );
		$secondary_label = isset( $data['secondary_label'] ) ? trim( igp_pro_to_string( $data['secondary_label'] ) ) : '';
		$secondary_url   = esc_url( igp_pro_to_string( $data['secondary_url'] ?? '' ) );
		$phone_label     = isset( $data['phone_label'] ) ? trim( igp_pro_to_string( $data['phone_label'] ) ) : '';
		$phone_url       = esc_url( igp_pro_to_string( $data['phone_url'] ?? '' ) );
		$note            = isset( $data['note'] ) ? trim( igp_pro_to_string( $data['note'] ) ) : '';

		ob_start();
		?>
		<aside class="igp-pro-sticky-booking-cta" aria-label="<?php echo esc_attr( '' !== $title ? $title : __( 'Booking call to action', 'igp-pro' ) ); ?>">
			<div class="igp-pro-sticky-booking-cta__content">
				<?php if ( '' !== $title ) : ?><p class="igp-pro-sticky-booking-cta__title"><?php echo esc_html( $title ); ?></p><?php endif; ?>
				<?php if ( '' !== $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
				<?php if ( '' !== $price_from ) : ?><p class="igp-pro-sticky-booking-cta__price"><span><?php esc_html_e( 'From', 'igp-pro' ); ?></span> <?php echo esc_html( trim( $currency . $price_from ) ); ?></p><?php endif; ?>
			</div>
			<div class="igp-pro-sticky-booking-cta__actions">
				<?php if ( '' !== $primary_url && '' !== $primary_label ) : ?><a class="igp-pro-button" href="<?php echo esc_url( $primary_url ); ?>"><?php echo esc_html( $primary_label ); ?></a><?php endif; ?>
				<?php if ( '' !== $secondary_url && '' !== $secondary_label ) : ?><a class="igp-pro-button igp-pro-button--secondary" href="<?php echo esc_url( $secondary_url ); ?>"><?php echo esc_html( $secondary_label ); ?></a><?php endif; ?>
				<?php if ( '' !== $phone_url && '' !== $phone_label ) : ?><a class="igp-pro-text-link" href="<?php echo esc_url( $phone_url ); ?>"><?php echo esc_html( $phone_label ); ?></a><?php endif; ?>
			</div>
			<?php if ( '' !== $note ) : ?><p class="igp-pro-sticky-booking-cta__note"><?php echo esc_html( $note ); ?></p><?php endif; ?>
		</aside>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_sticky_booking_cta( $resolved_data ?? array() );
