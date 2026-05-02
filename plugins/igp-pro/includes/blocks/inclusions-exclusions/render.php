<?php
/**
 * Inclusions / Exclusions block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_inclusions_exclusions' ) ) {
	/**
	 * Render inclusions and exclusions.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_inclusions_exclusions( array $data ): string {
		$title      = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$intro      = isset( $data['intro'] ) ? trim( igp_pro_to_string( $data['intro'] ) ) : '';
		$note       = isset( $data['note'] ) ? trim( igp_pro_to_string( $data['note'] ) ) : '';
		$inclusions = igp_pro_normalize_list( $data['inclusions'] ?? array() );
		$exclusions = igp_pro_normalize_list( $data['exclusions'] ?? array() );

		$list = static function ( array $items, string $class ): string {
			ob_start();
			?>
			<ul class="<?php echo esc_attr( $class ); ?>">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$item = is_array( $item ) ? $item : array( 'item' => $item );
					$text = trim( igp_pro_to_string( $item['item'] ?? '' ) );
					$note = trim( igp_pro_to_string( $item['note'] ?? '' ) );
					if ( '' === $text ) {
						continue;
					}
					?>
					<li>
						<span><?php echo esc_html( $text ); ?></span>
						<?php if ( '' !== $note ) : ?>
							<small><?php echo esc_html( $note ); ?></small>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			return trim( ob_get_clean() );
		};

		ob_start();
		?>
		<div class="igp-pro-inclusions-exclusions">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== $intro ) : ?>
				<p class="igp-pro-inclusions-exclusions__intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>

			<div class="igp-pro-inclusions-exclusions__columns">
				<div class="igp-pro-inclusions-exclusions__column igp-pro-inclusions-exclusions__column--included">
					<h3><?php esc_html_e( 'Included', 'igp-pro' ); ?></h3>
					<?php echo wp_kses_post( $list( $inclusions, 'igp-pro-inclusions-exclusions__list' ) ); ?>
				</div>
				<div class="igp-pro-inclusions-exclusions__column igp-pro-inclusions-exclusions__column--excluded">
					<h3><?php esc_html_e( 'Not included', 'igp-pro' ); ?></h3>
					<?php echo wp_kses_post( $list( $exclusions, 'igp-pro-inclusions-exclusions__list' ) ); ?>
				</div>
			</div>

			<?php if ( '' !== $note ) : ?>
				<p class="igp-pro-inclusions-exclusions__note"><?php echo esc_html( $note ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_inclusions_exclusions( $resolved_data ?? array() );
