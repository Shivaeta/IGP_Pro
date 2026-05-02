<?php
/**
 * Pricing engine for IGP Pro bookings.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Parse money-like values into floats.
 *
 * @param mixed $value Raw value.
 */
function igp_pro_parse_money( $value ): float {
	if ( is_numeric( $value ) ) {
		return max( 0.0, (float) $value );
	}

	$value = preg_replace( '/[^0-9\.\-]/', '', (string) $value );
	if ( '' === $value || '.' === $value || '-' === $value ) {
		return 0.0;
	}

	return max( 0.0, (float) $value );
}

/**
 * Format money for display.
 */
function igp_pro_format_money( float $amount, string $currency = '₹' ): string {
	$amount = round( $amount, 2 );
	$number = floor( $amount ) === $amount ? number_format_i18n( $amount, 0 ) : number_format_i18n( $amount, 2 );

	return trim( $currency . $number );
}

/**
 * Sanitize a list of pricing rows from JSON/meta.
 *
 * @param mixed  $value Raw value.
 * @param string $fallback_label Fallback label.
 * @return array<int,array{id:string,label:string,price:float,description:string}>
 */
function igp_pro_sanitize_pricing_rows( $value, string $fallback_label = 'Standard' ): array {
	if ( is_string( $value ) ) {
		$decoded = json_decode( $value, true );
		$value   = JSON_ERROR_NONE === json_last_error() ? $decoded : array();
	}

	if ( ! is_array( $value ) ) {
		$value = array();
	}

	$rows = array();
	$seen = array();
	foreach ( $value as $index => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label = isset( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : '';
		if ( '' === $label ) {
			$label = $fallback_label . ' ' . ( count( $rows ) + 1 );
		}

		$id = isset( $row['id'] ) ? sanitize_key( (string) $row['id'] ) : '';
		if ( '' === $id ) {
			$id = sanitize_key( $label );
		}
		if ( '' === $id ) {
			$id = 'item-' . absint( $index );
		}

		$base_id = $id;
		$suffix  = 2;
		while ( isset( $seen[ $id ] ) ) {
			$id = $base_id . '-' . $suffix;
			$suffix++;
		}
		$seen[ $id ] = true;

		$rows[] = array(
			'id'          => $id,
			'label'       => $label,
			'price'       => isset( $row['price'] ) ? igp_pro_parse_money( $row['price'] ) : 0.0,
			'description' => isset( $row['description'] ) ? sanitize_text_field( (string) $row['description'] ) : '',
		);
	}

	return $rows;
}

/**
 * Sanitize traveler rows. This is separate from add-ons/options because travelers define quantity-bearing price lines.
 *
 * @param mixed $value Raw value.
 * @return array<string,array{id:string,label:string,price:float,description:string}>
 */
function igp_pro_sanitize_guest_type_rows( $value ): array {
	$rows = igp_pro_sanitize_pricing_rows( $value, __( 'Traveler', 'igp-pro' ) );
	$out  = array();

	foreach ( $rows as $row ) {
		$out[ $row['id'] ] = $row;
	}

	return $out;
}

/**
 * Return the booking/pricing configuration for a tour.
 */
function igp_pro_get_tour_booking_config( int $tour_id ): array {
	$base_price = igp_pro_parse_money( get_post_meta( $tour_id, '_igp_booking_base_price', true ) );
	if ( $base_price <= 0 ) {
		$base_price = igp_pro_parse_money( get_post_meta( $tour_id, '_igp_price', true ) );
	}
	if ( $base_price <= 0 ) {
		$base_price = igp_pro_parse_money( get_post_meta( $tour_id, 'price', true ) );
	}

	$currency = sanitize_text_field( (string) get_post_meta( $tour_id, '_igp_booking_currency', true ) );
	if ( '' === $currency ) {
		$currency = '₹';
	}

	$options = igp_pro_sanitize_pricing_rows( get_post_meta( $tour_id, '_igp_booking_options', true ), __( 'Option', 'igp-pro' ) );
	if ( empty( $options ) ) {
		$options[] = array(
			'id'          => 'standard',
			'label'       => __( 'Standard tour', 'igp-pro' ),
			'price'       => 0.0,
			'description' => __( 'Default tour option.', 'igp-pro' ),
		);
	}

	$addons = igp_pro_sanitize_pricing_rows( get_post_meta( $tour_id, '_igp_booking_addons', true ), __( 'Add-on', 'igp-pro' ) );

	$guest_types = igp_pro_sanitize_guest_type_rows( get_post_meta( $tour_id, '_igp_booking_guest_types', true ) );
	if ( empty( $guest_types ) ) {
		$adult_price  = igp_pro_parse_money( get_post_meta( $tour_id, '_igp_adult_price', true ) );
		$senior_price = igp_pro_parse_money( get_post_meta( $tour_id, '_igp_senior_price', true ) );
		$child_price  = igp_pro_parse_money( get_post_meta( $tour_id, '_igp_child_price', true ) );

		if ( $adult_price <= 0 ) {
			$adult_price = $base_price;
		}
		if ( $senior_price <= 0 ) {
			$senior_price = $adult_price;
		}
		if ( $child_price <= 0 && $adult_price > 0 ) {
			$child_price = round( $adult_price * 0.6, 2 );
		}

		$guest_types = array(
			'senior'   => array(
				'id'          => 'senior',
				'label'       => __( 'Senior', 'igp-pro' ),
				'price'       => $senior_price,
				'description' => __( 'Senior traveler', 'igp-pro' ),
			),
			'adult'    => array(
				'id'          => 'adult',
				'label'       => __( 'Adult', 'igp-pro' ),
				'price'       => $adult_price,
				'description' => __( 'Adult traveler', 'igp-pro' ),
			),
			'children' => array(
				'id'          => 'children',
				'label'       => __( 'Children', 'igp-pro' ),
				'price'       => $child_price,
				'description' => __( 'Child traveler', 'igp-pro' ),
			),
		);
	}

	return array(
		'tour_id'      => $tour_id,
		'enabled'      => 'no' !== (string) get_post_meta( $tour_id, '_igp_booking_enabled', true ),
		'base_price'   => $base_price,
		'currency'     => $currency,
		'pricing_unit' => sanitize_text_field( (string) ( get_post_meta( $tour_id, '_igp_booking_pricing_unit', true ) ?: '/person' ) ),
		'options'      => $options,
		'addons'       => $addons,
		'guest_types'  => $guest_types,
	);
}

/**
 * Locate a config row by ID.
 */
function igp_pro_find_pricing_row( array $rows, string $id ): ?array {
	foreach ( $rows as $key => $row ) {
		$row_id = isset( $row['id'] ) ? (string) $row['id'] : (string) $key;
		if ( $id === $row_id ) {
			return $row;
		}
	}

	return null;
}

/**
 * Calculate a booking total.
 *
 * @param int   $tour_id Tour ID.
 * @param array $request Request payload.
 * @return array|WP_Error
 */
function igp_pro_calculate_booking_total( int $tour_id, array $request ) {
	if ( $tour_id <= 0 || 'tour' !== get_post_type( $tour_id ) ) {
		return new WP_Error( 'igp_pro_invalid_tour', __( 'Invalid tour selected.', 'igp-pro' ) );
	}

	$config = igp_pro_get_tour_booking_config( $tour_id );
	if ( ! $config['enabled'] ) {
		return new WP_Error( 'igp_pro_booking_disabled', __( 'Booking is disabled for this tour.', 'igp-pro' ) );
	}

	$option_id = isset( $request['tour_option'] ) ? sanitize_key( (string) wp_unslash( $request['tour_option'] ) ) : 'standard';
	$option    = igp_pro_find_pricing_row( $config['options'], $option_id );
	if ( null === $option ) {
		$option = $config['options'][0];
	}

	$guest_request = isset( $request['guest_qty'] ) && is_array( $request['guest_qty'] ) ? wp_unslash( $request['guest_qty'] ) : array();
	$guests        = array();
	$total_guests  = 0;
	$guest_total   = 0.0;

	foreach ( $config['guest_types'] as $type => $guest_type ) {
		$type = sanitize_key( (string) $type );
		$qty  = isset( $guest_request[ $type ] ) ? max( 0, absint( $guest_request[ $type ] ) ) : 0;

		// Backward compatibility for the previous senior_qty/adult_qty/children_qty request keys.
		$legacy_key = $type . '_qty';
		if ( 0 === $qty && isset( $request[ $legacy_key ] ) ) {
			$qty = max( 0, absint( $request[ $legacy_key ] ) );
		}

		$line_total = $qty * (float) $guest_type['price'];
		$guests[ $type ] = array(
			'id'          => $type,
			'label'       => $guest_type['label'],
			'description' => $guest_type['description'] ?? '',
			'quantity'    => $qty,
			'unit_price'  => (float) $guest_type['price'],
			'line_total'  => $line_total,
		);

		$total_guests += $qty;
		$guest_total  += $line_total;
	}

	if ( $total_guests < 1 ) {
		return new WP_Error( 'igp_pro_no_guests', __( 'Please select at least one traveller.', 'igp-pro' ) );
	}

	$requested_addons = isset( $request['addons'] ) ? (array) wp_unslash( $request['addons'] ) : array();
	$requested_addons = array_map( 'sanitize_key', array_map( 'strval', $requested_addons ) );
	$addon_rows       = array();
	$addons_total     = 0.0;

	foreach ( $requested_addons as $addon_id ) {
		$addon = igp_pro_find_pricing_row( $config['addons'], $addon_id );
		if ( null === $addon ) {
			continue;
		}

		$addon_rows[] = $addon;
		$addons_total += (float) $addon['price'];
	}

	$option_total = (float) $option['price'] * $total_guests;
	$total        = $guest_total + $option_total + $addons_total;

	return array(
		'currency'      => $config['currency'],
		'pricing_unit'  => $config['pricing_unit'],
		'option'        => $option,
		'guests'        => $guests,
		'total_guests'  => $total_guests,
		'guest_total'   => $guest_total,
		'option_total'  => $option_total,
		'addons'        => $addon_rows,
		'addons_total'  => $addons_total,
		'total'         => $total,
		'formatted'     => igp_pro_format_money( $total, $config['currency'] ),
		'line_items'    => array(
			'guests'  => $guest_total,
			'option'  => $option_total,
			'addons'  => $addons_total,
			'total'   => $total,
		),
	);
}
