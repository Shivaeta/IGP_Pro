<?php
/**
 * Payment gateway settings for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register payment settings admin screen.
 */
function igp_pro_register_payment_settings_admin(): void {
	add_action( 'admin_menu', 'igp_pro_register_payment_settings_menu' );
	add_action( 'admin_post_igp_pro_save_payment_settings', 'igp_pro_handle_save_payment_settings' );
}

/**
 * Register submenu under IGP Pro.
 */
function igp_pro_register_payment_settings_menu(): void {
	add_submenu_page(
		'igp-pro-content-editor',
		__( 'Payment Settings', 'igp-pro' ),
		__( 'Payment Settings', 'igp-pro' ),
		function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'payment_settings' ) : 'manage_options',
		'igp-pro-payment-settings',
		'igp_pro_render_payment_settings_page'
	);
}

/**
 * Sanitize checkbox value.
 */
function igp_pro_payment_bool_from_post( string $key ): string {
	return isset( $_POST[ $key ] ) ? 'yes' : 'no';
}

/**
 * Return masked value for display hint only.
 */
function igp_pro_mask_secret( string $value ): string {
	if ( '' === $value ) {
		return '';
	}
	$len = strlen( $value );
	if ( $len <= 6 ) {
		return str_repeat( '•', $len );
	}
	return substr( $value, 0, 3 ) . str_repeat( '•', max( 3, $len - 6 ) ) . substr( $value, -3 );
}

/**
 * Render payment settings page.
 */
