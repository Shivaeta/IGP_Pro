<?php
/**
 * Booking and enquiry engine for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

const IGP_PRO_BOOKING_POST_TYPE = 'igp_booking';

/**
 * Register Phase 4 hooks.
 */
function igp_pro_register_booking_module(): void {
	add_action( 'init', 'igp_pro_register_booking_storage', 2 );
	add_action( 'wp_enqueue_scripts', 'igp_pro_enqueue_booking_assets' );
	add_shortcode( 'igp_booking_panel', 'igp_pro_booking_panel_shortcode' );
	add_filter( 'the_content', 'igp_pro_maybe_append_booking_panel_to_tour_content', 30 );
	add_action( 'wp_ajax_igp_pro_submit_booking', 'igp_pro_ajax_submit_booking' );
	add_action( 'wp_ajax_nopriv_igp_pro_submit_booking', 'igp_pro_ajax_submit_booking' );
	add_action( 'wp_ajax_igp_pro_submit_enquiry', 'igp_pro_ajax_submit_enquiry' );
	add_action( 'wp_ajax_nopriv_igp_pro_submit_enquiry', 'igp_pro_ajax_submit_enquiry' );
	add_action( 'template_redirect', 'igp_pro_maybe_render_payment_pages' );
	add_action( 'admin_post_igp_pro_complete_mock_payment', 'igp_pro_handle_mock_payment_completion' );
	add_action( 'admin_post_nopriv_igp_pro_complete_mock_payment', 'igp_pro_handle_mock_payment_completion' );

	if ( is_admin() ) {
		add_action( 'add_meta_boxes_tour', 'igp_pro_register_tour_booking_meta_box' );
		add_action( 'save_post_tour', 'igp_pro_save_tour_booking_meta_box', 10, 2 );
	}
}

/**
 * Register internal booking/enquiry submission storage.
 */
function igp_pro_register_booking_storage(): void {
	register_post_type(
		IGP_PRO_BOOKING_POST_TYPE,
		array(
			'labels'              => array(
				'name'          => __( 'Bookings / Enquiries', 'igp-pro' ),
				'singular_name' => __( 'Booking / Enquiry', 'igp-pro' ),
			),
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'supports'            => array( 'title' ),
			'query_var'           => false,
			'rewrite'             => false,
		)
	);
}

/**
 * Enqueue booking frontend assets.
 */
function igp_pro_enqueue_booking_assets(): void {
	$css = 'assets/css/booking.css';
	$js  = 'assets/js/booking.js';

	if ( file_exists( igp_pro_path( $css ) ) ) {
		wp_enqueue_style( 'igp-pro-booking', igp_pro_url( $css ), array(), function_exists( 'igp_pro_asset_version' ) ? igp_pro_asset_version( $css ) : IGP_PRO_VERSION );
	}

	if ( file_exists( igp_pro_path( $js ) ) ) {
		wp_enqueue_script( 'igp-pro-booking', igp_pro_url( $js ), array(), function_exists( 'igp_pro_asset_version' ) ? igp_pro_asset_version( $js ) : IGP_PRO_VERSION, true );
		wp_localize_script(
			'igp-pro-booking',
			'igpProBooking',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'igp_pro_booking' ),
				'i18n'    => array(
					'networkError'     => __( 'Network error. Please try again.', 'igp-pro' ),
					'bookingCreated'   => __( 'Booking created. Redirecting to payment…', 'igp-pro' ),
					'enquiryReceived'  => __( 'Enquiry received. We will contact you shortly.', 'igp-pro' ),
					'chooseGuests'     => __( 'Please select at least one traveller.', 'igp-pro' ),
					'chooseDate'       => __( 'Please select a booking date.', 'igp-pro' ),
				),
			)
		);
	}
}

/**
 * Shortcode renderer.
 *
 * @param array $atts Shortcode attrs.
 */
function igp_pro_booking_panel_shortcode( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'tour_id' => get_the_ID(),
		),
		(array) $atts,
		'igp_booking_panel'
	);

	return igp_pro_render_booking_enquiry_panel( absint( $atts['tour_id'] ) );
}

/**
 * Append the booking widget to single tour content. JS moves it into the IGP Travel Pro sidebar when present.
 */
