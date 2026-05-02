<?php
/**
 * Hero block render callback.
 *
 * Expects $resolved_data from the central renderer.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_hero' ) ) {
	/**
	 * Render the Hero block.
	 *
	 * @param array $data Resolved hero block data.
	 * @return string
	 */
	function igp_pro_render_hero( array $data ): string {
		$heading          = isset( $data['heading'] ) ? trim( igp_pro_to_string( $data['heading'] ) ) : '';
		$subheading       = isset( $data['subheading'] ) ? trim( igp_pro_to_string( $data['subheading'] ) ) : '';
		$background_image = $data['background_image'] ?? '';
		$background_url   = igp_pro_get_image_url( $background_image );
		$background_alt   = igp_pro_get_image_alt( $background_image, $heading );
		$cta              = isset( $data['cta'] ) && is_array( $data['cta'] ) ? $data['cta'] : array();
		$cta_label        = isset( $cta['label'] ) ? trim( igp_pro_to_string( $cta['label'] ) ) : '';
		$cta_url          = isset( $cta['url'] ) ? esc_url( igp_pro_to_string( $cta['url'] ) ) : '';
		$enable_search    = ! empty( $data['enable_search'] );

		if ( '' === $heading || '' === $background_url ) {
			return igp_pro_render_block_fallback( 'hero', 'missing_required_fields' );
		}

		ob_start();
		?>
		<section class="igp-pro-hero" aria-label="<?php echo esc_attr( $heading ); ?>">
			<div class="igp-pro-hero__media" aria-hidden="true">
				<img class="igp-pro-hero__image" src="<?php echo esc_url( $background_url ); ?>" alt="<?php echo esc_attr( $background_alt ); ?>" loading="eager" decoding="async" />
			</div>
			<div class="igp-pro-hero__content">
				<h1 class="igp-pro-hero__heading"><?php echo esc_html( $heading ); ?></h1>

				<?php if ( '' !== $subheading ) : ?>
					<p class="igp-pro-hero__subheading"><?php echo esc_html( $subheading ); ?></p>
				<?php endif; ?>

				<?php if ( '' !== $cta_label && '' !== $cta_url ) : ?>
					<a class="igp-pro-hero__cta" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
				<?php endif; ?>

				<?php if ( $enable_search ) : ?>
					<form class="igp-pro-hero__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<label class="screen-reader-text" for="igp-pro-hero-search"><?php esc_html_e( 'Search tours and destinations', 'igp-pro' ); ?></label>
						<input id="igp-pro-hero-search" class="igp-pro-hero__search-input" type="search" name="s" placeholder="<?php echo esc_attr__( 'Search tours and destinations', 'igp-pro' ); ?>" />
						<button class="igp-pro-hero__search-submit" type="submit"><?php esc_html_e( 'Search', 'igp-pro' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_hero( $resolved_data ?? array() );
