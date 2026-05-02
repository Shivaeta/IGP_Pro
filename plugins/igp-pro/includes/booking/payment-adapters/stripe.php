<?php
/**
 * Stripe adapter placeholder.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IGP_Pro_Stripe_Payment_Adapter' ) ) {
	/**
	 * Stripe adapter scaffold. Real API calls are enabled in a later credentialed integration pass.
	 */
	class IGP_Pro_Stripe_Payment_Adapter implements IGP_Pro_Payment_Adapter {
		public function get_id(): string { return 'stripe'; }
		public function get_label(): string { return __( 'Stripe', 'igp-pro' ); }
		public function is_available(): bool {
			return '' !== (string) get_option( 'igp_pro_stripe_secret_key', '' );
		}
		public function create_payment_url( int $booking_id, array $booking, array $pricing ) {
			return new WP_Error( 'igp_pro_stripe_not_connected', __( 'Stripe credentials are not connected yet. Use the local gateway for Phase 4 validation.', 'igp-pro' ) );
		}
	}
}