function igp_pro_maybe_append_booking_panel_to_tour_content( string $content ): string {
	if ( is_admin() || ! is_singular( 'tour' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	if ( false !== strpos( $content, 'data-igp-booking-panel' ) || has_shortcode( $content, 'igp_booking_panel' ) ) {
		return $content;
	}

	$panel = igp_pro_render_booking_enquiry_panel( get_the_ID() );
	if ( '' === $panel ) {
		return $content;
	}

	return $content . "\n" . $panel;
}

/**
 * Render the booking/enquiry panel.
 */
function igp_pro_render_booking_enquiry_panel( int $tour_id ): string {
	if ( $tour_id <= 0 || 'tour' !== get_post_type( $tour_id ) ) {
		return '';
	}

	$config = igp_pro_get_tour_booking_config( $tour_id );
	if ( empty( $config['enabled'] ) ) {
		return '';
	}

	$price_label = $config['base_price'] > 0 ? igp_pro_format_money( (float) $config['base_price'], (string) $config['currency'] ) : __( 'On request', 'igp-pro' );
	$today       = current_time( 'Y-m-d' );

	ob_start();
	?>
	<aside class="igp-booking-panel" data-igp-booking-panel data-tour-id="<?php echo esc_attr( $tour_id ); ?>" aria-label="<?php esc_attr_e( 'Booking and enquiry panel', 'igp-pro' ); ?>">
		<div class="igp-booking-panel__price">
			<span><?php esc_html_e( 'from', 'igp-pro' ); ?></span>
			<strong><?php echo esc_html( $price_label ); ?></strong>
			<em><?php echo esc_html( (string) $config['pricing_unit'] ); ?></em>
		</div>

		<div class="igp-booking-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Booking mode', 'igp-pro' ); ?>">
			<button type="button" class="is-active" data-igp-tab="book" role="tab" aria-selected="true"><?php esc_html_e( 'Book', 'igp-pro' ); ?></button>
			<button type="button" data-igp-tab="enquiry" role="tab" aria-selected="false"><?php esc_html_e( 'Enquiry', 'igp-pro' ); ?></button>
		</div>

		<div class="igp-booking-tab-panel is-active" data-igp-tab-panel="book" role="tabpanel">
			<form class="igp-booking-form" data-igp-booking-form>
				<input type="hidden" name="action" value="igp_pro_submit_booking">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'igp_pro_booking' ) ); ?>">
				<input type="hidden" name="tour_id" value="<?php echo esc_attr( $tour_id ); ?>">

				<label class="igp-field igp-field--full">
					<span><?php esc_html_e( 'Booking date', 'igp-pro' ); ?></span>
					<input type="date" name="booking_date" min="<?php echo esc_attr( $today ); ?>" required>
				</label>

				<label class="igp-field igp-field--full">
					<span><?php esc_html_e( 'Tour option', 'igp-pro' ); ?></span>
					<select name="tour_option" data-igp-tour-option>
						<?php foreach ( $config['options'] as $option ) : ?>
							<option value="<?php echo esc_attr( $option['id'] ); ?>" data-price="<?php echo esc_attr( (float) $option['price'] ); ?>">
								<?php echo esc_html( $option['label'] ); ?><?php echo (float) $option['price'] > 0 ? ' +' . esc_html( igp_pro_format_money( (float) $option['price'], (string) $config['currency'] ) ) : ''; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>

				<div class="igp-booking-guests" data-igp-guest-wrap>
					<p class="igp-booking-section-title"><?php esc_html_e( 'Travellers', 'igp-pro' ); ?></p>
					<?php foreach ( $config['guest_types'] as $type => $guest ) : ?>
						<div class="igp-booking-guest-row" data-igp-guest-row>
							<div>
								<strong><?php echo esc_html( $guest['label'] ); ?></strong>
								<span><?php echo esc_html( igp_pro_format_money( (float) $guest['price'], (string) $config['currency'] ) ); ?></span>
							</div>
							<div class="igp-quantity" data-igp-quantity>
								<button type="button" data-igp-qty-minus aria-label="<?php esc_attr_e( 'Decrease quantity', 'igp-pro' ); ?>">−</button>
								<input type="number" min="0" max="99" value="0" name="<?php echo esc_attr( $type ); ?>_qty" data-price="<?php echo esc_attr( (float) $guest['price'] ); ?>">
								<button type="button" data-igp-qty-plus aria-label="<?php esc_attr_e( 'Increase quantity', 'igp-pro' ); ?>">+</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( ! empty( $config['addons'] ) ) : ?>
					<div class="igp-booking-addons">
						<p class="igp-booking-section-title"><?php esc_html_e( 'Add-ons', 'igp-pro' ); ?></p>
						<?php foreach ( $config['addons'] as $addon ) : ?>
							<label class="igp-addon">
								<input type="checkbox" name="addons[]" value="<?php echo esc_attr( $addon['id'] ); ?>" data-price="<?php echo esc_attr( (float) $addon['price'] ); ?>">
								<span><strong><?php echo esc_html( $addon['label'] ); ?></strong><?php if ( '' !== $addon['description'] ) : ?><small><?php echo esc_html( $addon['description'] ); ?></small><?php endif; ?></span>
								<em><?php echo esc_html( igp_pro_format_money( (float) $addon['price'], (string) $config['currency'] ) ); ?></em>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="igp-booking-customer">
					<p class="igp-booking-section-title"><?php esc_html_e( 'Contact details', 'igp-pro' ); ?></p>
					<label class="igp-field"><span><?php esc_html_e( 'First name', 'igp-pro' ); ?></span><input type="text" name="first_name" required></label>
					<label class="igp-field"><span><?php esc_html_e( 'Last name', 'igp-pro' ); ?></span><input type="text" name="last_name" required></label>
					<label class="igp-field"><span><?php esc_html_e( 'Email', 'igp-pro' ); ?></span><input type="email" name="email" required></label>
					<label class="igp-field"><span><?php esc_html_e( 'Phone', 'igp-pro' ); ?></span><input type="tel" name="phone" required></label>
				</div>

				<div class="igp-booking-total" data-currency="<?php echo esc_attr( (string) $config['currency'] ); ?>">
					<span><?php esc_html_e( 'Total', 'igp-pro' ); ?></span>
					<strong data-igp-total><?php echo esc_html( igp_pro_format_money( 0, (string) $config['currency'] ) ); ?></strong>
				</div>

				<p class="igp-booking-message" data-igp-form-message aria-live="polite"></p>
				<button class="igp-booking-submit" type="submit"><?php esc_html_e( 'Book now', 'igp-pro' ); ?></button>
			</form>
		</div>

		<div class="igp-booking-tab-panel" data-igp-tab-panel="enquiry" role="tabpanel" hidden>
			<p class="igp-booking-intro"><?php esc_html_e( 'Have a question before booking? Message us to learn more.', 'igp-pro' ); ?></p>
			<form class="igp-enquiry-form" data-igp-enquiry-form>
				<input type="hidden" name="action" value="igp_pro_submit_enquiry">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'igp_pro_booking' ) ); ?>">
				<input type="hidden" name="tour_id" value="<?php echo esc_attr( $tour_id ); ?>">
				<div class="igp-booking-customer">
					<label class="igp-field"><span><?php esc_html_e( 'First name', 'igp-pro' ); ?></span><input type="text" name="first_name" required></label>
					<label class="igp-field"><span><?php esc_html_e( 'Last name', 'igp-pro' ); ?></span><input type="text" name="last_name" required></label>
					<label class="igp-field igp-field--full"><span><?php esc_html_e( 'Email', 'igp-pro' ); ?></span><input type="email" name="email" required></label>
					<label class="igp-field igp-field--full"><span><?php esc_html_e( 'Phone', 'igp-pro' ); ?></span><input type="tel" name="phone" required></label>
					<label class="igp-field igp-field--full"><span><?php esc_html_e( 'Your question', 'igp-pro' ); ?></span><textarea name="question" rows="5" required></textarea></label>
				</div>
				<p class="igp-booking-message" data-igp-form-message aria-live="polite"></p>
				<button class="igp-booking-submit" type="submit"><?php esc_html_e( 'Send enquiry', 'igp-pro' ); ?></button>
			</form>
		</div>
	</aside>
	<?php
	return (string) ob_get_clean();
}

