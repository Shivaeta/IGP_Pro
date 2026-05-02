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
	add_action( 'wp_ajax_igp_pro_complete_checkout', 'igp_pro_ajax_complete_checkout' );
	add_action( 'wp_ajax_nopriv_igp_pro_complete_checkout', 'igp_pro_ajax_complete_checkout' );
	add_action( 'wp_ajax_igp_pro_submit_enquiry', 'igp_pro_ajax_submit_enquiry' );
	add_action( 'wp_ajax_nopriv_igp_pro_submit_enquiry', 'igp_pro_ajax_submit_enquiry' );
	add_action( 'template_redirect', 'igp_pro_maybe_render_payment_pages' );
	add_action( 'init', 'igp_pro_register_booking_panel_block', 20 );
	add_action( 'enqueue_block_editor_assets', 'igp_pro_enqueue_booking_panel_block_editor_assets' );
	add_action( 'admin_post_igp_pro_complete_mock_payment', 'igp_pro_handle_mock_payment_completion' );
	add_action( 'admin_post_nopriv_igp_pro_complete_mock_payment', 'igp_pro_handle_mock_payment_completion' );

	if ( is_admin() ) {
		add_action( 'add_meta_boxes_tour', 'igp_pro_register_tour_booking_meta_box' );
		add_action( 'save_post_tour', 'igp_pro_save_tour_booking_meta_box', 10, 2 );
		add_action( 'admin_enqueue_scripts', 'igp_pro_enqueue_tour_booking_settings_assets' );
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
 * Register the booking panel as a dynamic Gutenberg block.
 *
 * The block uses the same booking engine as the automatic single-tour panel and shortcode,
 * so visual placement can be controlled without duplicating booking logic.
 */
function igp_pro_register_booking_panel_block(): void {
	if ( ! function_exists( 'register_block_type' ) || ! class_exists( 'WP_Block_Type_Registry' ) ) {
		return;
	}

	$block_name = 'igp-pro/booking-panel';
	if ( WP_Block_Type_Registry::get_instance()->is_registered( $block_name ) ) {
		return;
	}

	register_block_type(
		$block_name,
		array(
			'api_version'     => 2,
			'title'           => __( 'IGP Booking Form', 'igp-pro' ),
			'description'     => __( 'Dynamic booking/enquiry panel for Tour pages.', 'igp-pro' ),
			'category'        => 'widgets',
			'icon'            => 'tickets-alt',
			'attributes'      => array(
				'tour_id' => array(
					'type'    => 'number',
					'default' => 0,
				),
			),
			'supports'        => array(
				'html' => false,
			),
			'render_callback' => static function ( array $attributes = array() ): string {
				$tour_id = isset( $attributes['tour_id'] ) ? absint( $attributes['tour_id'] ) : 0;
				if ( $tour_id <= 0 ) {
					$tour_id = get_the_ID();
				}
				return igp_pro_render_booking_enquiry_panel( $tour_id );
			},
		)
	);
}


/**
 * Register a minimal editor-side client block for the dynamic booking panel.
 */
function igp_pro_enqueue_booking_panel_block_editor_assets(): void {
	if ( ! function_exists( 'wp_register_script' ) || ! function_exists( 'wp_add_inline_script' ) ) {
		return;
	}

	$handle = 'igp-pro-booking-panel-block-editor';
	wp_register_script(
		$handle,
		false,
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
		IGP_PRO_VERSION,
		true
	);
	wp_enqueue_script( $handle );

	wp_add_inline_script(
		$handle,
		<<<'JS'
(function (blocks, blockEditor, components, element, i18n, ServerSideRender) {
	if (!blocks || !blockEditor || !components || !element || !i18n) return;
	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	ServerSideRender = ServerSideRender && (ServerSideRender.default || ServerSideRender);
	if (blocks.getBlockType && blocks.getBlockType('igp-pro/booking-panel')) return;
	blocks.registerBlockType('igp-pro/booking-panel', {
		apiVersion: 2,
		title: __('IGP Booking Form', 'igp-pro'),
		description: __('Dynamic booking/enquiry form for Tour pages.', 'igp-pro'),
		icon: 'tickets-alt',
		category: 'widgets',
		attributes: { tour_id: { type: 'number', default: 0 } },
		edit: function (props) {
			var blockProps = useBlockProps({ className: 'igp-booking-panel-block-editor' });
			return el('div', blockProps,
				el(InspectorControls, {},
					el(PanelBody, { title: __('Booking Form Settings', 'igp-pro'), initialOpen: true },
						el(TextControl, {
							label: __('Tour ID override', 'igp-pro'),
							help: __('Leave as 0 to use the current Tour page.', 'igp-pro'),
							type: 'number',
							value: props.attributes.tour_id || 0,
							onChange: function (value) { props.setAttributes({ tour_id: Number(value) || 0 }); }
						})
					)
				),
				ServerSideRender ? el(ServerSideRender, { block: 'igp-pro/booking-panel', attributes: props.attributes }) : el('p', {}, __('IGP Booking Form', 'igp-pro'))
			);
		},
		save: function () { return null; }
	});
})(window.wp && window.wp.blocks, window.wp && window.wp.blockEditor, window.wp && window.wp.components, window.wp && window.wp.element, window.wp && window.wp.i18n, window.wp && window.wp.serverSideRender);
JS
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
					'chooseDate'       => __( 'Please select a tour date.', 'igp-pro' ),
					'travelersSummary' => __( '%d traveler(s)', 'igp-pro' ),
				),
			)
		);
	}
}

/**
 * Enqueue Tour edit screen booking settings helper.
 */
function igp_pro_enqueue_tour_booking_settings_assets( string $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'tour' !== $screen->post_type ) {
		return;
	}

	$css = 'assets/css/booking-admin.css';
	$js  = 'assets/js/booking-admin.js';

	if ( file_exists( igp_pro_path( $css ) ) ) {
		wp_enqueue_style( 'igp-pro-booking-admin', igp_pro_url( $css ), array(), function_exists( 'igp_pro_asset_version' ) ? igp_pro_asset_version( $css ) : IGP_PRO_VERSION );
	}

	if ( file_exists( igp_pro_path( $js ) ) ) {
		wp_enqueue_script( 'igp-pro-booking-admin', igp_pro_url( $js ), array(), function_exists( 'igp_pro_asset_version' ) ? igp_pro_asset_version( $js ) : IGP_PRO_VERSION, true );
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

	if ( false !== strpos( $content, 'data-igp-booking-panel' ) || has_shortcode( $content, 'igp_booking_panel' ) || has_block( 'igp-pro/booking-panel', get_post() ) ) {
		return $content;
	}

	$panel = igp_pro_render_booking_enquiry_panel( get_the_ID() );
	if ( '' === $panel ) {
		return $content;
	}

	return $content . "\n" . $panel;
}

