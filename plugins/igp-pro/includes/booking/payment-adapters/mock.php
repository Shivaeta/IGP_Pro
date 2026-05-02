<?php
/**
 * Local payment adapter used for Phase 4 validation.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IGP_Pro_Mock_Payment_Adapter' ) ) {
	/**
	 * Local checkout/payment simulation adapter.
	 */
	class IGP_Pro_Mock_Payment_Adapter implements IGP_Pro_Payment_Adapter {
		public function get_id(): string {
			return 'mock';
		}

		public function get_label(): string {
			return __( 'Local Payment Gateway', 'igp-pro' );
		}

		public function is_available(): bool {
			return true;
		}

		public function create_payment_url( int $booking_id, array $booking, array $pricing ) {
			$key = get_post_meta( $booking_id, '_igp_payment_key', true );
			if ( '' === (string) $key ) {
				$key = wp_generate_password( 32, false, false );
				update_post_meta( $booking_id, '_igp_payment_key', $key );
			}

			return add_query_arg(
				array(
					'igp_pro_checkout' => $booking_id,
					'key'              => rawurlencode( (string) $key ),
				),
				home_url( '/' )
			);
		}
	}
}
