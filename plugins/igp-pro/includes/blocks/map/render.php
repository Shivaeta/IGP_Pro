<?php
/**
 * Map block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_map' ) ) {
	/**
	 * Render map block.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_map( array $data ): string {
		$title      = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$address    = isset( $data['address'] ) ? trim( igp_pro_to_string( $data['address'] ) ) : '';
		$embed_url  = isset( $data['embed_url'] ) ? esc_url( igp_pro_to_string( $data['embed_url'] ) ) : '';
		$height     = igp_pro_int_range( $data['height'] ?? 360, 360, 160, 720 );
		$link_label = isset( $data['link_label'] ) ? trim( igp_pro_to_string( $data['link_label'] ) ) : __( 'Open map', 'igp-pro' );
		$link_url   = '' !== $embed_url ? $embed_url : ( '' !== $address ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address ) : '' );

		ob_start();
		?>
		<section class="igp-pro-map">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== $address ) : ?>
				<p class="igp-pro-map__address"><?php echo esc_html( $address ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $embed_url ) : ?>
				<iframe class="igp-pro-map__iframe" src="<?php echo esc_url( $embed_url ); ?>" height="<?php echo esc_attr( (string) $height ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?php echo esc_attr( '' !== $title ? $title : __( 'Map', 'igp-pro' ) ); ?>"></iframe>
			<?php elseif ( '' !== $link_url ) : ?>
				<a class="igp-pro-map__link" href="<?php echo esc_url( $link_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link_label ); ?></a>
			<?php else : ?>
				<p class="igp-pro-empty-state"><?php esc_html_e( 'No map location configured.', 'igp-pro' ); ?></p>
			<?php endif; ?>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_map( $resolved_data ?? array() );