/**
 * Return default trust/booking benefits for the sidebar card.
 *
 * @return array<int,string>
 */
function igp_pro_get_booking_benefits(): array {
	return array(
		__( 'Free cancellation up to 24 hours', 'igp-pro' ),
		__( 'Trusted by verified travelers', 'igp-pro' ),
		__( 'Secure booking and payment handoff', 'igp-pro' ),
		__( '24-hour support', 'igp-pro' ),
	);
}

/**
 * Render one traveler quantity row.
 */
function igp_pro_render_guest_quantity_row( string $type, array $guest, string $currency ): void {
	?>
	<div class="igp-booking-guest-row" data-igp-guest-row>
		<div class="igp-booking-guest-copy">
			<strong><?php echo esc_html( $guest['label'] ); ?></strong>
			<?php if ( ! empty( $guest['description'] ) ) : ?>
				<span><?php echo esc_html( (string) $guest['description'] ); ?></span>
			<?php endif; ?>
		</div>
		<div class="igp-quantity" data-igp-quantity>
			<button type="button" data-igp-qty-minus aria-label="<?php esc_attr_e( 'Decrease quantity', 'igp-pro' ); ?>">−</button>
			<input type="number" min="0" max="9999" value="0" name="guest_qty[<?php echo esc_attr( $type ); ?>]" data-price="<?php echo esc_attr( (float) $guest['price'] ); ?>" aria-label="<?php echo esc_attr( $guest['label'] ); ?>">
			<button type="button" data-igp-qty-plus aria-label="<?php esc_attr_e( 'Increase quantity', 'igp-pro' ); ?>">+</button>
		</div>
	</div>
	<?php
}


/**
 * Echo a form attribute for detached booking controls.
 */
function igp_pro_echo_form_attr( string $form_id ): void {
	if ( '' !== $form_id ) {
		echo ' form="' . esc_attr( $form_id ) . '"';
	}
}

/**
 * Render one add-on quantity row.
 */
