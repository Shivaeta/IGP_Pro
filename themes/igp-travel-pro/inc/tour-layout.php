<?php
/**
 * Tour page layout and booking visual helpers for IGP Travel Pro.
 *
 * The plugin remains the owner of booking logic, Ajax, checkout, payment,
 * storage, and dashboard records. This file only maps the plugin-supplied
 * contract to theme-owned markup and page layout.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;

function igp_travel_pro_is_tour_singular(): bool {
	return is_singular( 'tour' );
}

function igp_travel_pro_get_tour_graph( int $post_id ): array {
	if ( function_exists( 'igp_pro_load_content_graph' ) ) {
		$graph = igp_pro_load_content_graph( $post_id );
		return is_array( $graph ) ? $graph : array();
	}
	return array();
}

function igp_travel_pro_get_booking_contract( int $tour_id ): array {
	if ( function_exists( 'igp_pro_get_booking_form_contract' ) ) {
		$contract = igp_pro_get_booking_form_contract( $tour_id );
		return is_array( $contract ) ? $contract : array();
	}

	// Compatibility with the current plugin zip: use the plugin pricing/config
	// helper when the newer public contract function is not present. This keeps
	// booking logic in the plugin while allowing the theme template to receive
	// options/add-ons/guest rows.
	if ( function_exists( 'igp_pro_get_tour_booking_config' ) ) {
		$config = igp_pro_get_tour_booking_config( $tour_id );
		if ( is_array( $config ) && ! empty( $config ) ) {
			$config['tour_id'] = $tour_id;
			$config['trip_id'] = $tour_id;
			$config['form_id'] = 'igp-booking-form-' . absint( $tour_id );
			return $config;
		}
	}

	// Legacy-safe empty contract. The theme must not move booking logic here.
	return array(
		'tour_id'       => $tour_id,
		'trip_id'       => $tour_id,
		'form_id'       => 'igp-booking-form-' . absint( $tour_id ),
		'form_mode'     => 'booking_enquiry',
		'currency'      => '₹',
		'base_price'    => 0,
		'price_label'   => __( 'On request', 'igp-travel-pro' ),
		'unit_label'    => __( '/person', 'igp-travel-pro' ),
		'guest_types'   => array(
			'adult' => array(
				'label'         => __( 'Adult', 'igp-travel-pro' ),
				'description'   => __( 'Ages 12+', 'igp-travel-pro' ),
				'price'         => 0,
				'default'       => 1,
				'min'           => 0,
				'max'           => 20,
			),
		),
		'maximum_guests' => 20,
		'minimum_guests' => 1,
		'options'       => array(),
		'addons'        => array(),
		'dates'         => array(),
		'benefits'      => array(
			__( 'Free cancellation up to 24 hours', 'igp-travel-pro' ),
			__( 'Trusted by verified travelers', 'igp-travel-pro' ),
			__( 'Secure booking and payment handoff', 'igp-travel-pro' ),
			__( '24-hour support', 'igp-travel-pro' ),
		),
	);
}

function igp_travel_pro_contract_value( array $contract, array $keys, $default = null ) {
	foreach ( $keys as $key ) {
		if ( array_key_exists( $key, $contract ) && null !== $contract[ $key ] && '' !== $contract[ $key ] ) {
			return $contract[ $key ];
		}
	}
	return $default;
}

function igp_travel_pro_money( $amount, string $currency = '₹' ): string {
	if ( function_exists( 'igp_travel_pro_normalize_currency_symbol' ) ) {
		$currency = igp_travel_pro_normalize_currency_symbol( $currency );
	}
	if ( is_string( $amount ) && '' !== trim( $amount ) && ! is_numeric( $amount ) ) {
		return trim( $amount );
	}
	if ( function_exists( 'igp_pro_format_money' ) ) {
		return igp_pro_format_money( (float) $amount, $currency );
	}
	$number = number_format_i18n( (float) $amount, ( floor( (float) $amount ) === (float) $amount ? 0 : 2 ) );
	return $currency . $number;
}

function igp_travel_pro_normalize_guest_types( array $contract ): array {
	$guest_types = igp_travel_pro_contract_value( $contract, array( 'guest_types', 'guests', 'traveler_types', 'travellers' ), array() );
	if ( ! is_array( $guest_types ) || empty( $guest_types ) ) {
		$guest_types = array(
			'adult' => array(
				'label'       => __( 'Adult', 'igp-travel-pro' ),
				'description' => __( 'Ages 12+', 'igp-travel-pro' ),
				'price'       => igp_travel_pro_contract_value( $contract, array( 'base_price', 'price' ), 0 ),
				'default'     => 1,
				'min'         => 0,
				'max'         => 20,
			),
		);
	}

	$out = array();
	foreach ( $guest_types as $key => $guest ) {
		$type = is_string( $key ) ? sanitize_key( $key ) : sanitize_key( (string) ( $guest['type'] ?? $guest['id'] ?? 'adult' ) );
		if ( '' === $type ) {
			$type = 'adult';
		}
		$guest = is_array( $guest ) ? $guest : array( 'label' => (string) $guest );
		$out[ $type ] = array(
			'label'       => igp_travel_pro_text( $guest['label'] ?? $guest['name'] ?? ucfirst( $type ) ),
			'description' => igp_travel_pro_text( $guest['description'] ?? $guest['subtitle'] ?? '' ),
			'price'       => (float) ( $guest['price'] ?? $guest['amount'] ?? igp_travel_pro_contract_value( $contract, array( 'base_price', 'price' ), 0 ) ),
			'default'     => absint( $guest['default'] ?? $guest['default_qty'] ?? ( 'adult' === $type ? 1 : 0 ) ),
			'min'         => absint( $guest['min'] ?? 0 ),
			'max'         => absint( $guest['max'] ?? 20 ),
		);
	}
	return $out;
}

function igp_travel_pro_normalize_options( array $contract ): array {
	$options = igp_travel_pro_contract_value( $contract, array( 'options', 'tour_options', 'packages' ), array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}
	if ( empty( $options ) ) {
		$base_price = (float) igp_travel_pro_contract_value( $contract, array( 'base_price', 'price' ), 0 );
		$options[] = array(
			'id'          => 'standard',
			'label'       => __( 'Standard tour', 'igp-travel-pro' ),
			'description' => __( 'Default tour option.', 'igp-travel-pro' ),
			'price'       => $base_price,
		);
	}
	$out = array();
	foreach ( $options as $index => $option ) {
		$option = is_array( $option ) ? $option : array( 'label' => (string) $option );
		$out[] = array(
			'id'          => sanitize_key( (string) ( $option['id'] ?? $option['value'] ?? 'option-' . $index ) ),
			'label'       => igp_travel_pro_text( $option['label'] ?? $option['title'] ?? $option['name'] ?? __( 'Tour option', 'igp-travel-pro' ) ),
			'description' => igp_travel_pro_text( $option['description'] ?? $option['summary'] ?? '' ),
			'price'       => (float) ( $option['price'] ?? $option['amount'] ?? 0 ),
		);
	}
	return $out;
}

function igp_travel_pro_normalize_addons( array $contract ): array {
	$addons = igp_travel_pro_contract_value( $contract, array( 'addons', 'extra_services', 'extras' ), array() );
	if ( ! is_array( $addons ) ) {
		return array();
	}
	$out = array();
	foreach ( $addons as $index => $addon ) {
		$addon = is_array( $addon ) ? $addon : array( 'label' => (string) $addon );
		$out[] = array(
			'id'          => sanitize_key( (string) ( $addon['id'] ?? $addon['value'] ?? 'addon-' . $index ) ),
			'label'       => igp_travel_pro_text( $addon['label'] ?? $addon['title'] ?? $addon['name'] ?? __( 'Add-on', 'igp-travel-pro' ) ),
			'description' => igp_travel_pro_text( $addon['description'] ?? $addon['summary'] ?? '' ),
			'included'    => igp_travel_pro_text( $addon['included'] ?? '' ),
			'excluded'    => igp_travel_pro_text( $addon['excluded'] ?? '' ),
			'price'       => (float) ( $addon['price'] ?? $addon['amount'] ?? 0 ),
		);
	}
	return $out;
}

function igp_travel_pro_booking_context( array $contract ): array {
	$tour_id = absint( igp_travel_pro_contract_value( $contract, array( 'tour_id', 'post_id', 'id' ), get_the_ID() ) );
	$currency = igp_travel_pro_text( igp_travel_pro_contract_value( $contract, array( 'currency' ), '₹' ), '₹' );
	if ( function_exists( 'igp_travel_pro_normalize_currency_symbol' ) ) {
		$currency = igp_travel_pro_normalize_currency_symbol( $currency );
	}
	$base_price = igp_travel_pro_contract_value( $contract, array( 'base_price', 'price', 'price_from' ), 0 );
	$price_label = igp_travel_pro_text( igp_travel_pro_contract_value( $contract, array( 'price_label', 'formatted_price' ), '' ) );
	if ( '' === $price_label ) {
		$price_label = (float) $base_price > 0 ? igp_travel_pro_money( $base_price, $currency ) : __( 'On request', 'igp-travel-pro' );
	}
	$guest_types = igp_travel_pro_normalize_guest_types( $contract );
	$max_guests = absint( igp_travel_pro_contract_value( $contract, array( 'maximum_guests', 'max_guests' ), 0 ) );
	if ( $max_guests <= 0 ) {
		foreach ( $guest_types as $guest ) {
			$max_guests += max( 1, absint( $guest['max'] ?? 20 ) );
		}
	}
	$benefits = igp_travel_pro_contract_value( $contract, array( 'benefits', 'trust_items' ), array() );
	if ( ! is_array( $benefits ) || empty( $benefits ) ) {
		if ( function_exists( 'igp_pro_get_booking_benefits' ) ) {
			$benefits = igp_pro_get_booking_benefits();
		} else {
			$benefits = array(
				__( 'Free cancellation up to 24 hours', 'igp-travel-pro' ),
				__( 'Trusted by verified travelers', 'igp-travel-pro' ),
				__( 'Secure booking and payment handoff', 'igp-travel-pro' ),
				__( '24-hour support', 'igp-travel-pro' ),
			);
		}
	}
	return array(
		'tour_id'        => $tour_id,
		'trip_id'        => absint( igp_travel_pro_contract_value( $contract, array( 'trip_id' ), $tour_id ) ),
		'form_id'        => sanitize_html_class( igp_travel_pro_text( igp_travel_pro_contract_value( $contract, array( 'form_id' ), 'igp-booking-form-' . $tour_id ) ) ),
		'form_mode'      => igp_travel_pro_text( igp_travel_pro_contract_value( $contract, array( 'form_mode', 'mode' ), 'booking_enquiry' ), 'booking_enquiry' ),
		'nonce'          => igp_travel_pro_text( igp_travel_pro_contract_value( $contract, array( 'nonce' ), wp_create_nonce( 'igp_pro_booking' ) ) ),
		'currency'       => $currency,
		'base_price'     => (float) $base_price,
		'price_label'    => $price_label,
		'compare_label'  => igp_travel_pro_text( igp_travel_pro_contract_value( $contract, array( 'compare_price_label', 'compare_label' ), '' ) ),
		'unit_label'     => igp_travel_pro_text( igp_travel_pro_contract_value( $contract, array( 'unit_label', 'pricing_unit' ), __( '/person', 'igp-travel-pro' ) ) ),
		'discount_badge' => igp_travel_pro_text( igp_travel_pro_contract_value( $contract, array( 'discount_badge', 'discount' ), '' ) ),
		'guest_types'    => $guest_types,
		'maximum_guests' => $max_guests,
		'minimum_guests' => absint( igp_travel_pro_contract_value( $contract, array( 'minimum_guests', 'min_guests' ), 1 ) ),
		'options'        => igp_travel_pro_normalize_options( $contract ),
		'addons'         => igp_travel_pro_normalize_addons( $contract ),
		'date_rules'     => igp_travel_pro_contract_value( $contract, array( 'date_rules', 'dates', 'availability_dates' ), array() ),
		'benefits'       => $benefits,
	);
}

function igp_travel_pro_render_booking_date_picker( array $ctx, bool $inside_form = true ): string {
	$rules = is_array( $ctx['date_rules'] ) ? $ctx['date_rules'] : array();
	ob_start();
	?>
	<div class="igp-booking-picker igp-booking-picker--date" data-igp-date-picker>
		<button type="button" class="igp-booking-choice" data-igp-date-toggle aria-expanded="false">
			<span class="igp-booking-choice__icon" aria-hidden="true">▣</span>
			<strong data-igp-date-label><?php esc_html_e( 'Date', 'igp-travel-pro' ); ?></strong>
		</button>
		<input type="hidden" name="booking_date" value="" <?php echo $inside_form ? '' : 'form="' . esc_attr( $ctx['form_id'] ) . '"'; ?>>
		<input type="hidden" name="tour_date" value="" required data-igp-tour-date <?php echo $inside_form ? '' : 'form="' . esc_attr( $ctx['form_id'] ) . '"'; ?>>
		<div class="igp-date-popover" data-igp-date-popover data-currency="<?php echo esc_attr( $ctx['currency'] ); ?>" data-price="<?php echo esc_attr( (string) $ctx['base_price'] ); ?>" data-dates="<?php echo esc_attr( wp_json_encode( $rules ) ); ?>" hidden>
			<div class="igp-calendar">
				<div class="igp-calendar__header">
					<button type="button" data-igp-calendar-prev aria-label="<?php esc_attr_e( 'Previous month', 'igp-travel-pro' ); ?>">‹</button>
					<h6 data-igp-calendar-title><?php echo esc_html( date_i18n( 'F Y' ) ); ?></h6>
					<button type="button" data-igp-calendar-next aria-label="<?php esc_attr_e( 'Next month', 'igp-travel-pro' ); ?>">›</button>
				</div>
				<div class="igp-calendar__days">
					<?php foreach ( array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' ) as $day ) : ?>
						<div class="igp-calendar__day-name"><?php echo esc_html( $day ); ?></div>
					<?php endforeach; ?>
				</div>
				<div class="igp-calendar__dates" data-igp-date-calendar></div>
			</div>
			<div class="igp-calendar__actions">
				<button type="button" data-igp-calendar-close><?php esc_html_e( 'Close', 'igp-travel-pro' ); ?></button>
				<a href="#" data-igp-calendar-apply><?php esc_html_e( 'Apply', 'igp-travel-pro' ); ?></a>
			</div>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

function igp_travel_pro_render_guest_picker( array $ctx ): string {
	ob_start();
	?>
	<div class="igp-booking-picker igp-booking-picker--guest" data-igp-traveler-picker>
		<button type="button" class="igp-booking-choice" data-igp-traveler-toggle aria-expanded="false">
			<span class="igp-booking-choice__icon" aria-hidden="true">♙</span>
			<strong data-igp-traveler-summary><?php esc_html_e( 'Guest', 'igp-travel-pro' ); ?></strong>
		</button>
		<div class="igp-guest-panel" data-igp-traveler-panel hidden>
			<div class="igp-guest-panel__items" data-igp-guest-wrap>
				<?php foreach ( $ctx['guest_types'] as $type => $guest ) : ?>
					<div class="igp-guest-row" data-igp-guest-row>
						<div class="igp-guest-row__copy">
							<strong><?php echo esc_html( $guest['label'] ); ?></strong>
							<?php if ( '' !== $guest['description'] ) : ?><span><?php echo esc_html( $guest['description'] ); ?></span><?php endif; ?>
							<?php if ( (float) $guest['price'] > 0 ) : ?><em><?php echo esc_html( igp_travel_pro_money( $guest['price'], $ctx['currency'] ) ); ?></em><?php endif; ?>
						</div>
						<div class="igp-qty" data-igp-quantity>
							<button type="button" data-igp-qty-minus aria-label="<?php esc_attr_e( 'Decrease', 'igp-travel-pro' ); ?>">−</button>
							<input type="number" name="guest_qty[<?php echo esc_attr( $type ); ?>]" value="<?php echo esc_attr( (string) $guest['default'] ); ?>" min="<?php echo esc_attr( (string) $guest['min'] ); ?>" max="<?php echo esc_attr( (string) $guest['max'] ); ?>" data-price="<?php echo esc_attr( (string) $guest['price'] ); ?>">
							<button type="button" data-igp-qty-plus aria-label="<?php esc_attr_e( 'Increase', 'igp-travel-pro' ); ?>">+</button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="igp-guest-panel__actions">
				<p class="igp-booking-notice igp-booking-notice--error" data-igp-traveler-notice aria-live="polite"></p>
				<a href="#" class="igp-guest-apply" data-igp-traveler-apply><?php esc_html_e( 'Apply', 'igp-travel-pro' ); ?></a>
			</div>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

function igp_travel_pro_render_availability_panel( array $ctx ): string {
	ob_start();
	?>
	<div class="igp-availability-dock" data-igp-availability-dock>
		<div class="igp-availability-panel" data-igp-availability-panel data-igp-availability-for="<?php echo esc_attr( $ctx['form_id'] ); ?>" role="dialog" aria-modal="false" aria-label="<?php esc_attr_e( 'Select tour package and add-ons', 'igp-travel-pro' ); ?>" hidden>
			<div class="igp-availability-panel__header">
				<div>
					<p class="igp-availability-panel__eyebrow"><?php esc_html_e( 'Availability confirmed', 'igp-travel-pro' ); ?></p>
					<h2 class="igp-availability-title"><?php esc_html_e( 'Select package and extras', 'igp-travel-pro' ); ?></h2>
				</div>
				<button type="button" class="igp-availability-panel__close" data-igp-close-availability aria-label="<?php esc_attr_e( 'Close availability panel', 'igp-travel-pro' ); ?>">×</button>
			</div>
			<div class="igp-availability-grid">
				<div class="igp-availability-main">
					<div class="igp-tour-options">
						<p class="igp-booking-section-title"><?php esc_html_e( 'Available package', 'igp-travel-pro' ); ?></p>
						<?php foreach ( $ctx['options'] as $index => $option ) : ?>
							<?php $option_has_price = (float) $option['price'] > 0; ?>
							<label class="igp-tour-option-card <?php echo $option_has_price ? 'igp-tour-option-card--priced' : 'igp-tour-option-card--no-price'; ?>">
								<input type="radio" name="tour_option" value="<?php echo esc_attr( $option['id'] ); ?>" data-price="<?php echo esc_attr( (string) $option['price'] ); ?>" data-igp-tour-option form="<?php echo esc_attr( $ctx['form_id'] ); ?>" <?php checked( 0, $index ); ?>>
								<span class="igp-tour-option-card__dot" aria-hidden="true"></span>
								<span class="igp-tour-option-card__copy">
									<strong><?php echo esc_html( $option['label'] ); ?></strong>
									<?php if ( '' !== $option['description'] ) : ?><em><?php echo esc_html( $option['description'] ); ?></em><?php endif; ?>
								</span>
								<?php if ( $option_has_price ) : ?><span class="igp-tour-option-card__price"><?php echo esc_html( igp_travel_pro_money( $option['price'], $ctx['currency'] ) ); ?></span><?php endif; ?>
							</label>
						<?php endforeach; ?>
					</div>
					<?php if ( ! empty( $ctx['addons'] ) ) : ?>
						<div class="igp-booking-addons" data-igp-addons-wrap>
							<p class="igp-booking-section-title"><?php esc_html_e( 'Extra Services', 'igp-travel-pro' ); ?></p>
							<?php foreach ( $ctx['addons'] as $addon ) : ?>
								<div class="igp-addon-row" data-igp-addon-row>
									<label class="igp-addon-check">
										<input type="checkbox" name="addons[]" value="<?php echo esc_attr( $addon['id'] ); ?>" data-igp-addon-check data-price="<?php echo esc_attr( (string) $addon['price'] ); ?>" form="<?php echo esc_attr( $ctx['form_id'] ); ?>">
										<span class="screen-reader-text"><?php echo esc_html( $addon['label'] ); ?></span>
									</label>
									<div class="igp-addon-copy">
										<strong><?php echo esc_html( $addon['label'] ); ?></strong>
										<?php if ( '' !== $addon['description'] ) : ?><span><?php echo esc_html( $addon['description'] ); ?></span><?php endif; ?>
										<?php if ( '' !== $addon['included'] ) : ?><span class="igp-addon-scope-line"><strong><?php esc_html_e( 'Included:', 'igp-travel-pro' ); ?></strong> <?php echo esc_html( $addon['included'] ); ?></span><?php endif; ?>
										<?php if ( '' !== $addon['excluded'] ) : ?><span class="igp-addon-scope-line"><strong><?php esc_html_e( 'Excluded:', 'igp-travel-pro' ); ?></strong> <?php echo esc_html( $addon['excluded'] ); ?></span><?php endif; ?>
									</div>
									<div class="igp-addon-price"><?php echo esc_html( igp_travel_pro_money( $addon['price'], $ctx['currency'] ) ); ?></div>
									<div class="igp-quantity igp-quantity--small" data-igp-quantity>
										<button type="button" data-igp-qty-minus aria-label="<?php esc_attr_e( 'Decrease add-on quantity', 'igp-travel-pro' ); ?>">−</button>
										<input type="number" name="addon_qty[<?php echo esc_attr( $addon['id'] ); ?>]" value="0" min="0" max="99" data-price="<?php echo esc_attr( (string) $addon['price'] ); ?>" data-igp-addon-qty form="<?php echo esc_attr( $ctx['form_id'] ); ?>" aria-label="<?php echo esc_attr( $addon['label'] ); ?>">
										<button type="button" data-igp-qty-plus aria-label="<?php esc_attr_e( 'Increase add-on quantity', 'igp-travel-pro' ); ?>">+</button>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<p class="igp-booking-cancel-note" data-igp-cancel-note><?php esc_html_e( 'Select a date to see the cancellation window.', 'igp-travel-pro' ); ?></p>
				</div>
				<div class="igp-availability-actions">
					<div class="igp-booking-total" data-currency="<?php echo esc_attr( $ctx['currency'] ); ?>">
						<span><?php esc_html_e( 'Total', 'igp-travel-pro' ); ?></span>
						<strong data-igp-total><?php echo esc_html( igp_travel_pro_money( 0, $ctx['currency'] ) ); ?></strong>
						<em><?php esc_html_e( 'Payable after checkout details', 'igp-travel-pro' ); ?></em>
					</div>
					<button class="igp-booking-submit" type="submit" form="<?php echo esc_attr( $ctx['form_id'] ); ?>"><?php esc_html_e( 'Book Now', 'igp-travel-pro' ); ?></button>
					<button class="igp-booking-secondary" type="button"><?php esc_html_e( 'Add to Cart', 'igp-travel-pro' ); ?></button>
				</div>
			</div>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

function igp_travel_pro_render_booking_card( array $ctx ): string {
	ob_start();
	?>
	<div class="igp-booking-panel">
		<div class="igp-booking-price-row">
			<div class="igp-booking-panel__price">
				<span><?php esc_html_e( 'from', 'igp-travel-pro' ); ?></span>
				<div>
					<?php if ( '' !== $ctx['compare_label'] ) : ?><del><?php echo esc_html( $ctx['compare_label'] ); ?></del><?php endif; ?>
					<strong><?php echo esc_html( $ctx['price_label'] ); ?></strong>
					<em><?php echo esc_html( $ctx['unit_label'] ); ?></em>
				</div>
			</div>
			<?php if ( '' !== $ctx['discount_badge'] ) : ?><span class="igp-booking-discount"><?php echo esc_html( $ctx['discount_badge'] ); ?></span><?php endif; ?>
		</div>
		<?php if ( 'booking_enquiry' === $ctx['form_mode'] ) : ?>
			<form id="<?php echo esc_attr( $ctx['form_id'] ); ?>" action="#" method="post" class="igp-booking-form" data-layout="theme-tour" data-igp-booking-form>
				<input type="hidden" name="action" value="igp_pro_submit_booking">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $ctx['nonce'] ); ?>">
				<input type="hidden" name="tour_id" value="<?php echo esc_attr( (string) $ctx['tour_id'] ); ?>">
				<input type="hidden" name="trip_id" value="<?php echo esc_attr( (string) $ctx['trip_id'] ); ?>">
				<input type="hidden" name="maximum_guests" value="<?php echo esc_attr( (string) $ctx['maximum_guests'] ); ?>">
				<input type="hidden" name="minimum_guests" value="<?php echo esc_attr( (string) $ctx['minimum_guests'] ); ?>">
				<div class="igp-booking-choice-row">
					<?php echo igp_travel_pro_render_booking_date_picker( $ctx ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo igp_travel_pro_render_guest_picker( $ctx ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<p class="igp-booking-message" data-igp-form-message aria-live="polite"></p>
				<button class="igp-booking-submit igp-booking-check" type="button" data-igp-check-availability><?php esc_html_e( 'Check availability', 'igp-travel-pro' ); ?></button>
				<button class="igp-booking-secondary" type="button" data-igp-open-enquiry><?php esc_html_e( 'Make enquiry', 'igp-travel-pro' ); ?></button>
			</form>
		<?php else : ?>
			<p class="igp-booking-message"><?php esc_html_e( 'This tour is available on request. Send an enquiry and the team will confirm availability and pricing.', 'igp-travel-pro' ); ?></p>
			<button class="igp-booking-submit" type="button" data-igp-open-enquiry><?php esc_html_e( 'Make enquiry', 'igp-travel-pro' ); ?></button>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

function igp_travel_pro_render_booking_benefits( array $ctx ): string {
	$out = '';
	foreach ( $ctx['benefits'] as $benefit ) {
		$text = is_array( $benefit ) ? igp_travel_pro_text( $benefit['label'] ?? $benefit['text'] ?? '' ) : igp_travel_pro_text( $benefit );
		if ( '' !== $text ) {
			$out .= '<li>' . esc_html( $text ) . '</li>';
		}
	}
	if ( '' === $out ) {
		return '';
	}
	return '<div class="igp-booking-benefits"><h3>' . esc_html__( 'Why booking with IGP?', 'igp-travel-pro' ) . '</h3><ul>' . $out . '</ul></div>';
}

function igp_travel_pro_render_enquiry_modal( array $ctx ): string {
	$modal_id = 'modal-enquiry-' . absint( $ctx['tour_id'] );
	ob_start();
	?>
	<div id="<?php echo esc_attr( $modal_id ); ?>" class="igp-booking-modal igp-enquiry-modal" data-igp-enquiry-modal hidden>
		<div class="igp-booking-modal__backdrop" data-igp-close-enquiry></div>
		<div class="igp-booking-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $modal_id ); ?>-title">
			<button type="button" class="igp-booking-modal__close" data-igp-close-enquiry aria-label="<?php esc_attr_e( 'Close enquiry form', 'igp-travel-pro' ); ?>">×</button>
			<h2 id="<?php echo esc_attr( $modal_id ); ?>-title"><?php esc_html_e( 'Send enquiry', 'igp-travel-pro' ); ?></h2>
			<form action="#" method="post" class="igp-enquiry-form" data-igp-enquiry-form>
				<input type="hidden" name="action" value="igp_pro_submit_enquiry">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $ctx['nonce'] ); ?>">
				<input type="hidden" name="tour_id" value="<?php echo esc_attr( (string) $ctx['tour_id'] ); ?>">
				<input type="hidden" name="trip_id" value="<?php echo esc_attr( (string) $ctx['trip_id'] ); ?>">
				<div class="igp-enquiry-grid">
					<label class="igp-enquiry-field"><span><?php esc_html_e( 'First name', 'igp-travel-pro' ); ?></span><input name="first_name" type="text" autocomplete="given-name" required></label>
					<label class="igp-enquiry-field"><span><?php esc_html_e( 'Last name', 'igp-travel-pro' ); ?></span><input name="last_name" type="text" autocomplete="family-name" required></label>
					<label class="igp-enquiry-field"><span><?php esc_html_e( 'Email', 'igp-travel-pro' ); ?></span><input name="email" type="email" autocomplete="email" required></label>
					<label class="igp-enquiry-field"><span><?php esc_html_e( 'Phone', 'igp-travel-pro' ); ?></span><input name="phone" type="tel" autocomplete="tel"></label>
					<label class="igp-enquiry-field igp-enquiry-field--full"><span><?php esc_html_e( 'Question', 'igp-travel-pro' ); ?></span><textarea name="question" rows="5" required></textarea></label>
				</div>
				<p class="igp-booking-message" data-igp-form-message aria-live="polite"></p>
				<button class="igp-booking-submit" type="submit"><?php esc_html_e( 'Send enquiry', 'igp-travel-pro' ); ?></button>
			</form>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

function igp_travel_pro_render_booking_visual( array $contract, array $args = array() ): string {
	$ctx = igp_travel_pro_booking_context( $contract );
	$root = array_key_exists( 'root', $args ) ? (bool) $args['root'] : true;
	$classes = $root ? 'igp-booking-shell' : 'igp-booking-shell-inner';
	ob_start();
	if ( $root ) : ?>
		<aside id="booking" class="<?php echo esc_attr( $classes ); ?>" data-igp-booking-panel data-tour-id="<?php echo esc_attr( (string) $ctx['tour_id'] ); ?>" data-form-mode="<?php echo esc_attr( $ctx['form_mode'] ); ?>" aria-label="<?php esc_attr_e( 'Booking and enquiry panel', 'igp-travel-pro' ); ?>">
	<?php else : ?>
		<div id="booking" class="<?php echo esc_attr( $classes ); ?>">
	<?php endif; ?>
		<?php echo igp_travel_pro_render_booking_card( $ctx ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo igp_travel_pro_render_availability_panel( $ctx ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo igp_travel_pro_render_booking_benefits( $ctx ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo igp_travel_pro_render_enquiry_modal( $ctx ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $root ? '</aside>' : '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return (string) ob_get_clean();
}

function igp_travel_pro_split_tour_sections( array $graph ): array {
	$sections = isset( $graph['sections'] ) && is_array( $graph['sections'] ) ? $graph['sections'] : array();
	$nav = array();
	$facts = array();
	$body = array();
	foreach ( $sections as $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$block = igp_travel_pro_normalize_block_id( $section['block_id'] ?? $section['block'] ?? '' );
		if ( in_array( $block, array( 'gallery' ), true ) ) {
			$nav[] = $section;
			continue;
		}
		if ( in_array( $block, array( 'hero' ), true ) && empty( $nav ) ) {
			$nav[] = $section;
			continue;
		}
		if ( in_array( $block, array( 'tour-facts' ), true ) ) {
			$facts[] = $section;
			continue;
		}
		if ( ! in_array( $block, array( 'sticky-booking-cta', 'breadcrumb' ), true ) ) {
			$body[] = $section;
		}
	}
	return array( 'nav' => $nav, 'facts' => $facts, 'body' => $body );
}

function igp_travel_pro_render_tour_fallback_gallery( int $post_id ): string {
	$thumb = get_post_thumbnail_id( $post_id );
	$img = $thumb ? array( 'id' => $thumb ) : array();
	return '<section class="igp-block igp-block--gallery igp-theme-tour-gallery-fallback"><section class="igp-pro-gallery igp-pro-gallery--grid igp-pro-gallery--columns-3">' . igp_travel_pro_render_media( $img, get_the_title( $post_id ), 'igp-pro-gallery__image' ) . igp_travel_pro_render_media( $img, get_the_title( $post_id ), 'igp-pro-gallery__image' ) . igp_travel_pro_render_media( $img, get_the_title( $post_id ), 'igp-pro-gallery__image' ) . '</section></section>';
}

function igp_travel_pro_render_tour_section_group( array $sections, array $context = array() ): string {
	$out = '';
	foreach ( $sections as $section ) {
		$out .= igp_travel_pro_render_section( $section, $context );
	}
	return $out;
}

function igp_travel_pro_render_default_tour_facts( array $contract ): string {
	$ctx = igp_travel_pro_booking_context( $contract );
	$data = array(
		'heading' => array( 'text' => __( 'Quick Tour Facts', 'igp-travel-pro' ), 'level' => 'h2', 'visible' => true ),
		'facts' => array(
			array( 'label' => __( 'Price from', 'igp-travel-pro' ), 'value' => $ctx['price_label'] ),
			array( 'label' => __( 'Guests', 'igp-travel-pro' ), 'value' => sprintf( __( 'Up to %d', 'igp-travel-pro' ), $ctx['maximum_guests'] ) ),
			array( 'label' => __( 'Tour type', 'igp-travel-pro' ), 'value' => __( 'Private / guided', 'igp-travel-pro' ) ),
			array( 'label' => __( 'Availability', 'igp-travel-pro' ), 'value' => __( 'Check dates', 'igp-travel-pro' ) ),
		),
	);
	$section = array( 'id' => 'igp-section-tour-facts-default', 'block_id' => 'tour_facts', 'data' => $data );
	return igp_travel_pro_render_section( $section, array( 'post_id' => get_the_ID() ) );
}

function igp_travel_pro_render_tour_page( int $post_id ): void {
	$graph = igp_travel_pro_get_tour_graph( $post_id );
	$split = igp_travel_pro_split_tour_sections( $graph );
	$contract = igp_travel_pro_get_booking_contract( $post_id );
	$ctx = igp_travel_pro_booking_context( $contract );
	?>
	<article <?php post_class( 'igp-theme-tour' ); ?>>
		<div class="igp-theme-container igp-theme-tour-flow">
			<nav class="igp-theme-tour-gallery-nav igp-theme-content" aria-label="<?php esc_attr_e( 'Tour gallery', 'igp-travel-pro' ); ?>">
				<h1 class="igp-page-title igp-page-title--post_title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h1>
				<?php
				if ( ! empty( $split['nav'] ) ) {
					echo igp_travel_pro_render_tour_section_group( $split['nav'], array( 'post_id' => $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo igp_travel_pro_render_tour_fallback_gallery( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</nav>

			<div class="igp-theme-tour-layout" data-igp-booking-panel data-tour-id="<?php echo esc_attr( (string) $ctx['tour_id'] ); ?>" data-form-mode="<?php echo esc_attr( $ctx['form_mode'] ); ?>">
				<article class="igp-theme-tour-content igp-theme-content">
					<?php
					if ( ! empty( $split['facts'] ) ) {
						echo igp_travel_pro_render_tour_section_group( $split['facts'], array( 'post_id' => $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						echo igp_travel_pro_render_default_tour_facts( $contract ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					echo igp_travel_pro_render_availability_panel( $ctx ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( ! empty( $split['body'] ) ) {
						echo igp_travel_pro_render_tour_section_group( $split['body'], array( 'post_id' => $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						$raw_content = get_the_content( null, false, $post_id );
						$raw_content = preg_replace( '/\[igp_booking_panel[^\]]*\]/', '', (string) $raw_content );
						echo '<div class="rich">' . wp_kses_post( wpautop( $raw_content ) ) . '</div>';
					}
					?>
				</article>

				<aside class="igp-theme-tour-rail" aria-label="<?php esc_attr_e( 'Booking rail', 'igp-travel-pro' ); ?>">
					<aside class="igp-booking-shell" aria-label="<?php esc_attr_e( 'Booking and enquiry panel', 'igp-travel-pro' ); ?>">
						<?php echo igp_travel_pro_render_booking_card( $ctx ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo igp_travel_pro_render_booking_benefits( $ctx ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo igp_travel_pro_render_enquiry_modal( $ctx ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</aside>
				</aside>
			</div>
		</div>
	</article>
	<?php
}
