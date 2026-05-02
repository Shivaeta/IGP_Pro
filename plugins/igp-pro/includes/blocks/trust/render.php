<?php
/**
 * Trust / Social Proof block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_trust' ) ) {
	function igp_pro_render_trust( array $data ): string {
		$eyebrow = isset( $data['eyebrow'] ) ? trim( igp_pro_to_string( $data['eyebrow'] ) ) : '';
		$title   = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$rating  = isset( $data['rating'] ) ? trim( igp_pro_to_string( $data['rating'] ) ) : '';
		$source  = isset( $data['source'] ) ? trim( igp_pro_to_string( $data['source'] ) ) : '';
		$layout  = igp_pro_enum( $data['layout'] ?? 'cards', array( 'cards', 'strip', 'logos', 'split' ), 'cards' );
		$items   = igp_pro_normalize_list( $data['items'] ?? array() );
		$logos   = igp_pro_normalize_list( $data['logos'] ?? array() );

		ob_start();
		?>
		<section class="igp-pro-trust igp-pro-trust--<?php echo esc_attr( $layout ); ?>">
			<header class="igp-pro-block-header">
				<?php if ( '' !== $eyebrow ) : ?><p class="igp-pro-block-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
				<?php if ( '' !== $title ) : ?><h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
				<?php if ( '' !== $rating || '' !== $source ) : ?>
					<p class="igp-pro-trust__summary">
						<?php if ( '' !== $rating ) : ?><strong><span aria-hidden="true">★</span><?php echo esc_html( $rating ); ?></strong><?php endif; ?>
						<?php if ( '' !== $source ) : ?><span><?php echo esc_html( $source ); ?></span><?php endif; ?>
					</p>
				<?php endif; ?>
			</header>

			<?php if ( ! empty( $logos ) ) : ?>
				<div class="igp-pro-trust__logos" aria-label="<?php esc_attr_e( 'Partner and trust badges', 'igp-pro' ); ?>">
					<?php foreach ( $logos as $logo ) : ?>
						<?php
						$logo_url = esc_url( igp_pro_to_string( $logo['url'] ?? $logo['image'] ?? '' ) );
						$logo_alt = sanitize_text_field( igp_pro_to_string( $logo['alt'] ?? $logo['label'] ?? '' ) );
						$link     = esc_url( igp_pro_to_string( $logo['link'] ?? '' ) );
						$label    = sanitize_text_field( igp_pro_to_string( $logo['label'] ?? '' ) );
						?>
						<?php if ( '' !== $logo_url || '' !== $label ) : ?>
							<div class="igp-pro-trust__logo">
								<?php if ( '' !== $link ) : ?><a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"><?php endif; ?>
								<?php if ( '' !== $logo_url ) : ?><img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>" loading="lazy" decoding="async"><?php endif; ?>
								<?php if ( '' !== $label ) : ?><span><?php echo esc_html( $label ); ?></span><?php endif; ?>
								<?php if ( '' !== $link ) : ?></a><?php endif; ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $items ) ) : ?>
				<div class="igp-pro-trust__items">
					<?php foreach ( $items as $item ) : ?>
						<figure class="igp-pro-trust__item">
							<?php if ( ! empty( $item['image'] ) || ! empty( $item['avatar'] ) ) : ?>
								<img class="igp-pro-trust__avatar" src="<?php echo esc_url( igp_pro_to_string( $item['image'] ?? $item['avatar'] ) ); ?>" alt="<?php echo esc_attr( igp_pro_to_string( $item['name'] ?? '' ) ); ?>" loading="lazy" decoding="async">
							<?php endif; ?>
							<?php if ( ! empty( $item['quote'] ) ) : ?><blockquote><?php echo igp_pro_kses_content( $item['quote'] ); ?></blockquote><?php endif; ?>
							<figcaption>
								<?php if ( ! empty( $item['name'] ) ) : ?><strong><?php echo esc_html( $item['name'] ); ?></strong><?php endif; ?>
								<?php if ( ! empty( $item['label'] ) ) : ?><span><?php echo esc_html( $item['label'] ); ?></span><?php endif; ?>
							</figcaption>
						</figure>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_trust( $resolved_data ?? array() );