/**
 * Verify frontend nonce.
 */
function igp_pro_verify_booking_nonce() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'igp_pro_booking' ) ) {
		return new WP_Error( 'igp_pro_invalid_nonce', __( 'Security check failed. Please refresh and try again.', 'igp-pro' ) );
	}

	return true;
}

/**
 * Send frontend WP_Error payload.
 */
function igp_pro_send_booking_error( WP_Error $error, int $status = 400 ): void {
	wp_send_json_error(
		array(
			'code'    => $error->get_error_code(),
			'message' => $error->get_error_message(),
		),
		$status
	);
}

/**
 * Sanitize contact fields from request.
 */
function igp_pro_sanitize_contact_request( array $request ) {
	$contact = array(
		'first_name' => isset( $request['first_name'] ) ? sanitize_text_field( (string) wp_unslash( $request['first_name'] ) ) : '',
		'last_name'  => isset( $request['last_name'] ) ? sanitize_text_field( (string) wp_unslash( $request['last_name'] ) ) : '',
		'email'      => isset( $request['email'] ) ? sanitize_email( (string) wp_unslash( $request['email'] ) ) : '',
		'phone'      => isset( $request['phone'] ) ? sanitize_text_field( (string) wp_unslash( $request['phone'] ) ) : '',
	);

	if ( '' === $contact['first_name'] || '' === $contact['email'] || '' === $contact['phone'] ) {
		return new WP_Error( 'igp_pro_missing_contact', __( 'Please complete the required contact fields.', 'igp-pro' ) );
	}

	if ( ! is_email( $contact['email'] ) ) {
		return new WP_Error( 'igp_pro_invalid_email', __( 'Please enter a valid email address.', 'igp-pro' ) );
	}

	return $contact;
}