function igp_pro_render_addon_quantity_row( array $addon, string $currency, string $form_id = '' ): void {
	$addon_id = sanitize_key( (string) $addon['id'] );
	?>
	<div class="igp-addon-row" data-igp-addon-row>
		<label class="igp-addon-check">
			<input type="checkbox" name="addons[]" value="<?php echo esc_attr( $addon_id ); ?>" data-igp-addon-check data-price="<?php echo esc_attr( (float) $addon['price'] ); ?>"<?php igp_pro_echo_form_attr( $form_id ); ?>>
			<span class="screen-reader-text"><?php echo esc_html( $addon['label'] ); ?></span>
		</label>
		<div class="igp-addon-copy">
			<strong><?php echo esc_html( $addon['label'] ); ?></strong>
			<?php if ( '' !== (string) $addon['description'] ) : ?>
				<span><?php echo esc_html( (string) $addon['description'] ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( ! empty( $addon['included'] ) || ! empty( $addon['excluded'] ) ) : ?>
			<div class="igp-addon-scope">
				<?php if ( ! empty( $addon['included'] ) ) : ?>
					<span><strong><?php esc_html_e( 'Included:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) $addon['included'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $addon['excluded'] ) ) : ?>
					<span><strong><?php esc_html_e( 'Excluded:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) $addon['excluded'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<div class="igp-addon-price"><?php echo esc_html( igp_pro_format_money( (float) $addon['price'], $currency ) ); ?></div>
		<div class="igp-quantity igp-quantity--small" data-igp-quantity>
			<button type="button" data-igp-qty-minus aria-label="<?php esc_attr_e( 'Decrease add-on quantity', 'igp-pro' ); ?>">−</button>
			<input type="number" min="0" max="9999" value="0" name="addon_qty[<?php echo esc_attr( $addon_id ); ?>]" data-price="<?php echo esc_attr( (float) $addon['price'] ); ?>" data-igp-addon-qty aria-label="<?php echo esc_attr( $addon['label'] ); ?>"<?php igp_pro_echo_form_attr( $form_id ); ?>>
			<button type="button" data-igp-qty-plus aria-label="<?php esc_attr_e( 'Increase add-on quantity', 'igp-pro' ); ?>">+</button>
		</div>
	</div>
	<?php
}

/**
 * Render the date picker shell. JavaScript populates the visible calendar.
 */
function igp_pro_render_booking_date_picker( string $currency, float $base_price ): void {
	?>
	<div class="igp-booking-picker igp-booking-picker--date" data-igp-date-picker>
		<button type="button" class="igp-booking-choice" data-igp-date-toggle aria-expanded="false">
			<span class="igp-booking-choice__icon" aria-hidden="true">▣</span>
			<strong data-igp-date-label><?php esc_html_e( 'Date', 'igp-pro' ); ?></strong>
		</button>
		<input type="hidden" name="tour_date" value="" required data-igp-tour-date>
		<div class="igp-booking-date-popover" data-igp-date-popover hidden data-currency="<?php echo esc_attr( $currency ); ?>" data-price="<?php echo esc_attr( $base_price ); ?>">
			<div class="igp-date-calendar" data-igp-date-calendar></div>
		</div>
	</div>
	<?php
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

	$price_label   = $config['base_price'] > 0 ? igp_pro_format_money( (float) $config['base_price'], (string) $config['currency'] ) : __( 'On request', 'igp-pro' );
	$compare_label = ! empty( $config['compare_price'] ) && (float) $config['compare_price'] > (float) $config['base_price'] ? igp_pro_format_money( (float) $config['compare_price'], (string) $config['currency'] ) : '';
	$booking_date  = current_time( 'Y-m-d' );
	$form_mode     = isset( $config['form_mode'] ) && 'enquiry_only' === $config['form_mode'] ? 'enquiry_only' : 'booking_enquiry';
	$unit_label    = (string) $config['pricing_unit'];
	$form_id       = 'igp-booking-form-' . absint( $tour_id );

	ob_start();
	?>
	<aside class="igp-booking-shell" data-igp-booking-panel data-tour-id="<?php echo esc_attr( $tour_id ); ?>" data-form-mode="<?php echo esc_attr( $form_mode ); ?>" aria-label="<?php esc_attr_e( 'Booking and enquiry panel', 'igp-pro' ); ?>">
		<div class="igp-booking-panel">
			<div class="igp-booking-price-row">
				<div class="igp-booking-panel__price">
					<span><?php esc_html_e( 'from', 'igp-pro' ); ?></span>
					<div>
						<?php if ( '' !== $compare_label ) : ?>
							<del><?php echo esc_html( $compare_label ); ?></del>
						<?php endif; ?>
						<strong><?php echo esc_html( $price_label ); ?></strong>
						<em><?php echo esc_html( $unit_label ); ?></em>
					</div>
				</div>
				<?php if ( '' !== (string) $config['discount_badge'] ) : ?>
					<span class="igp-booking-discount"><?php echo esc_html( (string) $config['discount_badge'] ); ?></span>
				<?php endif; ?>
			</div>

			<?php if ( 'booking_enquiry' === $form_mode ) : ?>
				<form id="<?php echo esc_attr( $form_id ); ?>" class="igp-booking-form" data-igp-booking-form>
					<input type="hidden" name="action" value="igp_pro_submit_booking">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'igp_pro_booking' ) ); ?>">
					<input type="hidden" name="tour_id" value="<?php echo esc_attr( $tour_id ); ?>">
					<input type="hidden" name="booking_date" value="<?php echo esc_attr( $booking_date ); ?>">

					<div class="igp-booking-choice-row">
						<?php igp_pro_render_booking_date_picker( (string) $config['currency'], (float) $config['base_price'] ); ?>

						<div class="igp-booking-picker igp-booking-picker--guest" data-igp-traveler-picker>
							<button type="button" class="igp-booking-choice" data-igp-traveler-toggle aria-expanded="false">
								<span class="igp-booking-choice__icon" aria-hidden="true">♙</span>
								<strong data-igp-traveler-summary><?php esc_html_e( 'Guest', 'igp-pro' ); ?></strong>
							</button>
							<div class="igp-booking-traveler-panel" data-igp-traveler-panel hidden>
								<div class="igp-booking-guests" data-igp-guest-wrap>
									<?php foreach ( $config['guest_types'] as $type => $guest ) : ?>
										<?php igp_pro_render_guest_quantity_row( (string) $type, $guest, (string) $config['currency'] ); ?>
									<?php endforeach; ?>
								</div>
								<p class="igp-booking-message igp-booking-message--inline" data-igp-traveler-notice aria-live="polite"></p>
								<button type="button" class="igp-booking-apply" data-igp-traveler-apply><?php esc_html_e( 'Apply', 'igp-pro' ); ?></button>
							</div>
						</div>
					</div>

					<p class="igp-booking-message" data-igp-form-message aria-live="polite"></p>
					<button class="igp-booking-submit igp-booking-check" type="button" data-igp-check-availability><?php esc_html_e( 'Check availability', 'igp-pro' ); ?></button>
					<button class="igp-booking-secondary" type="button" data-igp-open-enquiry><?php esc_html_e( 'Make enquiry', 'igp-pro' ); ?></button>

					<div class="igp-availability-panel" data-igp-availability-panel data-igp-availability-for="<?php echo esc_attr( $form_id ); ?>" hidden>
						<div class="igp-availability-main">
							<?php if ( ! empty( $config['options'] ) ) : ?>
								<div class="igp-tour-options">
									<?php foreach ( $config['options'] as $index => $option ) : ?>
										<label class="igp-tour-option-card">
											<input type="radio" name="tour_option" value="<?php echo esc_attr( $option['id'] ); ?>" data-price="<?php echo esc_attr( (float) $option['price'] ); ?>" data-igp-tour-option<?php igp_pro_echo_form_attr( $form_id ); ?> <?php checked( 0 === $index ); ?>>
											<span class="igp-tour-option-card__dot"></span>
											<span class="igp-tour-option-card__copy">
												<strong><?php echo esc_html( $option['label'] ); ?></strong>
												<?php if ( '' !== (string) $option['description'] ) : ?><em><?php echo esc_html( (string) $option['description'] ); ?></em><?php endif; ?>
											</span>
											<span class="igp-tour-option-card__price"><?php echo esc_html( igp_pro_format_money( (float) $option['price'], (string) $config['currency'] ) ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $config['addons'] ) ) : ?>
								<div class="igp-booking-addons" data-igp-addons-wrap>
									<p class="igp-booking-section-title"><?php esc_html_e( 'Extra Services', 'igp-pro' ); ?></p>
									<?php foreach ( $config['addons'] as $addon ) : ?>
										<?php igp_pro_render_addon_quantity_row( $addon, (string) $config['currency'], $form_id ); ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<p class="igp-booking-cancel-note" data-igp-cancel-note><?php esc_html_e( 'Select a date to see the cancellation window.', 'igp-pro' ); ?></p>
						</div>
						<div class="igp-availability-actions">
							<div class="igp-booking-total" data-currency="<?php echo esc_attr( (string) $config['currency'] ); ?>">
								<span><?php esc_html_e( 'Total', 'igp-pro' ); ?></span>
								<strong data-igp-total><?php echo esc_html( igp_pro_format_money( 0, (string) $config['currency'] ) ); ?></strong>
								<em><?php esc_html_e( 'Payable after checkout details', 'igp-pro' ); ?></em>
							</div>
							<button class="igp-booking-submit" type="submit" form="<?php echo esc_attr( $form_id ); ?>"><?php esc_html_e( 'Book Now', 'igp-pro' ); ?></button>
							<button class="igp-booking-secondary igp-booking-secondary--compact" type="button"><?php esc_html_e( 'Add to Cart', 'igp-pro' ); ?></button>
						</div>
					</div>
				</form>
			<?php else : ?>
				<div class="igp-enquiry-only-card">
					<p><?php esc_html_e( 'This tour is available on request. Send an enquiry and the team will confirm availability and pricing.', 'igp-pro' ); ?></p>
					<button class="igp-booking-secondary igp-booking-secondary--full" type="button" data-igp-open-enquiry><?php esc_html_e( 'Make enquiry', 'igp-pro' ); ?></button>
				</div>
			<?php endif; ?>
		</div>

		<div class="igp-booking-benefits">
			<h3><?php esc_html_e( 'Why booking with IGP?', 'igp-pro' ); ?></h3>
			<ul>
				<?php foreach ( igp_pro_get_booking_benefits() as $benefit ) : ?>
					<li><?php echo esc_html( $benefit ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="igp-enquiry-modal" data-igp-enquiry-modal hidden>
			<div class="igp-enquiry-modal__overlay" data-igp-close-enquiry></div>
			<div class="igp-enquiry-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Make enquiry', 'igp-pro' ); ?>">
				<button type="button" class="igp-enquiry-modal__close" data-igp-close-enquiry aria-label="<?php esc_attr_e( 'Close enquiry form', 'igp-pro' ); ?>">×</button>
				<h3><?php esc_html_e( 'Make enquiry', 'igp-pro' ); ?></h3>
				<p><?php esc_html_e( 'Have a question before booking? Message us to learn more.', 'igp-pro' ); ?></p>
				<form class="igp-enquiry-form" data-igp-enquiry-form>
					<input type="hidden" name="action" value="igp_pro_submit_enquiry">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'igp_pro_booking' ) ); ?>">
					<input type="hidden" name="tour_id" value="<?php echo esc_attr( $tour_id ); ?>">
					<div class="igp-booking-customer">
						<label class="igp-field"><input type="text" name="first_name" placeholder="<?php esc_attr_e( 'First name *', 'igp-pro' ); ?>" required></label>
						<label class="igp-field"><input type="text" name="last_name" placeholder="<?php esc_attr_e( 'Last name *', 'igp-pro' ); ?>" required></label>
						<label class="igp-field igp-field--full"><input type="email" name="email" placeholder="<?php esc_attr_e( 'Email *', 'igp-pro' ); ?>" required></label>
						<label class="igp-field igp-field--full"><input type="tel" name="phone" placeholder="<?php esc_attr_e( 'Phone *', 'igp-pro' ); ?>" required></label>
						<label class="igp-field igp-field--full"><textarea name="question" rows="6" placeholder="<?php esc_attr_e( 'Your question *', 'igp-pro' ); ?>" required></textarea></label>
					</div>
					<p class="igp-booking-message" data-igp-form-message aria-live="polite"></p>
					<button class="igp-booking-submit" type="submit"><?php esc_html_e( 'Send enquiry', 'igp-pro' ); ?></button>
				</form>
			</div>
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

	if ( '' === $contact['first_name'] || '' === $contact['last_name'] || '' === $contact['email'] || '' === $contact['phone'] ) {
		return new WP_Error( 'igp_pro_missing_contact', __( 'Please complete the required contact fields.', 'igp-pro' ) );
	}

	if ( ! is_email( $contact['email'] ) ) {
		return new WP_Error( 'igp_pro_invalid_email', __( 'Please enter a valid email address.', 'igp-pro' ) );
	}

	return $contact;
}

