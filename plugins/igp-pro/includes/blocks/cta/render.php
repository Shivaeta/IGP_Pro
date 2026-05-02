<?php
/**
 * CTA block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_cta' ) ) {
	function igp_pro_render_cta( array $data ): string {
		$eyebrow    = isset( $data['eyebrow'] ) ? trim( igp_pro_to_string( $data['eyebrow'] ) ) : '';
		$heading    = isset( $data['heading'] ) ? trim( igp_pro_to_string( $data['heading'] ) ) : '';
		$subheading = isset( $data['subheading'] ) ? trim( igp_pro_to_string( $data['subheading'] ) ) : '';
		$button     = isset( $data['button'] ) && is_array( $data['button'] ) ? $data['button'] : array();
		$secondary  = isset( $data['secondary_button'] ) && is_array( $data['secondary_button'] ) ? $data['secondary_button'] : array();
		$label      = isset( $button['label'] ) ? trim( igp_pro_to_string( $button['label'] ) ) : '';
		$url        = isset( $button['url'] ) ? esc_url( igp_pro_to_string( $button['url'] ) ) : '';
		$secondary_label = isset( $secondary['label'] ) ? trim( igp_pro_to_string( $secondary['label'] ) ) : '';
		$secondary_url   = isset( $secondary['url'] ) ? esc_url( igp_pro_to_string( $secondary['url'] ) ) : '';
		$alignment  = igp_pro_enum( $data['alignment'] ?? 'center', array( 'center', 'left', 'right' ), 'center' );
		$variant    = igp_pro_enum( $data['variant'] ?? 'solid', array( 'solid', 'outline', 'minimal', 'split', 'dark' ), 'solid' );
		$badges     = igp_pro_normalize_list( $data['badges'] ?? array() );
		$show_badges = ! empty( $data['show_badges'] );

		ob_start();
		?>
		<section class="igp-pro-cta igp-pro-cta--<?php echo esc_attr( $alignment ); ?> igp-pro-cta--<?php echo esc_attr( $variant ); ?>">
			<div class="igp-pro-cta__inner">
				<div class="igp-pro-cta__content">
					<?php if ( '' !== $eyebrow ) : ?><p class="igp-pro-cta__eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
					<?php if ( '' !== $heading ) : ?><h2 class="igp-pro-cta__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
					<?php if ( '' !== $subheading ) : ?><p class="igp-pro-cta__subheading"><?php echo esc_html( $subheading ); ?></p><?php endif; ?>

					<?php if ( $show_badges && ! empty( $badges ) ) : ?>
						<ul class="igp-pro-cta__badges">
							<?php foreach ( $badges as $badge ) : ?>
								<?php $badge_label = trim( igp_pro_to_string( $badge['label'] ?? $badge['value'] ?? '' ) ); ?>
								<?php if ( '' !== $badge_label ) : ?><li><span aria-hidden="true">✓</span><?php echo esc_html( $badge_label ); ?></li><?php endif; ?>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<?php if ( '' !== $label || '' !== $secondary_label ) : ?>
					<div class="igp-pro-cta__actions">
						<?php if ( '' !== $label && '' !== $url ) : ?><a class="igp-pro-cta__button igp-pro-cta__button--primary" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a><?php endif; ?>
						<?php if ( '' !== $secondary_label && '' !== $secondary_url ) : ?><a class="igp-pro-cta__button igp-pro-cta__button--secondary" href="<?php echo esc_url( $secondary_url ); ?>"><?php echo esc_html( $secondary_label ); ?></a><?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_cta( $resolved_data ?? array() );