function igp_pro_render_payment_settings_page(): void {
	if ( ! current_user_can( function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'payment_settings' ) : 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage payment settings.', 'igp-pro' ) );
	}

	$active = sanitize_key( (string) get_option( 'igp_pro_payment_adapter', 'mock' ) );
	?>
	<div class="wrap igp-booking-admin-wrap">
		<h1><?php esc_html_e( 'IGP Pro Payment Settings', 'igp-pro' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Store client gateway credentials here. The local gateway remains available for testing; live gateway redirects require a credentialed adapter implementation and webhook verification.', 'igp-pro' ); ?></p>
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Payment settings saved.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="igp-payment-settings-form">
			<input type="hidden" name="action" value="igp_pro_save_payment_settings">
			<?php wp_nonce_field( 'igp_pro_save_payment_settings' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="igp_pro_payment_adapter"><?php esc_html_e( 'Active gateway', 'igp-pro' ); ?></label></th>
					<td>
						<select id="igp_pro_payment_adapter" name="igp_pro_payment_adapter">
							<option value="mock" <?php selected( $active, 'mock' ); ?>><?php esc_html_e( 'Local Payment Gateway', 'igp-pro' ); ?></option>
							<option value="razorpay" <?php selected( $active, 'razorpay' ); ?>><?php esc_html_e( 'Razorpay', 'igp-pro' ); ?></option>
							<option value="stripe" <?php selected( $active, 'stripe' ); ?>><?php esc_html_e( 'Stripe', 'igp-pro' ); ?></option>
							<option value="paypal" <?php selected( $active, 'paypal' ); ?>><?php esc_html_e( 'PayPal', 'igp-pro' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Use Local Payment Gateway until the live adapter is connected and tested.', 'igp-pro' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Razorpay', 'igp-pro' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="igp_pro_razorpay_key_id"><?php esc_html_e( 'Key ID', 'igp-pro' ); ?></label></th><td><input class="regular-text" id="igp_pro_razorpay_key_id" name="igp_pro_razorpay_key_id" type="text" value="<?php echo esc_attr( (string) get_option( 'igp_pro_razorpay_key_id', '' ) ); ?>"></td></tr>
				<tr><th scope="row"><label for="igp_pro_razorpay_key_secret"><?php esc_html_e( 'Key Secret', 'igp-pro' ); ?></label></th><td><input class="regular-text" id="igp_pro_razorpay_key_secret" name="igp_pro_razorpay_key_secret" type="password" value="<?php echo esc_attr( (string) get_option( 'igp_pro_razorpay_key_secret', '' ) ); ?>"><p class="description"><?php echo esc_html( igp_pro_mask_secret( (string) get_option( 'igp_pro_razorpay_key_secret', '' ) ) ); ?></p></td></tr>
				<tr><th scope="row"><label for="igp_pro_razorpay_webhook_secret"><?php esc_html_e( 'Webhook Secret', 'igp-pro' ); ?></label></th><td><input class="regular-text" id="igp_pro_razorpay_webhook_secret" name="igp_pro_razorpay_webhook_secret" type="password" value="<?php echo esc_attr( (string) get_option( 'igp_pro_razorpay_webhook_secret', '' ) ); ?>"></td></tr>
			</table>

			<h2><?php esc_html_e( 'Stripe', 'igp-pro' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="igp_pro_stripe_publishable_key"><?php esc_html_e( 'Publishable key', 'igp-pro' ); ?></label></th><td><input class="regular-text" id="igp_pro_stripe_publishable_key" name="igp_pro_stripe_publishable_key" type="text" value="<?php echo esc_attr( (string) get_option( 'igp_pro_stripe_publishable_key', '' ) ); ?>"></td></tr>
				<tr><th scope="row"><label for="igp_pro_stripe_secret_key"><?php esc_html_e( 'Secret key', 'igp-pro' ); ?></label></th><td><input class="regular-text" id="igp_pro_stripe_secret_key" name="igp_pro_stripe_secret_key" type="password" value="<?php echo esc_attr( (string) get_option( 'igp_pro_stripe_secret_key', '' ) ); ?>"></td></tr>
				<tr><th scope="row"><label for="igp_pro_stripe_webhook_secret"><?php esc_html_e( 'Webhook secret', 'igp-pro' ); ?></label></th><td><input class="regular-text" id="igp_pro_stripe_webhook_secret" name="igp_pro_stripe_webhook_secret" type="password" value="<?php echo esc_attr( (string) get_option( 'igp_pro_stripe_webhook_secret', '' ) ); ?>"></td></tr>
			</table>

			<h2><?php esc_html_e( 'PayPal', 'igp-pro' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="igp_pro_paypal_client_id"><?php esc_html_e( 'Client ID', 'igp-pro' ); ?></label></th><td><input class="regular-text" id="igp_pro_paypal_client_id" name="igp_pro_paypal_client_id" type="text" value="<?php echo esc_attr( (string) get_option( 'igp_pro_paypal_client_id', '' ) ); ?>"></td></tr>
				<tr><th scope="row"><label for="igp_pro_paypal_secret"><?php esc_html_e( 'Secret', 'igp-pro' ); ?></label></th><td><input class="regular-text" id="igp_pro_paypal_secret" name="igp_pro_paypal_secret" type="password" value="<?php echo esc_attr( (string) get_option( 'igp_pro_paypal_secret', '' ) ); ?>"></td></tr>
				<tr><th scope="row"><label for="igp_pro_paypal_webhook_id"><?php esc_html_e( 'Webhook ID', 'igp-pro' ); ?></label></th><td><input class="regular-text" id="igp_pro_paypal_webhook_id" name="igp_pro_paypal_webhook_id" type="text" value="<?php echo esc_attr( (string) get_option( 'igp_pro_paypal_webhook_id', '' ) ); ?>"></td></tr>
			</table>

			<?php submit_button( __( 'Save payment settings', 'igp-pro' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Save payment settings.
 */
function igp_pro_handle_save_payment_settings(): void {
	if ( ! current_user_can( function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'payment_settings' ) : 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage payment settings.', 'igp-pro' ) );
	}

	if ( ! wp_verify_nonce( isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '', 'igp_pro_save_payment_settings' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'igp-pro' ) );
	}

	$adapter = isset( $_POST['igp_pro_payment_adapter'] ) ? sanitize_key( wp_unslash( $_POST['igp_pro_payment_adapter'] ) ) : 'mock';
	if ( ! in_array( $adapter, array( 'mock', 'razorpay', 'stripe', 'paypal' ), true ) ) {
		$adapter = 'mock';
	}
	update_option( 'igp_pro_payment_adapter', $adapter, false );

	$text_fields = array(
		'igp_pro_razorpay_key_id',
		'igp_pro_razorpay_key_secret',
		'igp_pro_razorpay_webhook_secret',
		'igp_pro_stripe_publishable_key',
		'igp_pro_stripe_secret_key',
		'igp_pro_stripe_webhook_secret',
		'igp_pro_paypal_client_id',
		'igp_pro_paypal_secret',
		'igp_pro_paypal_webhook_id',
	);

	foreach ( $text_fields as $field ) {
		$value = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
		update_option( $field, $value, false );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=igp-pro-payment-settings&updated=1' ) );
	exit;
}