/**
 * Sanitize a date value in Y-m-d format.
 */
function igp_pro_sanitize_ymd_date( $value ): string {
	$value = sanitize_text_field( (string) wp_unslash( $value ) );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
		return '';
	}

	$parts = array_map( 'absint', explode( '-', $value ) );
	return checkdate( $parts[1], $parts[2], $parts[0] ) ? $value : '';
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
		update_post_meta( $post_id, '_igp_booking_date', $payload['booking_date'] ?? current_time( 'Y-m-d' ) );
		update_post_meta( $post_id, '_igp_tour_date', $payload['tour_date'] ?? '' );
	}

	return $post_id;
}

/**
 * Return a signed public URL for a stored booking.
 */
function igp_pro_get_public_booking_url( int $booking_id, string $key, string $query_arg = 'igp_pro_checkout' ): string {
	return add_query_arg(
		array(
			$query_arg => $booking_id,
			'key'     => rawurlencode( $key ),
		),
		home_url( '/' )
	);
}

/**
 * AJAX: start booking checkout from the tour page.
 *
 * This intentionally stores only tour/date/guest/add-on choices. Contact and payment
 * details are collected on the dedicated checkout page.
 */
function igp_pro_ajax_submit_booking(): void {
	$nonce = igp_pro_verify_booking_nonce();
	if ( is_wp_error( $nonce ) ) {
		igp_pro_send_booking_error( $nonce, 403 );
	}

	$tour_id = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;
	if ( $tour_id <= 0 || 'tour' !== get_post_type( $tour_id ) ) {
		igp_pro_send_booking_error( new WP_Error( 'igp_pro_invalid_tour', __( 'Invalid tour selected.', 'igp-pro' ) ) );
	}

	$booking_date = current_time( 'Y-m-d' );
	$tour_date    = isset( $_POST['tour_date'] ) ? igp_pro_sanitize_ymd_date( $_POST['tour_date'] ) : '';
	if ( '' === $tour_date ) {
		igp_pro_send_booking_error( new WP_Error( 'igp_pro_missing_date', __( 'Please select a tour date.', 'igp-pro' ) ) );
	}

	$pricing = igp_pro_calculate_booking_total( $tour_id, $_POST );
	if ( is_wp_error( $pricing ) ) {
		igp_pro_send_booking_error( $pricing );
	}

	$addon_quantities = array();
	if ( isset( $_POST['addon_qty'] ) && is_array( $_POST['addon_qty'] ) ) {
		foreach ( wp_unslash( $_POST['addon_qty'] ) as $addon_id => $qty ) {
			$addon_id = sanitize_key( (string) $addon_id );
			$qty      = max( 0, absint( $qty ) );
			if ( '' !== $addon_id && $qty > 0 ) {
				$addon_quantities[ $addon_id ] = $qty;
			}
		}
	}

	$payload = array(
		'booking_date'   => $booking_date,
		'tour_date'      => $tour_date,
		'tour_option'    => isset( $_POST['tour_option'] ) ? sanitize_key( (string) wp_unslash( $_POST['tour_option'] ) ) : '',
		'addons'         => isset( $_POST['addons'] ) ? array_map( 'sanitize_key', array_map( 'strval', (array) wp_unslash( $_POST['addons'] ) ) ) : array(),
		'addon_qty'      => $addon_quantities,
		'guests'         => $pricing['guests'],
		'total_guests'   => isset( $pricing['total_guests'] ) ? absint( $pricing['total_guests'] ) : 0,
		'checkout_stage' => 'details_required',
	);

	$contact = array(
		'first_name' => __( 'Guest', 'igp-pro' ),
		'last_name'  => '',
		'email'      => '',
		'phone'      => '',
	);

	$booking_id = igp_pro_store_submission( 'booking', $tour_id, $contact, $payload, $pricing );
	if ( is_wp_error( $booking_id ) ) {
		igp_pro_send_booking_error( $booking_id );
	}

	$key = (string) get_post_meta( (int) $booking_id, '_igp_payment_key', true );
	if ( '' === $key ) {
		$key = wp_generate_password( 32, false, false );
		update_post_meta( (int) $booking_id, '_igp_payment_key', $key );
	}

	update_post_meta( (int) $booking_id, '_igp_submission_status', 'checkout_pending' );

	wp_send_json_success(
		array(
			'booking_id'   => (int) $booking_id,
			'redirect_url' => esc_url_raw( igp_pro_get_public_booking_url( (int) $booking_id, $key ) ),
			'status'       => 'checkout_pending',
			'message'      => __( 'Availability confirmed. Continue to checkout.', 'igp-pro' ),
		)
	);
}

