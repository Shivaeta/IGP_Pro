<?php
/**
 * Expert / Travel Consultant Box block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_expert_box' ) ) {
	/**
	 * Render expert box.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_expert_box( array $data ): string {
		$title       = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$name        = isset( $data['name'] ) ? trim( igp_pro_to_string( $data['name'] ) ) : '';
		$role        = isset( $data['role'] ) ? trim( igp_pro_to_string( $data['role'] ) ) : '';
		$bio         = isset( $data['bio'] ) ? trim( igp_pro_to_string( $data['bio'] ) ) : '';
		$image_url   = function_exists( 'igp_pro_get_image_url' ) ? igp_pro_get_image_url( $data['image'] ?? array() ) : '';
		$image_alt   = function_exists( 'igp_pro_get_image_alt' ) ? igp_pro_get_image_alt( $data['image'] ?? array(), $name ) : $name;
		$phone       = isset( $data['phone'] ) ? trim( igp_pro_to_string( $data['phone'] ) ) : '';
		$email       = isset( $data['email'] ) ? trim( igp_pro_to_string( $data['email'] ) ) : '';
		$cta_label   = isset( $data['cta_label'] ) ? trim( igp_pro_to_string( $data['cta_label'] ) ) : '';
		$cta_url     = esc_url( igp_pro_to_string( $data['cta_url'] ?? '' ) );
		$specialties = igp_pro_normalize_list( $data['specialties'] ?? array() );

		ob_start();
		?>
		<div class="igp-pro-expert-box">
			<?php if ( '' !== $title ) : ?><h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
			<div class="igp-pro-expert-box__card">
				<?php if ( '' !== $image_url ) : ?>
					<img class="igp-pro-expert-box__image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="lazy" />
				<?php endif; ?>
				<div class="igp-pro-expert-box__body">
					<?php if ( '' !== $name ) : ?><h3><?php echo esc_html( $name ); ?></h3><?php endif; ?>
					<?php if ( '' !== $role ) : ?><p class="igp-pro-expert-box__role"><?php echo esc_html( $role ); ?></p><?php endif; ?>
					<?php if ( '' !== $bio ) : ?><p><?php echo esc_html( $bio ); ?></p><?php endif; ?>
					<?php if ( ! empty( $specialties ) ) : ?>
						<ul class="igp-pro-expert-box__specialties">
							<?php foreach ( $specialties as $specialty ) : ?>
								<?php $specialty_text = is_array( $specialty ) ? trim( igp_pro_to_string( $specialty['item'] ?? '' ) ) : trim( igp_pro_to_string( $specialty ) ); ?>
								<?php if ( '' !== $specialty_text ) : ?><li><?php echo esc_html( $specialty_text ); ?></li><?php endif; ?>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<div class="igp-pro-expert-box__actions">
						<?php if ( '' !== $cta_url && '' !== $cta_label ) : ?><a class="igp-pro-button" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a><?php endif; ?>
						<?php if ( '' !== $phone ) : ?><a class="igp-pro-text-link" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
						<?php if ( '' !== $email && is_email( $email ) ) : ?><a class="igp-pro-text-link" href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a><?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_expert_box( $resolved_data ?? array() );
