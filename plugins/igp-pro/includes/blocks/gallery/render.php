<?php
/**
 * Gallery block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_gallery' ) ) {
	/**
	 * Render gallery block.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_gallery( array $data ): string {
		$title   = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$images  = igp_pro_normalize_list( $data['images'] ?? array() );
		$layout  = igp_pro_enum( $data['layout'] ?? 'grid', array( 'grid', 'masonry', 'slider' ), 'grid' );
		$columns = igp_pro_int_range( $data['columns'] ?? 3, 3, 1, 4 );

		ob_start();
		?>
		<section class="igp-pro-gallery igp-pro-gallery--<?php echo esc_attr( $layout ); ?> igp-pro-gallery--columns-<?php echo esc_attr( (string) $columns ); ?>">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $images ) ) : ?>
				<div class="igp-pro-gallery__grid">
					<?php foreach ( $images as $image ) : ?>
						<?php $url = isset( $image['url'] ) ? esc_url( igp_pro_to_string( $image['url'] ) ) : ''; ?>
						<?php if ( '' === $url ) { continue; } ?>
						<figure class="igp-pro-gallery__item">
							<img class="igp-pro-gallery__image" src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $image['alt'] ?? '' ); ?>" loading="lazy" decoding="async" />
							<?php if ( ! empty( $image['caption'] ) ) : ?>
								<figcaption class="igp-pro-gallery__caption"><?php echo esc_html( $image['caption'] ); ?></figcaption>
							<?php endif; ?>
						</figure>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="igp-pro-empty-state"><?php esc_html_e( 'No gallery images added.', 'igp-pro' ); ?></p>
			<?php endif; ?>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_gallery( $resolved_data ?? array() );
