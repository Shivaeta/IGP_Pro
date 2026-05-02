<?php
/**
 * Payment adapter contract for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! interface_exists( 'IGP_Pro_Payment_Adapter' ) ) {
	/**
	 * Payment adapter interface.
	 */
	interface IGP_Pro_Payment_Adapter {
		/**
		 * Adapter ID.
		 */
		public function get_id(): string;

		/**
		 * Adapter label.
		 */
		public function get_label(): string;

		/**
		 * Whether this adapter can process in the current install.
		 */
		public function is_available(): bool;

		/**
		 * Create a payment redirect URL for the supplied booking.
		 *
		 * @param int   $booking_id Booking submission post ID.
		 * @param array $booking Booking payload.
		 * @param array $pricing Pricing payload.
		 * @return string|WP_Error
		 */
		public function create_payment_url( int $booking_id, array $booking, array $pricing );
	}
}

/**
 * Return registered payment adapters.
 *
 * @return array<string,IGP_Pro_Payment_Adapter>
 */
function igp_pro_get_payment_adapters(): array {
	$adapters = array();

	foreach ( array( 'IGP_Pro_Mock_Payment_Adapter', 'IGP_Pro_Razorpay_Payment_Adapter', 'IGP_Pro_Stripe_Payment_Adapter', 'IGP_Pro_Paypal_Payment_Adapter' ) as $class_name ) {
		if ( class_exists( $class_name ) ) {
			$adapter = new $class_name();
			if ( $adapter instanceof IGP_Pro_Payment_Adapter ) {
				$adapters[ $adapter->get_id() ] = $adapter;
			}
		}
	}

	/**
	 * Filter available payment adapters.
	 *
	 * @param array<string,IGP_Pro_Payment_Adapter> $adapters Adapters.
	 */
	return apply_filters( 'igp_pro_payment_adapters', $adapters );
}

/**
 * Resolve the active adapter, falling back to the local/mock gateway.
 *
 * @param string $adapter_id Adapter ID.
 * @return IGP_Pro_Payment_Adapter|WP_Error
 */
function igp_pro_get_payment_adapter( string $adapter_id = '' ) {
	$adapters   = igp_pro_get_payment_adapters();
	$adapter_id = sanitize_key( '' !== $adapter_id ? $adapter_id : get_option( 'igp_pro_payment_adapter', 'mock' ) );

	if ( isset( $adapters[ $adapter_id ] ) && $adapters[ $adapter_id ]->is_available() ) {
		return $adapters[ $adapter_id ];
	}

	if ( isset( $adapters['mock'] ) ) {
		return $adapters['mock'];
	}

	return new WP_Error( 'igp_pro_no_payment_adapter', __( 'No payment adapter is available.', 'igp-pro' ) );
}
