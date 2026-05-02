<?php
/**
 * Booking / Enquiry admin panel.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Phase 4 admin hooks.
 */
function igp_pro_register_booking_admin(): void {
	add_action( 'admin_menu', 'igp_pro_register_booking_panel_menu' );
	add_action( 'admin_enqueue_scripts', 'igp_pro_enqueue_booking_admin_assets' );
	add_action( 'admin_post_igp_pro_update_submission_status', 'igp_pro_handle_submission_status_update' );
}

/**
 * Register submenu under IGP Pro.
 */
function igp_pro_register_booking_panel_menu(): void {
	add_submenu_page(
		'igp-pro-content-editor',
		__( 'Booking / Enquiry', 'igp-pro' ),
		__( 'Booking / Enquiry', 'igp-pro' ),
		'edit_posts',
		'igp-pro-bookings',
		'igp_pro_render_booking_panel_page'
	);
}

/**
 * Enqueue admin styles.
 */
function igp_pro_enqueue_booking_admin_assets( string $hook ): void {
	if ( false === strpos( $hook, 'igp-pro-bookings' ) ) {
		return;
	}

	$css = 'assets/css/booking-admin.css';
	if ( file_exists( igp_pro_path( $css ) ) ) {
		wp_enqueue_style( 'igp-pro-booking-admin', igp_pro_url( $css ), array(), function_exists( 'igp_pro_asset_version' ) ? igp_pro_asset_version( $css ) : IGP_PRO_VERSION );
	}
}

/**
 * Render panel page.
 */
