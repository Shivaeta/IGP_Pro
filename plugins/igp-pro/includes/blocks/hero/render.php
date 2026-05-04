<?php
/**
 * Hero block render callback.
 *
 * Expects $resolved_data and $context from the central renderer.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_hero' ) ) {
	/**
	 * Render the Hero block.
	 *
	 * @param array<string,mixed> $data    Resolved hero block data.
	 * @param array<string,mixed> $context Render context.
	 * @return string
	 */
	function igp_pro_render_hero( array $data, array $context = array() ): string {
		$heading_obj      = function_exists( 'igp_pro_normalize_block_heading' ) ? igp_pro_normalize_block_heading( $data, 'hero', $context ) : array( 'text' => igp_pro_to_string( $data['heading'] ?? '' ), 'level' => 'h2', 'eyebrow' => '', 'visible' => true );
		$heading          = trim( (string) ( $heading_obj['text'] ?? '' ) );
		$heading_level    = in_array( (string) ( $heading_obj['level'] ?? 'h2' ), array( 'h2', 'h3', 'h4' ), true ) ? (string) $heading_obj['level'] : 'h2';
		$heading_visible  = ! empty( $heading_obj['visible'] );
		$subheading       = isset( $data['subheading'] ) ? trim( igp_pro_to_string( $data['subheading'] ) ) : '';
		$background_image = $data['background_image'] ?? '';
		$background_url   = igp_pro_get_image_url( $background_image );
		$background_alt   = igp_pro_get_image_alt( $background_image, $heading );
		$cta              = isset( $data['cta'] ) && is_array( $data['cta'] ) ? $data['cta'] : array();
		$cta_label        = isset( $cta['label'] ) ? trim( igp_pro_to_string( $cta['label'] ) ) : '';
		$cta_url          = isset( $cta['url'] ) ? esc_url( igp_pro_to_string( $cta['url'] ) ) : '';
		$enable_search    = ! empty( $data['enable_search'] );

		if ( '' === $heading && '' === $subheading && '' === $background_url ) {
			return igp_pro_render_block_fallback( 'hero', 'missing_content' );
		}

		ob_start();
		?>
		<div class="igp-pro-hero" aria-label="<?php echo esc_attr( '' !== $heading ? $heading : __( 'Hero', 'igp-pro' ) ); ?>">
			<div class="igp-pro-hero__inner">
				<?php if ( '' !== $background_url ) : ?>
					<div class="igp-pro-hero__media" aria-hidden="true">
						<img class="igp-pro-hero__image" src="<?php echo esc_url( $background_url ); ?>" alt="<?php echo esc_attr( $background_alt ); ?>" loading="eager" decoding="async" />
					</div>
				<?php endif; ?>

				<div class="igp-pro-hero__content">
					<?php if ( $heading_visible && '' !== $heading ) : ?>
						<<?php echo esc_html( $heading_level ); ?> class="igp-pro-hero__heading"><?php echo esc_html( $heading ); ?></<?php echo esc_html( $heading_level ); ?>>
					<?php endif; ?>

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
			</div>
		</div>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_hero( $resolved_data ?? array(), $context ?? array() );