/**
 * Store a booking/enquiry submission.
 */
function igp_pro_store_submission( string $type, int $tour_id, array $contact, array $payload, array $pricing = array() ) {
	$type   = 'enquiry' === $type ? 'enquiry' : 'booking';
	$status = 'booking' === $type ? 'pending_payment' : 'received';
	$title  = sprintf(
		/* translators: 1: type, 2: tour title, 3: customer name. */
		__( '%1$s: %2$s — %3$s', 'igp-pro' ),
		ucfirst( $type ),
		get_the_title( $tour_id ),
		trim( $contact['first_name'] . ' ' . $contact['last_name'] )
	);

	$post_id = wp_insert_post(
		array(
			'post_type'   => IGP_PRO_BOOKING_POST_TYPE,
			'post_status' => 'publish',
			'post_title'  => wp_strip_all_tags( $title ),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	update_post_meta( $post_id, '_igp_submission_type', $type );
	update_post_meta( $post_id, '_igp_submission_status', $status );
	update_post_meta( $post_id, '_igp_tour_id', $tour_id );
	update_post_meta( $post_id, '_igp_customer_first_name', $contact['first_name'] );
	update_post_meta( $post_id, '_igp_customer_last_name', $contact['last_name'] );
	update_post_meta( $post_id, '_igp_customer_email', $contact['email'] );
	update_post_meta( $post_id, '_igp_customer_phone', $contact['phone'] );
	update_post_meta( $post_id, '_igp_submission_payload', wp_json_encode( $payload ) );
	update_post_meta( $post_id, '_igp_submission_pricing', wp_json_encode( $pricing ) );
	update_post_meta( $post_id, '_igp_submission_created_at', current_time( 'mysql' ) );

	if ( 'booking' === $type ) {
		update_post_meta( $post_id, '_igp_payment_key', wp_generate_password( 32, false, false ) );
		update_post_meta( $post_id, '_igp_total_amount', isset( $pricing['total'] ) ? (float) $pricing['total'] : 0.0 );
		update_post_meta( $post_id, '_igp_currency', $pricing['currency'] ?? '₹' );
	}

	return $post_id;
}

/**
 * AJAX: booking submission.
 */
function igp_pro_ajax_submit_booking(): void {
	$nonce = igp_pro_verify_booking_nonce();
	if ( is_wp_error( $nonce ) ) {
		igp_pro_send_booking_error( $nonce, 403 );
	}

	$tour_id = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;
	$contact = igp_pro_sanitize_contact_request( $_POST );
	if ( is_wp_error( $contact ) ) {
		igp_pro_send_booking_error( $contact );
	}

	$booking_date = isset( $_POST['booking_date'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['booking_date'] ) ) : '';
	if ( '' === $booking_date ) {
		igp_pro_send_booking_error( new WP_Error( 'igp_pro_missing_date', __( 'Please select a booking date.', 'igp-pro' ) ) );
	}

	$pricing = igp_pro_calculate_booking_total( $tour_id, $_POST );
	if ( is_wp_error( $pricing ) ) {
		igp_pro_send_booking_error( $pricing );
	}

	$payload = array(
		'booking_date' => $booking_date,
		'tour_option'  => isset( $_POST['tour_option'] ) ? sanitize_key( (string) wp_unslash( $_POST['tour_option'] ) ) : '',
		'addons'       => isset( $_POST['addons'] ) ? array_map( 'sanitize_key', array_map( 'strval', (array) wp_unslash( $_POST['addons'] ) ) ) : array(),
		'guests'       => $pricing['guests'],
	);

	$booking_id = igp_pro_store_submission( 'booking', $tour_id, $contact, $payload, $pricing );
	if ( is_wp_error( $booking_id ) ) {
		igp_pro_send_booking_error( $booking_id );
	}

	$adapter = igp_pro_get_payment_adapter();
	if ( is_wp_error( $adapter ) ) {
		igp_pro_send_booking_error( $adapter );
	}

	$payment_url = $adapter->create_payment_url( (int) $booking_id, $payload, $pricing );
	if ( is_wp_error( $payment_url ) ) {
		igp_pro_send_booking_error( $payment_url );
	}

	update_post_meta( (int) $booking_id, '_igp_payment_adapter', $adapter->get_id() );

	wp_send_json_success(
		array(
			'booking_id'   => (int) $booking_id,
			'redirect_url' => esc_url_raw( $payment_url ),
			'status'       => 'pending_payment',
			'message'      => __( 'Booking created. Redirecting to payment.', 'igp-pro' ),
		)
	);
}

/**
 * AJAX: enquiry submission.
 */
function igp_pro_ajax_submit_enquiry(): void {
	$nonce = igp_pro_verify_booking_nonce();
	if ( is_wp_error( $nonce ) ) {
		igp_pro_send_booking_error( $nonce, 403 );
	}

	$tour_id = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;
	if ( $tour_id <= 0 || 'tour' !== get_post_type( $tour_id ) ) {
		igp_pro_send_booking_error( new WP_Error( 'igp_pro_invalid_tour', __( 'Invalid tour selected.', 'igp-pro' ) ) );
	}

	$contact = igp_pro_sanitize_contact_request( $_POST );
	if ( is_wp_error( $contact ) ) {
		igp_pro_send_booking_error( $contact );
	}

	$question = isset( $_POST['question'] ) ? sanitize_textarea_field( (string) wp_unslash( $_POST['question'] ) ) : '';
	if ( '' === $question ) {
		igp_pro_send_booking_error( new WP_Error( 'igp_pro_missing_question', __( 'Please enter your question.', 'igp-pro' ) ) );
	}

	$payload = array(
		'question' => $question,
	);

	$enquiry_id = igp_pro_store_submission( 'enquiry', $tour_id, $contact, $payload );
	if ( is_wp_error( $enquiry_id ) ) {
		igp_pro_send_booking_error( $enquiry_id );
	}

	wp_send_json_success(
		array(
			'enquiry_id' => (int) $enquiry_id,
			'status'     => 'received',
			'message'    => __( 'Enquiry received. We will contact you shortly.', 'igp-pro' ),
		)
	);
}

/**
 * Validate public booking key.
 */
function igp_pro_validate_public_booking_key( int $booking_id, string $key ): bool {
	if ( $booking_id <= 0 || IGP_PRO_BOOKING_POST_TYPE !== get_post_type( $booking_id ) ) {
		return false;
	}

	$saved = (string) get_post_meta( $booking_id, '_igp_payment_key', true );
	return '' !== $saved && hash_equals( $saved, $key );
}

/**
 * Maybe render local checkout or confirmation pages.
 */
function igp_pro_maybe_render_payment_pages(): void {
	$checkout_id = isset( $_GET['igp_pro_checkout'] ) ? absint( $_GET['igp_pro_checkout'] ) : 0;
	$confirm_id  = isset( $_GET['igp_pro_confirmation'] ) ? absint( $_GET['igp_pro_confirmation'] ) : 0;
	$key         = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

	if ( $checkout_id > 0 ) {
		if ( ! igp_pro_validate_public_booking_key( $checkout_id, $key ) ) {
			status_header( 403 );
			wp_die( esc_html__( 'Invalid payment link.', 'igp-pro' ) );
		}
		igp_pro_render_checkout_page( $checkout_id, $key );
		exit;
	}

	if ( $confirm_id > 0 ) {
		if ( ! igp_pro_validate_public_booking_key( $confirm_id, $key ) ) {
			status_header( 403 );
			wp_die( esc_html__( 'Invalid confirmation link.', 'igp-pro' ) );
		}
		igp_pro_render_confirmation_page( $confirm_id );
		exit;
	}
}

/**
 * Decode JSON post meta arrays.
 */
function igp_pro_get_submission_json_meta( int $post_id, string $key ): array {
	$value = (string) get_post_meta( $post_id, $key, true );
	$data  = json_decode( $value, true );
	return is_array( $data ) ? $data : array();
}

/**
 * Render local checkout page.
 */
function igp_pro_render_checkout_page( int $booking_id, string $key ): void {
	$tour_id = absint( get_post_meta( $booking_id, '_igp_tour_id', true ) );
	$pricing = igp_pro_get_submission_json_meta( $booking_id, '_igp_submission_pricing' );
	$status  = (string) get_post_meta( $booking_id, '_igp_submission_status', true );

	get_header();
	?>
	<main class="igp-checkout-page">
		<section class="igp-checkout-card">
			<p class="igp-checkout-eyebrow"><?php esc_html_e( 'Secure payment', 'igp-pro' ); ?></p>
			<h1><?php esc_html_e( 'Complete your booking', 'igp-pro' ); ?></h1>
			<p><?php echo esc_html( get_the_title( $tour_id ) ); ?></p>
			<div class="igp-checkout-summary">
				<span><?php esc_html_e( 'Booking ID', 'igp-pro' ); ?></span><strong>#<?php echo esc_html( (string) $booking_id ); ?></strong>
				<span><?php esc_html_e( 'Status', 'igp-pro' ); ?></span><strong><?php echo esc_html( igp_pro_format_submission_status( $status ) ); ?></strong>
				<span><?php esc_html_e( 'Amount payable', 'igp-pro' ); ?></span><strong><?php echo esc_html( isset( $pricing['formatted'] ) ? (string) $pricing['formatted'] : igp_pro_format_money( (float) get_post_meta( $booking_id, '_igp_total_amount', true ), (string) get_post_meta( $booking_id, '_igp_currency', true ) ) ); ?></strong>
			</div>
			<?php if ( 'confirmed' === $status ) : ?>
				<p class="igp-checkout-success"><?php esc_html_e( 'This booking is already confirmed.', 'igp-pro' ); ?></p>
			<?php else : ?>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="igp_pro_complete_mock_payment">
					<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking_id ); ?>">
					<input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>">
					<?php wp_nonce_field( 'igp_pro_complete_mock_payment_' . $booking_id ); ?>
					<button class="igp-booking-submit igp-booking-submit--large" type="submit"><?php esc_html_e( 'Pay now', 'igp-pro' ); ?></button>
				</form>
				<p class="igp-checkout-note"><?php esc_html_e( 'Phase 4 uses the local payment gateway so booking state, payment handoff, and confirmation can be validated before live gateway credentials are connected.', 'igp-pro' ); ?></p>
			<?php endif; ?>
		</section>
	</main>
	<?php
	get_footer();
}

/**
 * Handle local payment confirmation.
 */
function igp_pro_handle_mock_payment_completion(): void {
	$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
	$key        = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';

	if ( ! wp_verify_nonce( isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '', 'igp_pro_complete_mock_payment_' . $booking_id ) ) {
		wp_die( esc_html__( 'Security check failed.', 'igp-pro' ) );
	}

	if ( ! igp_pro_validate_public_booking_key( $booking_id, $key ) ) {
		wp_die( esc_html__( 'Invalid payment link.', 'igp-pro' ) );
	}

	update_post_meta( $booking_id, '_igp_submission_status', 'confirmed' );
	update_post_meta( $booking_id, '_igp_payment_completed_at', current_time( 'mysql' ) );
	update_post_meta( $booking_id, '_igp_transaction_id', 'IGP-LOCAL-' . strtoupper( wp_generate_password( 10, false, false ) ) );

	wp_safe_redirect(
		add_query_arg(
			array(
				'igp_pro_confirmation' => $booking_id,
				'key'                  => rawurlencode( $key ),
			),
			home_url( '/' )
		)
	);
	exit;
}

/**
 * Render confirmation page.
 */
function igp_pro_render_confirmation_page( int $booking_id ): void {
	$tour_id = absint( get_post_meta( $booking_id, '_igp_tour_id', true ) );
	$pricing = igp_pro_get_submission_json_meta( $booking_id, '_igp_submission_pricing' );
	$txn     = (string) get_post_meta( $booking_id, '_igp_transaction_id', true );

	get_header();
	?>
	<main class="igp-checkout-page">
		<section class="igp-checkout-card igp-checkout-card--success">
			<p class="igp-checkout-eyebrow"><?php esc_html_e( 'Booking confirmed', 'igp-pro' ); ?></p>
			<h1><?php esc_html_e( 'Payment received', 'igp-pro' ); ?></h1>
			<p><?php esc_html_e( 'Your booking has been confirmed. The site administrator can inspect this record in the Booking / Enquiry panel.', 'igp-pro' ); ?></p>
			<div class="igp-checkout-summary">
				<span><?php esc_html_e( 'Tour', 'igp-pro' ); ?></span><strong><?php echo esc_html( get_the_title( $tour_id ) ); ?></strong>
				<span><?php esc_html_e( 'Booking ID', 'igp-pro' ); ?></span><strong>#<?php echo esc_html( (string) $booking_id ); ?></strong>
				<span><?php esc_html_e( 'Transaction', 'igp-pro' ); ?></span><strong><?php echo esc_html( $txn ); ?></strong>
				<span><?php esc_html_e( 'Paid amount', 'igp-pro' ); ?></span><strong><?php echo esc_html( isset( $pricing['formatted'] ) ? (string) $pricing['formatted'] : '' ); ?></strong>
			</div>
			<a class="igp-booking-submit igp-booking-submit--link" href="<?php echo esc_url( get_permalink( $tour_id ) ); ?>"><?php esc_html_e( 'Back to tour', 'igp-pro' ); ?></a>
		</section>
	</main>
	<?php
	get_footer();
}

/**
 * Register tour booking settings metabox.
 */
function igp_pro_register_tour_booking_meta_box(): void {
	add_meta_box(
		'igp-pro-tour-booking-settings',
		__( 'IGP Booking Settings', 'igp-pro' ),
		'igp_pro_render_tour_booking_meta_box',
		'tour',
		'normal',
		'default'
	);
}

/**
 * Render tour booking settings metabox.
 */
function igp_pro_render_tour_booking_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'igp_pro_save_tour_booking_meta', 'igp_pro_booking_meta_nonce' );
	$config = igp_pro_get_tour_booking_config( $post->ID );
	$options_json = get_post_meta( $post->ID, '_igp_booking_options', true );
	$addons_json  = get_post_meta( $post->ID, '_igp_booking_addons', true );
	?>
	<div class="igp-booking-meta-box">
		<p><label><input type="checkbox" name="igp_booking_enabled" value="1" <?php checked( $config['enabled'] ); ?>> <?php esc_html_e( 'Enable booking/enquiry panel for this tour', 'igp-pro' ); ?></label></p>
		<p class="description"><?php esc_html_e( 'The panel appears automatically on single Tour pages and can also be inserted with [igp_booking_panel].', 'igp-pro' ); ?></p>
		<div class="igp-booking-meta-grid">
			<label><?php esc_html_e( 'Base price', 'igp-pro' ); ?><input type="number" step="0.01" min="0" name="igp_booking_base_price" value="<?php echo esc_attr( (string) $config['base_price'] ); ?>"></label>
			<label><?php esc_html_e( 'Currency symbol', 'igp-pro' ); ?><input type="text" name="igp_booking_currency" value="<?php echo esc_attr( (string) $config['currency'] ); ?>"></label>
			<label><?php esc_html_e( 'Pricing unit', 'igp-pro' ); ?><input type="text" name="igp_booking_pricing_unit" value="<?php echo esc_attr( (string) $config['pricing_unit'] ); ?>"></label>
			<label><?php esc_html_e( 'Senior price', 'igp-pro' ); ?><input type="number" step="0.01" min="0" name="igp_senior_price" value="<?php echo esc_attr( (string) $config['guest_types']['senior']['price'] ); ?>"></label>
			<label><?php esc_html_e( 'Adult price', 'igp-pro' ); ?><input type="number" step="0.01" min="0" name="igp_adult_price" value="<?php echo esc_attr( (string) $config['guest_types']['adult']['price'] ); ?>"></label>
			<label><?php esc_html_e( 'Children price', 'igp-pro' ); ?><input type="number" step="0.01" min="0" name="igp_child_price" value="<?php echo esc_attr( (string) $config['guest_types']['children']['price'] ); ?>"></label>
		</div>
		<label class="igp-booking-meta-textarea"><?php esc_html_e( 'Tour options JSON', 'igp-pro' ); ?>
			<textarea name="igp_booking_options" rows="5" spellcheck="false"><?php echo esc_textarea( (string) $options_json ); ?></textarea>
		</label>
		<p class="description"><code>[{"id":"standard","label":"Standard","price":0},{"id":"premium","label":"Premium","price":2500}]</code></p>
		<label class="igp-booking-meta-textarea"><?php esc_html_e( 'Add-ons JSON', 'igp-pro' ); ?>
			<textarea name="igp_booking_addons" rows="5" spellcheck="false"><?php echo esc_textarea( (string) $addons_json ); ?></textarea>
		</label>
		<p class="description"><code>[{"id":"pickup","label":"Airport pickup","price":1200,"description":"Private pickup"}]</code></p>
	</div>
	<?php
}

