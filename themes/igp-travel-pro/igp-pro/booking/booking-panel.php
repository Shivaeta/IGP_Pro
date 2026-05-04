<?php
/**
 * IGP Pro booking panel theme override.
 *
 * The plugin supplies the contract/context and continues to own logic, Ajax,
 * checkout, payment adapters, booking storage, and dashboard records.
 *
 * Expected context shape is intentionally defensive because the plugin is the
 * contract authority:
 * - $context['contract'] or $contract: booking form contract array.
 * - $context['tour_id'] or $tour_id: tour ID fallback.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;

$igp_context = isset( $context ) && is_array( $context ) ? $context : array();
$igp_contract = array();

if ( isset( $igp_context['contract'] ) && is_array( $igp_context['contract'] ) ) {
	$igp_contract = $igp_context['contract'];
} elseif ( isset( $contract ) && is_array( $contract ) ) {
	$igp_contract = $contract;
}

if ( empty( $igp_contract ) ) {
	$igp_tour_id = 0;
	if ( isset( $igp_context['tour_id'] ) ) {
		$igp_tour_id = absint( $igp_context['tour_id'] );
	} elseif ( isset( $tour_id ) ) {
		$igp_tour_id = absint( $tour_id );
	} else {
		$igp_tour_id = get_the_ID();
	}
	if ( function_exists( 'igp_travel_pro_get_booking_contract' ) ) {
		$igp_contract = igp_travel_pro_get_booking_contract( $igp_tour_id );
	}
}

if ( function_exists( 'igp_travel_pro_render_booking_visual' ) ) {
	echo igp_travel_pro_render_booking_visual( is_array( $igp_contract ) ? $igp_contract : array(), array( 'root' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
