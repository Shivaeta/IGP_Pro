<?php
/**
 * Package Tiers / Price Comparison block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_package_tiers' ) ) {
	/**
	 * Render package tiers.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_package_tiers( array $data ): string {
		$title = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$intro = isset( $data['intro'] ) ? trim( igp_pro_to_string( $data['intro'] ) ) : '';
		$note  = isset( $data['note'] ) ? trim( igp_pro_to_string( $data['note'] ) ) : '';
		$tiers = igp_pro_normalize_list( $data['tiers'] ?? array() );

		ob_start();
		?>
		<div class="igp-pro-package-tiers">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== $intro ) : ?>
				<p class="igp-pro-package-tiers__intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $tiers ) ) : ?>
				<div class="igp-pro-package-tiers__grid">
					<?php foreach ( $tiers as $tier ) : ?>
						<?php
						if ( ! is_array( $tier ) ) {
							continue;
						}
						$name = trim( igp_pro_to_string( $tier['name'] ?? '' ) );
						if ( '' === $name ) {
							continue;
						}
						$price    = trim( igp_pro_to_string( $tier['price'] ?? '' ) );
						$currency = trim( igp_pro_to_string( $tier['currency'] ?? '' ) );
						$duration = trim( igp_pro_to_string( $tier['duration'] ?? '' ) );
						$desc     = trim( igp_pro_to_string( $tier['description'] ?? '' ) );
						$features = igp_pro_normalize_list( $tier['features'] ?? array() );
						$highlight = ! empty( $tier['highlight'] );
						$cta_label = trim( igp_pro_to_string( $tier['cta_label'] ?? '' ) );
						$cta_url   = esc_url( igp_pro_to_string( $tier['cta_url'] ?? '' ) );
						?>
						<article class="igp-pro-package-tier<?php echo $highlight ? ' igp-pro-package-tier--highlight' : ''; ?>">
							<header>
								<h3><?php echo esc_html( $name ); ?></h3>
								<?php if ( '' !== $price ) : ?>
									<p class="igp-pro-package-tier__price"><span><?php echo esc_html( $currency ); ?></span><?php echo esc_html( $price ); ?></p>
								<?php endif; ?>
								<?php if ( '' !== $duration ) : ?>
									<p class="igp-pro-package-tier__duration"><?php echo esc_html( $duration ); ?></p>
								<?php endif; ?>
							</header>

							<?php if ( '' !== $desc ) : ?>
								<p><?php echo esc_html( $desc ); ?></p>
							<?php endif; ?>

							<?php if ( ! empty( $features ) ) : ?>
								<ul>
									<?php foreach ( $features as $feature ) : ?>
										<?php $feature_text = is_array( $feature ) ? trim( igp_pro_to_string( $feature['item'] ?? '' ) ) : trim( igp_pro_to_string( $feature ) ); ?>
										<?php if ( '' !== $feature_text ) : ?>
											<li><?php echo esc_html( $feature_text ); ?></li>
										<?php endif; ?>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>

							<?php if ( '' !== $cta_url && '' !== $cta_label ) : ?>
								<a class="igp-pro-button" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $note ) : ?>
				<p class="igp-pro-package-tiers__note"><?php echo esc_html( $note ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_package_tiers( $resolved_data ?? array() );
