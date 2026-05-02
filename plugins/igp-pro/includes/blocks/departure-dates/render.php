<?php
/**
 * Departure Dates / Availability block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_departure_status_label' ) ) {
	function igp_pro_departure_status_label( string $status ): string {
		$labels = array(
			'available'  => __( 'Available', 'igp-pro' ),
			'limited'    => __( 'Limited seats', 'igp-pro' ),
			'sold_out'   => __( 'Sold out', 'igp-pro' ),
			'on_request' => __( 'On request', 'igp-pro' ),
		);
		return $labels[ sanitize_key( $status ) ] ?? $labels['available'];
	}
}

if ( ! function_exists( 'igp_pro_render_departure_dates' ) ) {
	/**
	 * Render departure dates.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_departure_dates( array $data ): string {
		$title    = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$intro    = isset( $data['intro'] ) ? trim( igp_pro_to_string( $data['intro'] ) ) : '';
		$footnote = isset( $data['footnote'] ) ? trim( igp_pro_to_string( $data['footnote'] ) ) : '';
		$dates    = igp_pro_normalize_list( $data['dates'] ?? array() );

		ob_start();
		?>
		<div class="igp-pro-departure-dates">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== $intro ) : ?>
				<p class="igp-pro-departure-dates__intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $dates ) ) : ?>
				<div class="igp-pro-departure-dates__table-wrap">
					<table class="igp-pro-departure-dates__table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Start', 'igp-pro' ); ?></th>
								<th scope="col"><?php esc_html_e( 'End', 'igp-pro' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Status', 'igp-pro' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Price', 'igp-pro' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Seats', 'igp-pro' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Action', 'igp-pro' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $dates as $date ) : ?>
								<?php
								if ( ! is_array( $date ) ) {
									continue;
								}
								$start = trim( igp_pro_to_string( $date['start_date'] ?? '' ) );
								$end   = trim( igp_pro_to_string( $date['end_date'] ?? '' ) );
								if ( '' === $start ) {
									continue;
								}
								$status = sanitize_key( igp_pro_to_string( $date['status'] ?? 'available' ) );
								$price  = trim( igp_pro_to_string( $date['price'] ?? '' ) );
								$curr   = trim( igp_pro_to_string( $date['currency'] ?? '' ) );
								$seats  = isset( $date['seats_left'] ) ? absint( $date['seats_left'] ) : 0;
								$url    = esc_url( igp_pro_to_string( $date['booking_url'] ?? '' ) );
								$note   = trim( igp_pro_to_string( $date['note'] ?? '' ) );
								?>
								<tr class="igp-pro-departure-dates__row igp-pro-departure-dates__row--<?php echo esc_attr( $status ); ?>">
									<td><?php echo esc_html( $start ); ?></td>
									<td><?php echo esc_html( $end ); ?></td>
									<td><span class="igp-pro-departure-dates__status"><?php echo esc_html( igp_pro_departure_status_label( $status ) ); ?></span><?php if ( '' !== $note ) : ?><small><?php echo esc_html( $note ); ?></small><?php endif; ?></td>
									<td><?php echo esc_html( '' !== $price ? $curr . $price : __( 'On request', 'igp-pro' ) ); ?></td>
									<td><?php echo esc_html( $seats > 0 ? (string) $seats : '—' ); ?></td>
									<td><?php if ( '' !== $url && 'sold_out' !== $status ) : ?><a class="igp-pro-button" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Enquire', 'igp-pro' ); ?></a><?php else : ?>—<?php endif; ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $footnote ) : ?>
				<p class="igp-pro-departure-dates__footnote"><?php echo esc_html( $footnote ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_departure_dates( $resolved_data ?? array() );