/**
 * Save tour booking settings.
 */
function igp_pro_save_tour_booking_meta_box( int $post_id, WP_Post $post ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! isset( $_POST['igp_pro_booking_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igp_pro_booking_meta_nonce'] ) ), 'igp_pro_save_tour_booking_meta' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_igp_booking_enabled', isset( $_POST['igp_booking_enabled'] ) ? 'yes' : 'no' );
	update_post_meta( $post_id, '_igp_booking_base_price', isset( $_POST['igp_booking_base_price'] ) ? igp_pro_parse_money( wp_unslash( $_POST['igp_booking_base_price'] ) ) : 0 );
	update_post_meta( $post_id, '_igp_booking_currency', isset( $_POST['igp_booking_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['igp_booking_currency'] ) ) : '₹' );
	update_post_meta( $post_id, '_igp_booking_pricing_unit', isset( $_POST['igp_booking_pricing_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['igp_booking_pricing_unit'] ) ) : '/person' );
	update_post_meta( $post_id, '_igp_senior_price', isset( $_POST['igp_senior_price'] ) ? igp_pro_parse_money( wp_unslash( $_POST['igp_senior_price'] ) ) : 0 );
	update_post_meta( $post_id, '_igp_adult_price', isset( $_POST['igp_adult_price'] ) ? igp_pro_parse_money( wp_unslash( $_POST['igp_adult_price'] ) ) : 0 );
	update_post_meta( $post_id, '_igp_child_price', isset( $_POST['igp_child_price'] ) ? igp_pro_parse_money( wp_unslash( $_POST['igp_child_price'] ) ) : 0 );

	foreach ( array( '_igp_booking_options' => 'igp_booking_options', '_igp_booking_addons' => 'igp_booking_addons' ) as $meta_key => $field_name ) {
		$raw = isset( $_POST[ $field_name ] ) ? trim( (string) wp_unslash( $_POST[ $field_name ] ) ) : '';
		if ( '' === $raw ) {
			delete_post_meta( $post_id, $meta_key );
			continue;
		}

		$decoded = json_decode( $raw, true );
		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			update_post_meta( $post_id, $meta_key, wp_json_encode( $decoded ) );
		}
	}
}

/**
 * Human status label helper shared with admin panel.
 */
function igp_pro_format_submission_status( string $status ): string {
	$labels = array(
		'pending_payment' => __( 'Pending payment', 'igp-pro' ),
		'confirmed'       => __( 'Confirmed', 'igp-pro' ),
		'failed'          => __( 'Failed', 'igp-pro' ),
		'cancelled'       => __( 'Cancelled', 'igp-pro' ),
		'received'        => __( 'Received', 'igp-pro' ),
		'contacted'       => __( 'Contacted', 'igp-pro' ),
		'converted'       => __( 'Converted', 'igp-pro' ),
		'closed'          => __( 'Closed', 'igp-pro' ),
	);

	return $labels[ $status ] ?? ucwords( str_replace( '_', ' ', sanitize_key( $status ) ) );
}
