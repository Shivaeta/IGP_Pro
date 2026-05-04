<?php
/**
 * Theme override for IGP Pro booking/enquiry panel.
 *
 * Available variable:
 * $contract array
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $contract ) || ! is_array( $contract ) ) {
	return;
}

$tour_id       = absint( $contract['tour_id'] ?? 0 );
$form_id       = sanitize_key( (string) ( $contract['form_id'] ?? '' ) );
$form_mode     = sanitize_key( (string) ( $contract['form_mode'] ?? 'booking_enquiry' ) );
$currency      = (string) ( $contract['currency'] ?? '₹' );
$price_label   = (string) ( $contract['price_label'] ?? '' );
$compare_label = (string) ( $contract['compare_label'] ?? '' );
$pricing_unit  = (string) ( $contract['pricing_unit'] ?? '/person' );
$nonce         = (string) ( $contract['nonce'] ?? '' );
$booking_date  = (string) ( $contract['booking_date'] ?? current_time( 'Y-m-d' ) );
$actions       = isset( $contract['actions'] ) && is_array( $contract['actions'] ) ? $contract['actions'] : array();
$guest_types   = isset( $contract['guest_types'] ) && is_array( $contract['guest_types'] ) ? $contract['guest_types'] : array();
$options       = isset( $contract['options'] ) && is_array( $contract['options'] ) ? $contract['options'] : array();
$addons        = isset( $contract['addons'] ) && is_array( $contract['addons'] ) ? $contract['addons'] : array();
$benefits      = isset( $contract['benefits'] ) && is_array( $contract['benefits'] ) ? $contract['benefits'] : array();

if ( $tour_id <= 0 || '' === $form_id ) {
	return;
}
?>

<aside
	class="igp-booking-shell igp-theme-booking-shell"
	data-igp-booking-panel
	data-tour-id="<?php echo esc_attr( $tour_id ); ?>"
	data-form-mode="<?php echo esc_attr( $form_mode ); ?>"
	aria-label="<?php esc_attr_e( 'Booking and enquiry panel', 'igp-travel-pro' ); ?>"
>
	<div class="igp-booking-panel igp-theme-booking-card">
		<header class="igp-theme-booking-card__header">
			<p class="igp-theme-booking-eyebrow"><?php esc_html_e( 'Book this tour', 'igp-travel-pro' ); ?></p>

			<div class="igp-booking-price-row">
				<div class="igp-booking-panel__price">
					<span><?php esc_html_e( 'from', 'igp-travel-pro' ); ?></span>
					<div>
						<?php if ( '' !== $compare_label ) : ?>
							<del><?php echo esc_html( $compare_label ); ?></del>
						<?php endif; ?>

						<strong><?php echo esc_html( $price_label ); ?></strong>
						<em><?php echo esc_html( $pricing_unit ); ?></em>
					</div>
				</div>

				<?php if ( ! empty( $contract['discount_badge'] ) ) : ?>
					<span class="igp-booking-discount"><?php echo esc_html( (string) $contract['discount_badge'] ); ?></span>
				<?php endif; ?>
			</div>
		</header>

		<?php if ( 'booking_enquiry' === $form_mode ) : ?>
			<form id="<?php echo esc_attr( $form_id ); ?>" class="igp-booking-form igp-theme-booking-form" data-igp-booking-form>
				<input type="hidden" name="action" value="<?php echo esc_attr( $actions['booking'] ?? 'igp_pro_submit_booking' ); ?>">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
				<input type="hidden" name="tour_id" value="<?php echo esc_attr( $tour_id ); ?>">
				<input type="hidden" name="booking_date" value="<?php echo esc_attr( $booking_date ); ?>">

				<div class="igp-booking-choice-row igp-theme-booking-choice-row">
					<div class="igp-booking-picker igp-booking-picker--date" data-igp-date-picker>
						<button type="button" class="igp-booking-choice" data-igp-date-toggle aria-expanded="false">
							<span class="igp-booking-choice__icon" aria-hidden="true">▣</span>
							<strong data-igp-date-label><?php esc_html_e( 'Date', 'igp-travel-pro' ); ?></strong>
						</button>

						<input type="hidden" name="tour_date" value="" required data-igp-tour-date>

						<div
							class="igp-booking-date-popover"
							data-igp-date-popover
							hidden
							data-currency="<?php echo esc_attr( $currency ); ?>"
							data-price="<?php echo esc_attr( (float) ( $contract['base_price'] ?? 0 ) ); ?>"
						>
							<div class="igp-date-calendar" data-igp-date-calendar></div>
						</div>
					</div>

					<div class="igp-booking-picker igp-booking-picker--guest" data-igp-traveler-picker>
						<button type="button" class="igp-booking-choice" data-igp-traveler-toggle aria-expanded="false">
							<span class="igp-booking-choice__icon" aria-hidden="true">♙</span>
							<strong data-igp-traveler-summary><?php esc_html_e( 'Guest', 'igp-travel-pro' ); ?></strong>
						</button>

						<div class="igp-booking-traveler-panel" data-igp-traveler-panel hidden>
							<div class="igp-booking-guests" data-igp-guest-wrap>
								<?php foreach ( $guest_types as $type => $guest ) : ?>
									<?php
									$type        = sanitize_key( (string) $type );
									$guest_label = (string) ( $guest['label'] ?? $type );
									$guest_desc  = (string) ( $guest['description'] ?? '' );
									$guest_price = (float) ( $guest['price'] ?? 0 );
									?>
									<div class="igp-booking-guest-row" data-igp-guest-row>
										<div class="igp-booking-guest-copy">
											<strong><?php echo esc_html( $guest_label ); ?></strong>
											<?php if ( '' !== $guest_desc ) : ?>
												<span><?php echo esc_html( $guest_desc ); ?></span>
											<?php endif; ?>
										</div>

										<div class="igp-quantity" data-igp-quantity>
											<button type="button" data-igp-qty-minus aria-label="<?php esc_attr_e( 'Decrease quantity', 'igp-travel-pro' ); ?>">−</button>
											<input
												type="number"
												min="0"
												max="9999"
												value="0"
												name="guest_qty[<?php echo esc_attr( $type ); ?>]"
												data-price="<?php echo esc_attr( $guest_price ); ?>"
												aria-label="<?php echo esc_attr( $guest_label ); ?>"
											>
											<button type="button" data-igp-qty-plus aria-label="<?php esc_attr_e( 'Increase quantity', 'igp-travel-pro' ); ?>">+</button>
										</div>
									</div>
								<?php endforeach; ?>
							</div>

							<p class="igp-booking-message igp-booking-message--inline" data-igp-traveler-notice aria-live="polite"></p>
							<button type="button" class="igp-booking-apply" data-igp-traveler-apply><?php esc_html_e( 'Apply', 'igp-travel-pro' ); ?></button>
						</div>
					</div>
				</div>

				<p class="igp-booking-message" data-igp-form-message aria-live="polite"></p>

				<button class="igp-booking-submit igp-booking-check" type="button" data-igp-check-availability>
					<?php esc_html_e( 'Check availability', 'igp-travel-pro' ); ?>
				</button>

				<button class="igp-booking-secondary" type="button" data-igp-open-enquiry>
					<?php esc_html_e( 'Make enquiry', 'igp-travel-pro' ); ?>
				</button>

				<div
					class="igp-availability-panel"
					data-igp-availability-panel
					data-igp-availability-for="<?php echo esc_attr( $form_id ); ?>"
					hidden
				>
					<div class="igp-availability-main">
						<?php if ( ! empty( $options ) ) : ?>
							<div class="igp-tour-options">
								<?php foreach ( $options as $index => $option ) : ?>
									<?php
									$option_id    = sanitize_key( (string) ( $option['id'] ?? '' ) );
									$option_label = (string) ( $option['label'] ?? '' );
									$option_desc  = (string) ( $option['description'] ?? '' );
									$option_price = (float) ( $option['price'] ?? 0 );
									?>
									<label class="igp-tour-option-card">
										<input
											type="radio"
											name="tour_option"
											value="<?php echo esc_attr( $option_id ); ?>"
											data-price="<?php echo esc_attr( $option_price ); ?>"
											data-igp-tour-option
											form="<?php echo esc_attr( $form_id ); ?>"
											<?php checked( 0 === (int) $index ); ?>
										>
										<span class="igp-tour-option-card__dot"></span>
										<span class="igp-tour-option-card__copy">
											<strong><?php echo esc_html( $option_label ); ?></strong>
											<?php if ( '' !== $option_desc ) : ?>
												<em><?php echo esc_html( $option_desc ); ?></em>
											<?php endif; ?>
										</span>
										<span class="igp-tour-option-card__price">
											<?php echo esc_html( function_exists( 'igp_pro_format_money' ) ? igp_pro_format_money( $option_price, $currency ) : $currency . $option_price ); ?>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $addons ) ) : ?>
							<div class="igp-booking-addons" data-igp-addons-wrap>
								<p class="igp-booking-section-title"><?php esc_html_e( 'Extra Services', 'igp-travel-pro' ); ?></p>

								<?php foreach ( $addons as $addon ) : ?>
									<?php
									$addon_id    = sanitize_key( (string) ( $addon['id'] ?? '' ) );
									$addon_label = (string) ( $addon['label'] ?? '' );
									$addon_desc  = (string) ( $addon['description'] ?? '' );
									$addon_price = (float) ( $addon['price'] ?? 0 );
									?>
									<div class="igp-addon-row" data-igp-addon-row>
										<label class="igp-addon-check">
											<input
												type="checkbox"
												name="addons[]"
												value="<?php echo esc_attr( $addon_id ); ?>"
												data-igp-addon-check
												data-price="<?php echo esc_attr( $addon_price ); ?>"
												form="<?php echo esc_attr( $form_id ); ?>"
											>
											<span class="screen-reader-text"><?php echo esc_html( $addon_label ); ?></span>
										</label>

										<div class="igp-addon-copy">
											<strong><?php echo esc_html( $addon_label ); ?></strong>
											<?php if ( '' !== $addon_desc ) : ?>
												<span><?php echo esc_html( $addon_desc ); ?></span>
											<?php endif; ?>
										</div>

										<div class="igp-addon-price">
											<?php echo esc_html( function_exists( 'igp_pro_format_money' ) ? igp_pro_format_money( $addon_price, $currency ) : $currency . $addon_price ); ?>
										</div>

										<div class="igp-quantity igp-quantity--small" data-igp-quantity>
											<button type="button" data-igp-qty-minus aria-label="<?php esc_attr_e( 'Decrease add-on quantity', 'igp-travel-pro' ); ?>">−</button>
											<input
												type="number"
												min="0"
												max="9999"
												value="0"
												name="addon_qty[<?php echo esc_attr( $addon_id ); ?>]"
												data-price="<?php echo esc_attr( $addon_price ); ?>"
												data-igp-addon-qty
												aria-label="<?php echo esc_attr( $addon_label ); ?>"
												form="<?php echo esc_attr( $form_id ); ?>"
											>
											<button type="button" data-igp-qty-plus aria-label="<?php esc_attr_e( 'Increase add-on quantity', 'igp-travel-pro' ); ?>">+</button>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<p class="igp-booking-cancel-note" data-igp-cancel-note>
							<?php esc_html_e( 'Select a date to see the cancellation window.', 'igp-travel-pro' ); ?>
						</p>
					</div>

					<div class="igp-availability-actions">
						<div class="igp-booking-total" data-currency="<?php echo esc_attr( $currency ); ?>">
							<span><?php esc_html_e( 'Total', 'igp-travel-pro' ); ?></span>
							<strong data-igp-total><?php echo esc_html( function_exists( 'igp_pro_format_money' ) ? igp_pro_format_money( 0, $currency ) : $currency . '0' ); ?></strong>
							<em><?php esc_html_e( 'Payable after checkout details', 'igp-travel-pro' ); ?></em>
						</div>

						<button class="igp-booking-submit" type="submit" form="<?php echo esc_attr( $form_id ); ?>">
							<?php esc_html_e( 'Book Now', 'igp-travel-pro' ); ?>
						</button>
					</div>
				</div>
			</form>
		<?php else : ?>
			<div class="igp-enquiry-only-card">
				<p><?php esc_html_e( 'This tour is available on request. Send an enquiry and the team will confirm availability and pricing.', 'igp-travel-pro' ); ?></p>
				<button class="igp-booking-secondary igp-booking-secondary--full" type="button" data-igp-open-enquiry>
					<?php esc_html_e( 'Make enquiry', 'igp-travel-pro' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $benefits ) ) : ?>
		<div class="igp-booking-benefits">
			<h3><?php esc_html_e( 'Why booking with IGP?', 'igp-travel-pro' ); ?></h3>
			<ul>
				<?php foreach ( $benefits as $benefit ) : ?>
					<li><?php echo esc_html( (string) $benefit ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<div class="igp-enquiry-modal" data-igp-enquiry-modal hidden>
		<div class="igp-enquiry-modal__overlay" data-igp-close-enquiry></div>

		<div class="igp-enquiry-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Make enquiry', 'igp-travel-pro' ); ?>">
			<button type="button" class="igp-enquiry-modal__close" data-igp-close-enquiry aria-label="<?php esc_attr_e( 'Close enquiry form', 'igp-travel-pro' ); ?>">×</button>

			<h3><?php esc_html_e( 'Make enquiry', 'igp-travel-pro' ); ?></h3>
			<p><?php esc_html_e( 'Have a question before booking? Message us to learn more.', 'igp-travel-pro' ); ?></p>

			<form class="igp-enquiry-form" data-igp-enquiry-form>
				<input type="hidden" name="action" value="<?php echo esc_attr( $actions['enquiry'] ?? 'igp_pro_submit_enquiry' ); ?>">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
				<input type="hidden" name="tour_id" value="<?php echo esc_attr( $tour_id ); ?>">

				<div class="igp-booking-customer">
					<label class="igp-field">
						<input type="text" name="first_name" placeholder="<?php esc_attr_e( 'First name *', 'igp-travel-pro' ); ?>" required>
					</label>

					<label class="igp-field">
						<input type="text" name="last_name" placeholder="<?php esc_attr_e( 'Last name *', 'igp-travel-pro' ); ?>" required>
					</label>

					<label class="igp-field igp-field--full">
						<input type="email" name="email" placeholder="<?php esc_attr_e( 'Email *', 'igp-travel-pro' ); ?>" required>
					</label>

					<label class="igp-field igp-field--full">
						<input type="tel" name="phone" placeholder="<?php esc_attr_e( 'Phone *', 'igp-travel-pro' ); ?>" required>
					</label>

					<label class="igp-field igp-field--full">
						<textarea name="question" rows="6" placeholder="<?php esc_attr_e( 'Your question *', 'igp-travel-pro' ); ?>" required></textarea>
					</label>
				</div>

				<p class="igp-booking-message" data-igp-form-message aria-live="polite"></p>

				<button class="igp-booking-submit" type="submit">
					<?php esc_html_e( 'Send enquiry', 'igp-travel-pro' ); ?>
				</button>
			</form>
		</div>
	</div>
</aside>