/**
 * AJAX: complete checkout contact/payment details and hand off to the active gateway.
 */
function igp_pro_ajax_complete_checkout(): void {
	$nonce = igp_pro_verify_booking_nonce();
	if ( is_wp_error( $nonce ) ) {
		igp_pro_send_booking_error( $nonce, 403 );
	}

	$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
	$key        = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
	if ( ! igp_pro_validate_public_booking_key( $booking_id, $key ) ) {
		igp_pro_send_booking_error( new WP_Error( 'igp_pro_invalid_checkout', __( 'Invalid checkout link.', 'igp-pro' ) ), 403 );
	}

	$status = (string) get_post_meta( $booking_id, '_igp_submission_status', true );
	if ( 'confirmed' === $status ) {
		wp_send_json_success(
			array(
				'redirect_url' => esc_url_raw( igp_pro_get_public_booking_url( $booking_id, $key, 'igp_pro_confirmation' ) ),
				'message'      => __( 'Booking is already confirmed.', 'igp-pro' ),
			)
		);
	}

	$contact = igp_pro_sanitize_contact_request( $_POST );
	if ( is_wp_error( $contact ) ) {
		igp_pro_send_booking_error( $contact );
	}

	$country_region = isset( $_POST['country_region'] ) ? sanitize_text_field( wp_unslash( $_POST['country_region'] ) ) : '';
	$order_notes    = isset( $_POST['order_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['order_notes'] ) ) : '';
	$payment_method = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'gateway';

	if ( '' === $country_region ) {
		igp_pro_send_booking_error( new WP_Error( 'igp_pro_missing_country', __( 'Please select your country or region.', 'igp-pro' ) ) );
	}

	$payload = igp_pro_get_submission_json_meta( $booking_id, '_igp_submission_payload' );
	$pricing = igp_pro_get_submission_json_meta( $booking_id, '_igp_submission_pricing' );
	if ( empty( $pricing ) ) {
		igp_pro_send_booking_error( new WP_Error( 'igp_pro_missing_pricing', __( 'Pricing data is missing for this checkout.', 'igp-pro' ) ) );
	}

	$payload['checkout_stage'] = 'payment_pending';
	$payload['checkout']       = array(
		'country_region' => $country_region,
		'order_notes'    => $order_notes,
		'payment_method' => $payment_method,
	);

	update_post_meta( $booking_id, '_igp_customer_first_name', $contact['first_name'] );
	update_post_meta( $booking_id, '_igp_customer_last_name', $contact['last_name'] );
	update_post_meta( $booking_id, '_igp_customer_email', $contact['email'] );
	update_post_meta( $booking_id, '_igp_customer_phone', $contact['phone'] );
	update_post_meta( $booking_id, '_igp_submission_payload', wp_json_encode( $payload ) );
	update_post_meta( $booking_id, '_igp_submission_status', 'pending_payment' );

	$adapter = igp_pro_get_payment_adapter();
	if ( is_wp_error( $adapter ) ) {
		igp_pro_send_booking_error( $adapter );
	}

	$payment_url = $adapter->create_payment_url( $booking_id, $payload, $pricing );
	if ( is_wp_error( $payment_url ) ) {
		igp_pro_send_booking_error( $payment_url );
	}

	update_post_meta( $booking_id, '_igp_payment_adapter', $adapter->get_id() );

	wp_send_json_success(
		array(
			'booking_id'   => $booking_id,
			'redirect_url' => esc_url_raw( $payment_url ),
			'status'       => 'pending_payment',
			'message'      => __( 'Checkout details saved. Redirecting to payment.', 'igp-pro' ),
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
 * Return the first positive guest line from a pricing payload.
 */
function igp_pro_get_primary_guest_line( array $pricing ): array {
	foreach ( (array) ( $pricing['guests'] ?? array() ) as $guest ) {
		if ( is_array( $guest ) && ! empty( $guest['quantity'] ) ) {
			return $guest;
		}
	}
	return array();
}

/**
 * Render a compact checkout order summary.
 */
function igp_pro_render_checkout_order_summary( int $booking_id, int $tour_id, array $payload, array $pricing ): void {
	$currency     = (string) ( $pricing['currency'] ?? get_post_meta( $booking_id, '_igp_currency', true ) ?: '₹' );
	$total        = isset( $pricing['total'] ) ? (float) $pricing['total'] : (float) get_post_meta( $booking_id, '_igp_total_amount', true );
	$total_guests = isset( $pricing['total_guests'] ) ? absint( $pricing['total_guests'] ) : absint( $payload['total_guests'] ?? 0 );
	$tour_date    = (string) ( $payload['tour_date'] ?? get_post_meta( $booking_id, '_igp_tour_date', true ) );
	$thumb        = get_the_post_thumbnail_url( $tour_id, 'thumbnail' );
	?>
	<aside class="igp-checkout-order">
		<div class="igp-checkout-order__product">
			<?php if ( $thumb ) : ?><img src="<?php echo esc_url( $thumb ); ?>" alt=""><?php endif; ?>
			<div>
				<strong><?php echo esc_html( get_the_title( $tour_id ) ); ?></strong>
				<span><?php esc_html_e( 'Package:', 'igp-pro' ); ?> <?php echo esc_html( get_the_title( $tour_id ) ); ?></span>
			</div>
		</div>
		<ul class="igp-checkout-order__meta">
			<li><?php esc_html_e( 'Tour date', 'igp-pro' ); ?><strong><?php echo esc_html( $tour_date ?: '—' ); ?></strong></li>
			<li><?php esc_html_e( 'Departure', 'igp-pro' ); ?><strong><?php esc_html_e( '9:00 AM', 'igp-pro' ); ?></strong></li>
			<li><?php esc_html_e( 'Guests', 'igp-pro' ); ?><strong><?php echo esc_html( sprintf( _n( '%d guest', '%d guests', $total_guests, 'igp-pro' ), $total_guests ) ); ?></strong></li>
		</ul>
		<div class="igp-checkout-order__line">
			<span><?php echo esc_html( isset( $pricing['formatted'] ) ? (string) $pricing['formatted'] : igp_pro_format_money( $total, $currency ) ); ?> / <?php echo esc_html( sprintf( _n( '%d guest', '%d guests', $total_guests, 'igp-pro' ), $total_guests ) ); ?></span>
			<strong><?php echo esc_html( igp_pro_format_money( (float) ( $pricing['guest_total'] ?? $total ), $currency ) ); ?></strong>
		</div>
		<?php if ( ! empty( $pricing['option'] ) && is_array( $pricing['option'] ) ) : ?>
			<div class="igp-checkout-order__line"><span><?php echo esc_html( (string) $pricing['option']['label'] ); ?></span><strong><?php echo esc_html( igp_pro_format_money( (float) ( $pricing['option_total'] ?? 0 ), $currency ) ); ?></strong></div>
		<?php endif; ?>
		<?php foreach ( (array) ( $pricing['addons'] ?? array() ) as $addon ) : ?>
			<?php if ( ! is_array( $addon ) ) { continue; } ?>
			<div class="igp-checkout-order__line"><span><?php echo esc_html( (string) $addon['label'] ); ?> × <?php echo esc_html( (string) ( $addon['quantity'] ?? 1 ) ); ?></span><strong><?php echo esc_html( igp_pro_format_money( (float) ( $addon['line_total'] ?? 0 ), $currency ) ); ?></strong></div>
		<?php endforeach; ?>
		<a class="igp-checkout-order__promo" href="#"><?php esc_html_e( 'Enter Promo Code', 'igp-pro' ); ?></a>
		<div class="igp-checkout-order__subtotal"><span><?php esc_html_e( 'Subtotal', 'igp-pro' ); ?></span><strong><?php echo esc_html( igp_pro_format_money( $total, $currency ) ); ?></strong></div>
		<div class="igp-checkout-order__total"><span><?php esc_html_e( 'Total', 'igp-pro' ); ?></span><strong><?php echo esc_html( igp_pro_format_money( $total, $currency ) ); ?></strong></div>
	</aside>
	<?php
}

/**
 * Render checkout details page or local payment handoff page, depending on booking status.
 */
function igp_pro_render_checkout_page( int $booking_id, string $key ): void {
	$tour_id = absint( get_post_meta( $booking_id, '_igp_tour_id', true ) );
	$pricing = igp_pro_get_submission_json_meta( $booking_id, '_igp_submission_pricing' );
	$payload = igp_pro_get_submission_json_meta( $booking_id, '_igp_submission_payload' );
	$status  = (string) get_post_meta( $booking_id, '_igp_submission_status', true );

	if ( 'pending_payment' === $status ) {
		igp_pro_render_payment_handoff_page( $booking_id, $key );
		return;
	}

	if ( 'confirmed' === $status ) {
		wp_safe_redirect( igp_pro_get_public_booking_url( $booking_id, $key, 'igp_pro_confirmation' ) );
		exit;
	}

	get_header();
	?>
	<main class="igp-checkout-page igp-checkout-page--details">
		<div class="igp-checkout-layout">
			<div class="igp-checkout-main">
				<form class="igp-checkout-form" data-igp-checkout-form>
					<input type="hidden" name="action" value="igp_pro_complete_checkout">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'igp_pro_booking' ) ); ?>">
					<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking_id ); ?>">
					<input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>">

					<section class="igp-checkout-section">
						<h2><?php esc_html_e( 'Contact details', 'igp-pro' ); ?></h2>
						<p><?php esc_html_e( 'We’ll use this information to send booking confirmations and updates.', 'igp-pro' ); ?></p>
						<div class="igp-checkout-grid">
							<label><?php esc_html_e( 'First name', 'igp-pro' ); ?> <sup>*</sup><input type="text" name="first_name" required></label>
							<label><?php esc_html_e( 'Last name', 'igp-pro' ); ?> <sup>*</sup><input type="text" name="last_name" required></label>
							<label class="igp-checkout-field--full"><?php esc_html_e( 'Country / Region', 'igp-pro' ); ?> <sup>*</sup><select name="country_region" required><option value="India"><?php esc_html_e( 'India', 'igp-pro' ); ?></option><option value="United States (US)"><?php esc_html_e( 'United States (US)', 'igp-pro' ); ?></option><option value="United Kingdom"><?php esc_html_e( 'United Kingdom', 'igp-pro' ); ?></option><option value="Other"><?php esc_html_e( 'Other', 'igp-pro' ); ?></option></select></label>
							<label><?php esc_html_e( 'Phone', 'igp-pro' ); ?> <sup>*</sup><input type="tel" name="phone" required></label>
							<label><?php esc_html_e( 'Email address', 'igp-pro' ); ?> <sup>*</sup><input type="email" name="email" required></label>
						</div>
					</section>

					<section class="igp-checkout-section">
						<h2><?php esc_html_e( 'Additional information', 'igp-pro' ); ?></h2>
						<p><?php esc_html_e( 'If you have any special needs, please enter them here.', 'igp-pro' ); ?></p>
						<label><?php esc_html_e( 'Order notes (optional)', 'igp-pro' ); ?><textarea name="order_notes" rows="5" placeholder="<?php esc_attr_e( 'Notes about your order, e.g. special requests or pickup details.', 'igp-pro' ); ?>"></textarea></label>
					</section>

					<section class="igp-checkout-section igp-checkout-payment">
						<h2><?php esc_html_e( 'Payment details', 'igp-pro' ); ?></h2>
						<p><?php esc_html_e( 'Choose how this booking should proceed to payment.', 'igp-pro' ); ?></p>
						<label class="igp-checkout-radio"><input type="radio" name="payment_method" value="gateway" checked> <strong><?php esc_html_e( 'Secure online payment', 'igp-pro' ); ?></strong><span><?php esc_html_e( 'Continue to the configured payment gateway after placing the order.', 'igp-pro' ); ?></span></label>
						<label class="igp-checkout-radio"><input type="radio" name="payment_method" value="manual_review"> <strong><?php esc_html_e( 'Manual review', 'igp-pro' ); ?></strong><span><?php esc_html_e( 'Store booking details for staff review before payment collection.', 'igp-pro' ); ?></span></label>
						<p class="igp-booking-message" data-igp-form-message aria-live="polite"></p>
						<button class="igp-booking-submit igp-checkout-place-order" type="submit"><?php esc_html_e( 'Place order', 'igp-pro' ); ?></button>
					</section>
				</form>
			</div>
			<?php igp_pro_render_checkout_order_summary( $booking_id, $tour_id, $payload, $pricing ); ?>
		</div>
	</main>
	<?php
	get_footer();
}

/**
 * Render the final local gateway handoff page.
 */
function igp_pro_render_payment_handoff_page( int $booking_id, string $key ): void {
	$tour_id   = absint( get_post_meta( $booking_id, '_igp_tour_id', true ) );
	$pricing   = igp_pro_get_submission_json_meta( $booking_id, '_igp_submission_pricing' );
	$payload   = igp_pro_get_submission_json_meta( $booking_id, '_igp_submission_payload' );
	$tour_date = $payload['tour_date'] ?? get_post_meta( $booking_id, '_igp_tour_date', true );
	$status    = (string) get_post_meta( $booking_id, '_igp_submission_status', true );

	get_header();
	?>
	<main class="igp-checkout-page">
		<section class="igp-checkout-card">
			<p class="igp-checkout-eyebrow"><?php esc_html_e( 'Secure payment', 'igp-pro' ); ?></p>
			<h1><?php esc_html_e( 'Complete your payment', 'igp-pro' ); ?></h1>
			<p><?php echo esc_html( get_the_title( $tour_id ) ); ?></p>
			<div class="igp-checkout-summary">
				<span><?php esc_html_e( 'Booking ID', 'igp-pro' ); ?></span><strong>#<?php echo esc_html( (string) $booking_id ); ?></strong>
				<span><?php esc_html_e( 'Tour date', 'igp-pro' ); ?></span><strong><?php echo esc_html( $tour_date ?: '—' ); ?></strong>
				<span><?php esc_html_e( 'Status', 'igp-pro' ); ?></span><strong><?php echo esc_html( igp_pro_format_submission_status( $status ) ); ?></strong>
				<span><?php esc_html_e( 'Amount payable', 'igp-pro' ); ?></span><strong><?php echo esc_html( isset( $pricing['formatted'] ) ? (string) $pricing['formatted'] : igp_pro_format_money( (float) get_post_meta( $booking_id, '_igp_total_amount', true ), (string) get_post_meta( $booking_id, '_igp_currency', true ) ) ); ?></strong>
			</div>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="igp_pro_complete_mock_payment">
				<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking_id ); ?>">
				<input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>">
				<?php wp_nonce_field( 'igp_pro_complete_mock_payment_' . $booking_id ); ?>
				<button class="igp-booking-submit igp-booking-submit--large" type="submit"><?php esc_html_e( 'Pay now', 'igp-pro' ); ?></button>
			</form>
			<p class="igp-checkout-note"><?php esc_html_e( 'Phase 4 uses the local payment gateway so booking state, payment handoff, and confirmation can be validated before live gateway credentials are connected.', 'igp-pro' ); ?></p>
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
	$payload = igp_pro_get_submission_json_meta( $booking_id, '_igp_submission_payload' );
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
				<span><?php esc_html_e( 'Tour date', 'igp-pro' ); ?></span><strong><?php echo esc_html( (string) ( $payload['tour_date'] ?? '' ) ); ?></strong>
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
 * Convert associative guest config into sequential rows for admin rendering.
 */
function igp_pro_admin_config_rows_from_assoc( array $rows ): array {
	$out = array();
	foreach ( $rows as $id => $row ) {
		$row['id'] = $row['id'] ?? (string) $id;
		$out[] = $row;
	}
	return $out;
}

/**
 * Render a repeatable pricing table.
 */
function igp_pro_render_booking_repeater_table( string $kind, string $label, array $rows, bool $with_description = true ): void {
	?>
	<div class="igp-booking-repeater" data-igp-booking-repeater="<?php echo esc_attr( $kind ); ?>">
		<div class="igp-booking-repeater__head">
			<h4><?php echo esc_html( $label ); ?></h4>
			<button type="button" class="button" data-igp-add-row><?php esc_html_e( 'Add row', 'igp-pro' ); ?></button>
		</div>
		<div class="igp-booking-repeater__rows" data-igp-rows>
			<?php foreach ( $rows as $row ) : ?>
				<div class="igp-booking-repeater__row" data-igp-row>
					<input type="text" name="igp_booking_<?php echo esc_attr( $kind ); ?>[id][]" value="<?php echo esc_attr( (string) ( $row['id'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'id', 'igp-pro' ); ?>">
					<input type="text" name="igp_booking_<?php echo esc_attr( $kind ); ?>[label][]" value="<?php echo esc_attr( (string) ( $row['label'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Label', 'igp-pro' ); ?>">
					<input type="number" step="0.01" min="0" name="igp_booking_<?php echo esc_attr( $kind ); ?>[price][]" value="<?php echo esc_attr( (string) ( $row['price'] ?? 0 ) ); ?>" placeholder="<?php esc_attr_e( 'Price', 'igp-pro' ); ?>">
					<?php if ( $with_description ) : ?>
						<input type="text" name="igp_booking_<?php echo esc_attr( $kind ); ?>[description][]" value="<?php echo esc_attr( (string) ( $row['description'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Description', 'igp-pro' ); ?>">
					<?php else : ?>
						<input type="hidden" name="igp_booking_<?php echo esc_attr( $kind ); ?>[description][]" value="<?php echo esc_attr( (string) ( $row['description'] ?? '' ) ); ?>">
					<?php endif; ?>
					<?php if ( 'addons' === $kind ) : ?>
						<textarea name="igp_booking_<?php echo esc_attr( $kind ); ?>[included][]" placeholder="<?php esc_attr_e( 'Included items, one per line', 'igp-pro' ); ?>"><?php echo esc_textarea( (string) ( $row['included'] ?? '' ) ); ?></textarea>
						<textarea name="igp_booking_<?php echo esc_attr( $kind ); ?>[excluded][]" placeholder="<?php esc_attr_e( 'Excluded items, one per line', 'igp-pro' ); ?>"><?php echo esc_textarea( (string) ( $row['excluded'] ?? '' ) ); ?></textarea>
					<?php endif; ?>
					<button type="button" class="button-link-delete" data-igp-remove-row><?php esc_html_e( 'Remove', 'igp-pro' ); ?></button>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Render tour booking settings metabox.
 */
function igp_pro_render_tour_booking_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'igp_pro_save_tour_booking_meta', 'igp_pro_booking_meta_nonce' );
	$config      = igp_pro_get_tour_booking_config( $post->ID );
	$guest_rows  = igp_pro_admin_config_rows_from_assoc( $config['guest_types'] );
	$option_rows = $config['options'];
	$addon_rows  = $config['addons'];
	if ( empty( $addon_rows ) ) {
		$addon_rows = array();
	}
	?>
	<div class="igp-booking-meta-box" data-igp-booking-settings>
		<p><label><input type="checkbox" name="igp_booking_enabled" value="1" <?php checked( $config['enabled'] ); ?>> <?php esc_html_e( 'Enable booking/enquiry panel for this tour', 'igp-pro' ); ?></label></p>
		<p class="description"><?php esc_html_e( 'The panel appears automatically on single Tour pages and can also be inserted with [igp_booking_panel] or the IGP Booking Form block.', 'igp-pro' ); ?></p>
		<div class="igp-booking-meta-grid">
			<label><?php esc_html_e( 'Form mode', 'igp-pro' ); ?>
				<select name="igp_booking_form_mode">
					<option value="booking_enquiry" <?php selected( $config['form_mode'], 'booking_enquiry' ); ?>><?php esc_html_e( 'Booking + Enquiry form', 'igp-pro' ); ?></option>
					<option value="enquiry_only" <?php selected( $config['form_mode'], 'enquiry_only' ); ?>><?php esc_html_e( 'Enquiry only form', 'igp-pro' ); ?></option>
				</select>
			</label>
			<label><?php esc_html_e( 'Sale/base price', 'igp-pro' ); ?><input type="number" step="0.01" min="0" name="igp_booking_base_price" value="<?php echo esc_attr( (string) $config['base_price'] ); ?>"></label>
			<label><?php esc_html_e( 'Compare-at price', 'igp-pro' ); ?><input type="number" step="0.01" min="0" name="igp_booking_compare_price" value="<?php echo esc_attr( (string) $config['compare_price'] ); ?>"></label>
			<label><?php esc_html_e( 'Discount badge', 'igp-pro' ); ?><input type="text" name="igp_booking_discount_badge" value="<?php echo esc_attr( (string) $config['discount_badge'] ); ?>" placeholder="-15%"></label>
			<label><?php esc_html_e( 'Currency symbol', 'igp-pro' ); ?><input type="text" name="igp_booking_currency" value="<?php echo esc_attr( (string) $config['currency'] ); ?>"></label>
			<label><?php esc_html_e( 'Pricing unit', 'igp-pro' ); ?><input type="text" name="igp_booking_pricing_unit" value="<?php echo esc_attr( (string) $config['pricing_unit'] ); ?>" placeholder="/group"></label>
		</div>
		<?php igp_pro_render_booking_repeater_table( 'guest_types', __( 'Traveler types', 'igp-pro' ), $guest_rows, true ); ?>
		<p class="description"><?php esc_html_e( 'Add, remove, or rename traveler classes. Each row creates one frontend quantity selector.', 'igp-pro' ); ?></p>
		<?php igp_pro_render_booking_repeater_table( 'options', __( 'Tour options', 'igp-pro' ), $option_rows, true ); ?>
		<?php igp_pro_render_booking_repeater_table( 'addons', __( 'Add-ons', 'igp-pro' ), $addon_rows, true ); ?>
	</div>
	<?php
}

/**
 * Build JSON rows from repeatable admin POST arrays.
 */
function igp_pro_build_booking_rows_from_post( string $field ): array {
	$raw = isset( $_POST[ $field ] ) && is_array( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : array();
	$ids = isset( $raw['id'] ) && is_array( $raw['id'] ) ? $raw['id'] : array();
	$rows = array();

	foreach ( $ids as $index => $id ) {
		$row = array(
			'id'          => sanitize_key( (string) $id ),
			'label'       => isset( $raw['label'][ $index ] ) ? sanitize_text_field( (string) $raw['label'][ $index ] ) : '',
			'price'       => isset( $raw['price'][ $index ] ) ? igp_pro_parse_money( $raw['price'][ $index ] ) : 0.0,
			'description' => isset( $raw['description'][ $index ] ) ? sanitize_text_field( (string) $raw['description'][ $index ] ) : '',
		);

		if ( false !== strpos( $field, 'addons' ) ) {
			$row['included'] = isset( $raw['included'][ $index ] ) ? sanitize_textarea_field( (string) $raw['included'][ $index ] ) : '';
			$row['excluded'] = isset( $raw['excluded'][ $index ] ) ? sanitize_textarea_field( (string) $raw['excluded'][ $index ] ) : '';
		}
		if ( '' === $row['id'] && '' !== $row['label'] ) {
			$row['id'] = sanitize_key( $row['label'] );
		}
		if ( '' === $row['id'] || '' === $row['label'] ) {
			continue;
		}
		$rows[] = $row;
	}

	return $rows;
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

	$form_mode = isset( $_POST['igp_booking_form_mode'] ) ? sanitize_key( wp_unslash( $_POST['igp_booking_form_mode'] ) ) : 'booking_enquiry';
	if ( ! in_array( $form_mode, array( 'booking_enquiry', 'enquiry_only' ), true ) ) {
		$form_mode = 'booking_enquiry';
	}

	update_post_meta( $post_id, '_igp_booking_enabled', isset( $_POST['igp_booking_enabled'] ) ? 'yes' : 'no' );
	update_post_meta( $post_id, '_igp_booking_form_mode', $form_mode );
	update_post_meta( $post_id, '_igp_booking_base_price', isset( $_POST['igp_booking_base_price'] ) ? igp_pro_parse_money( wp_unslash( $_POST['igp_booking_base_price'] ) ) : 0 );
	update_post_meta( $post_id, '_igp_booking_compare_price', isset( $_POST['igp_booking_compare_price'] ) ? igp_pro_parse_money( wp_unslash( $_POST['igp_booking_compare_price'] ) ) : 0 );
	update_post_meta( $post_id, '_igp_booking_discount_badge', isset( $_POST['igp_booking_discount_badge'] ) ? sanitize_text_field( wp_unslash( $_POST['igp_booking_discount_badge'] ) ) : '' );
	update_post_meta( $post_id, '_igp_booking_currency', isset( $_POST['igp_booking_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['igp_booking_currency'] ) ) : '₹' );
	update_post_meta( $post_id, '_igp_booking_pricing_unit', isset( $_POST['igp_booking_pricing_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['igp_booking_pricing_unit'] ) ) : '/person' );

	$guest_rows  = igp_pro_build_booking_rows_from_post( 'igp_booking_guest_types' );
	$option_rows = igp_pro_build_booking_rows_from_post( 'igp_booking_options' );
	$addon_rows  = igp_pro_build_booking_rows_from_post( 'igp_booking_addons' );

	if ( ! empty( $guest_rows ) ) {
		update_post_meta( $post_id, '_igp_booking_guest_types', wp_json_encode( $guest_rows ) );
	} else {
		delete_post_meta( $post_id, '_igp_booking_guest_types' );
	}

	if ( ! empty( $option_rows ) ) {
		update_post_meta( $post_id, '_igp_booking_options', wp_json_encode( $option_rows ) );
	} else {
		delete_post_meta( $post_id, '_igp_booking_options' );
	}

	if ( ! empty( $addon_rows ) ) {
		update_post_meta( $post_id, '_igp_booking_addons', wp_json_encode( $addon_rows ) );
	} else {
		delete_post_meta( $post_id, '_igp_booking_addons' );
	}
}

/**
 * Human status label helper shared with admin panel.
 */
function igp_pro_format_submission_status( string $status ): string {
	$labels = array(
		'checkout_pending' => __( 'Checkout pending', 'igp-pro' ),
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