function igp_pro_render_booking_panel_page(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to access bookings and enquiries.', 'igp-pro' ) );
	}

	$submission_id = isset( $_GET['submission_id'] ) ? absint( $_GET['submission_id'] ) : 0;
	?>
	<div class="wrap igp-booking-admin-wrap">
		<h1><?php esc_html_e( 'Booking / Enquiry Panel', 'igp-pro' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Inspect booking and enquiry submissions captured by the Phase 4 engine.', 'igp-pro' ); ?></p>
		<?php if ( $submission_id > 0 ) : ?>
			<?php igp_pro_render_submission_detail( $submission_id ); ?>
		<?php else : ?>
			<?php igp_pro_render_submission_filters(); ?>
			<?php igp_pro_render_submission_summary(); ?>
			<?php igp_pro_render_submission_table(); ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render filters.
 */
function igp_pro_render_submission_filters(): void {
	$type   = isset( $_GET['submission_type'] ) ? sanitize_key( wp_unslash( $_GET['submission_type'] ) ) : '';
	$status = isset( $_GET['submission_status'] ) ? sanitize_key( wp_unslash( $_GET['submission_status'] ) ) : '';
	?>
	<form method="get" class="igp-booking-admin-filters">
		<input type="hidden" name="page" value="igp-pro-bookings">
		<label><?php esc_html_e( 'Type', 'igp-pro' ); ?>
			<select name="submission_type">
				<option value=""><?php esc_html_e( 'All', 'igp-pro' ); ?></option>
				<option value="booking" <?php selected( $type, 'booking' ); ?>><?php esc_html_e( 'Bookings', 'igp-pro' ); ?></option>
				<option value="enquiry" <?php selected( $type, 'enquiry' ); ?>><?php esc_html_e( 'Enquiries', 'igp-pro' ); ?></option>
			</select>
		</label>
		<label><?php esc_html_e( 'Status', 'igp-pro' ); ?>
			<select name="submission_status">
				<option value=""><?php esc_html_e( 'All statuses', 'igp-pro' ); ?></option>
				<?php foreach ( igp_pro_get_submission_status_options() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<button class="button button-primary" type="submit"><?php esc_html_e( 'Filter', 'igp-pro' ); ?></button>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=igp-pro-bookings' ) ); ?>"><?php esc_html_e( 'Reset', 'igp-pro' ); ?></a>
	</form>
	<?php
}

/**
 * Status options.
 */
function igp_pro_get_submission_status_options(): array {
	return array(
		'pending_payment' => __( 'Pending payment', 'igp-pro' ),
		'confirmed'       => __( 'Confirmed', 'igp-pro' ),
		'failed'          => __( 'Failed', 'igp-pro' ),
		'cancelled'       => __( 'Cancelled', 'igp-pro' ),
		'received'        => __( 'Received', 'igp-pro' ),
		'contacted'       => __( 'Contacted', 'igp-pro' ),
		'converted'       => __( 'Converted', 'igp-pro' ),
		'closed'          => __( 'Closed', 'igp-pro' ),
	);
}

/**
 * Query submissions.
 */
function igp_pro_get_submission_query_args( int $posts_per_page = 50 ): array {
	$meta_query = array();
	$type       = isset( $_GET['submission_type'] ) ? sanitize_key( wp_unslash( $_GET['submission_type'] ) ) : '';
	$status     = isset( $_GET['submission_status'] ) ? sanitize_key( wp_unslash( $_GET['submission_status'] ) ) : '';

	if ( in_array( $type, array( 'booking', 'enquiry' ), true ) ) {
		$meta_query[] = array(
			'key'   => '_igp_submission_type',
			'value' => $type,
		);
	}

	if ( '' !== $status ) {
		$meta_query[] = array(
			'key'   => '_igp_submission_status',
			'value' => $status,
		);
	}

	return array(
		'post_type'      => IGP_PRO_BOOKING_POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => $posts_per_page,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => $meta_query,
	);
}

/**
 * Render summary cards.
 */
function igp_pro_render_submission_summary(): void {
	$all = get_posts(
		array(
			'post_type'      => IGP_PRO_BOOKING_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'fields'         => 'ids',
		)
	);

	$counts = array(
		'booking'         => 0,
		'enquiry'         => 0,
		'pending_payment' => 0,
		'confirmed'       => 0,
		'received'        => 0,
	);

	foreach ( $all as $id ) {
		$type   = (string) get_post_meta( $id, '_igp_submission_type', true );
		$status = (string) get_post_meta( $id, '_igp_submission_status', true );
		if ( isset( $counts[ $type ] ) ) {
			$counts[ $type ]++;
		}
		if ( isset( $counts[ $status ] ) ) {
			$counts[ $status ]++;
		}
	}
	?>
	<div class="igp-booking-admin-summary">
		<?php foreach ( $counts as $key => $count ) : ?>
			<div class="igp-booking-admin-card">
				<strong><?php echo esc_html( (string) $count ); ?></strong>
				<span><?php echo esc_html( igp_pro_format_submission_status( $key ) ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Render submissions table.
 */
function igp_pro_render_submission_table(): void {
	$submissions = get_posts( igp_pro_get_submission_query_args() );
	?>
	<table class="widefat striped igp-booking-admin-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'igp-pro' ); ?></th>
				<th><?php esc_html_e( 'Type', 'igp-pro' ); ?></th>
				<th><?php esc_html_e( 'Tour', 'igp-pro' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'igp-pro' ); ?></th>
				<th><?php esc_html_e( 'Contact', 'igp-pro' ); ?></th>
				<th><?php esc_html_e( 'Tour date', 'igp-pro' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'igp-pro' ); ?></th>
				<th><?php esc_html_e( 'Status', 'igp-pro' ); ?></th>
				<th><?php esc_html_e( 'Booking date', 'igp-pro' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'igp-pro' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $submissions ) ) : ?>
			<tr><td colspan="10"><?php esc_html_e( 'No bookings or enquiries found.', 'igp-pro' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $submissions as $submission ) : ?>
				<?php
				$id       = $submission->ID;
				$type     = (string) get_post_meta( $id, '_igp_submission_type', true );
				$status   = (string) get_post_meta( $id, '_igp_submission_status', true );
				$tour_id  = absint( get_post_meta( $id, '_igp_tour_id', true ) );
				$first    = (string) get_post_meta( $id, '_igp_customer_first_name', true );
				$last     = (string) get_post_meta( $id, '_igp_customer_last_name', true );
				$email    = (string) get_post_meta( $id, '_igp_customer_email', true );
				$phone    = (string) get_post_meta( $id, '_igp_customer_phone', true );
				$amount       = (float) get_post_meta( $id, '_igp_total_amount', true );
				$currency     = (string) get_post_meta( $id, '_igp_currency', true );
				$tour_date    = (string) get_post_meta( $id, '_igp_tour_date', true );
				$booking_date = (string) get_post_meta( $id, '_igp_booking_date', true );
				?>
				<tr>
					<td>#<?php echo esc_html( (string) $id ); ?></td>
					<td><span class="igp-pill igp-pill--<?php echo esc_attr( $type ); ?>"><?php echo esc_html( ucfirst( $type ) ); ?></span></td>
					<td><?php echo $tour_id ? '<a href="' . esc_url( get_edit_post_link( $tour_id ) ) . '">' . esc_html( get_the_title( $tour_id ) ) . '</a>' : '—'; ?></td>
					<td><?php echo esc_html( trim( $first . ' ' . $last ) ); ?></td>
					<td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><br><?php echo esc_html( $phone ); ?></td>
					<td><?php echo '' !== $tour_date ? esc_html( $tour_date ) : '—'; ?></td>
					<td><?php echo $amount > 0 ? esc_html( igp_pro_format_money( $amount, $currency ?: '₹' ) ) : '—'; ?></td>
					<td><?php echo esc_html( igp_pro_format_submission_status( $status ) ); ?></td>
					<td><?php echo esc_html( '' !== $booking_date ? $booking_date : get_the_date( '', $submission ) ); ?></td>
					<td>
						<a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=igp-pro-bookings&submission_id=' . $id ) ); ?>"><?php esc_html_e( 'Inspect', 'igp-pro' ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Render one submission detail page.
 */
function igp_pro_render_submission_detail( int $submission_id ): void {
	$post = get_post( $submission_id );
	if ( ! $post || IGP_PRO_BOOKING_POST_TYPE !== $post->post_type ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Submission not found.', 'igp-pro' ) . '</p></div>';
		return;
	}

	$type      = (string) get_post_meta( $submission_id, '_igp_submission_type', true );
	$status    = (string) get_post_meta( $submission_id, '_igp_submission_status', true );
	$tour_id   = absint( get_post_meta( $submission_id, '_igp_tour_id', true ) );
	$payload   = igp_pro_get_submission_json_meta( $submission_id, '_igp_submission_payload' );
	$pricing   = igp_pro_get_submission_json_meta( $submission_id, '_igp_submission_pricing' );
	$first     = (string) get_post_meta( $submission_id, '_igp_customer_first_name', true );
	$last      = (string) get_post_meta( $submission_id, '_igp_customer_last_name', true );
	$email     = (string) get_post_meta( $submission_id, '_igp_customer_email', true );
	$phone     = (string) get_post_meta( $submission_id, '_igp_customer_phone', true );
	$txn          = (string) get_post_meta( $submission_id, '_igp_transaction_id', true );
	$tour_date    = (string) get_post_meta( $submission_id, '_igp_tour_date', true );
	$booking_date = (string) get_post_meta( $submission_id, '_igp_booking_date', true );
	?>
	<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=igp-pro-bookings' ) ); ?>">← <?php esc_html_e( 'Back to panel', 'igp-pro' ); ?></a></p>
	<div class="igp-booking-detail-grid">
		<section class="igp-booking-admin-box">
			<h2><?php echo esc_html( ucfirst( $type ) ); ?> #<?php echo esc_html( (string) $submission_id ); ?></h2>
			<dl>
				<dt><?php esc_html_e( 'Status', 'igp-pro' ); ?></dt><dd><?php echo esc_html( igp_pro_format_submission_status( $status ) ); ?></dd>
				<dt><?php esc_html_e( 'Tour', 'igp-pro' ); ?></dt><dd><?php echo $tour_id ? esc_html( get_the_title( $tour_id ) ) : '—'; ?></dd>
				<?php if ( 'booking' === $type ) : ?>
					<dt><?php esc_html_e( 'Booking date', 'igp-pro' ); ?></dt><dd><?php echo esc_html( '' !== $booking_date ? $booking_date : get_the_date( '', $post ) ); ?></dd>
					<dt><?php esc_html_e( 'Tour date', 'igp-pro' ); ?></dt><dd><?php echo esc_html( '' !== $tour_date ? $tour_date : (string) ( $payload['tour_date'] ?? '—' ) ); ?></dd>
				<?php endif; ?>
				<dt><?php esc_html_e( 'Customer', 'igp-pro' ); ?></dt><dd><?php echo esc_html( trim( $first . ' ' . $last ) ); ?></dd>
				<dt><?php esc_html_e( 'Email', 'igp-pro' ); ?></dt><dd><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></dd>
				<dt><?php esc_html_e( 'Phone', 'igp-pro' ); ?></dt><dd><?php echo esc_html( $phone ); ?></dd>
				<?php if ( '' !== $txn ) : ?><dt><?php esc_html_e( 'Transaction', 'igp-pro' ); ?></dt><dd><?php echo esc_html( $txn ); ?></dd><?php endif; ?>
			</dl>
		</section>
		<section class="igp-booking-admin-box">
			<h2><?php esc_html_e( 'Update status', 'igp-pro' ); ?></h2>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="igp_pro_update_submission_status">
				<input type="hidden" name="submission_id" value="<?php echo esc_attr( $submission_id ); ?>">
				<?php wp_nonce_field( 'igp_pro_update_submission_status_' . $submission_id ); ?>
				<select name="submission_status">
					<?php foreach ( igp_pro_get_submission_status_options() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<button class="button button-primary" type="submit"><?php esc_html_e( 'Update', 'igp-pro' ); ?></button>
			</form>
		</section>
	</div>
	<div class="igp-booking-detail-grid">
		<section class="igp-booking-admin-box">
			<h2><?php esc_html_e( 'Submission payload', 'igp-pro' ); ?></h2>
			<pre><?php echo esc_html( wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
		</section>
		<section class="igp-booking-admin-box">
			<h2><?php esc_html_e( 'Pricing payload', 'igp-pro' ); ?></h2>
			<pre><?php echo esc_html( wp_json_encode( $pricing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
		</section>
	</div>
	<?php
}

/**
 * Handle status update.
 */
function igp_pro_handle_submission_status_update(): void {
	$submission_id = isset( $_POST['submission_id'] ) ? absint( $_POST['submission_id'] ) : 0;

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to update this submission.', 'igp-pro' ) );
	}

	if ( ! wp_verify_nonce( isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '', 'igp_pro_update_submission_status_' . $submission_id ) ) {
		wp_die( esc_html__( 'Security check failed.', 'igp-pro' ) );
	}

	$status = isset( $_POST['submission_status'] ) ? sanitize_key( wp_unslash( $_POST['submission_status'] ) ) : '';
	if ( ! array_key_exists( $status, igp_pro_get_submission_status_options() ) ) {
		wp_die( esc_html__( 'Invalid status.', 'igp-pro' ) );
	}

	if ( IGP_PRO_BOOKING_POST_TYPE !== get_post_type( $submission_id ) ) {
		wp_die( esc_html__( 'Submission not found.', 'igp-pro' ) );
	}

	update_post_meta( $submission_id, '_igp_submission_status', $status );
	wp_safe_redirect( admin_url( 'admin.php?page=igp-pro-bookings&submission_id=' . $submission_id . '&updated=1' ) );
	exit;
}
