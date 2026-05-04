<?php
/**
 * Section wrapper block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_section_wrapper' ) ) {
	function igp_pro_render_section_wrapper( array $data, string $children = '' ): string {
		$eyebrow     = isset( $data['eyebrow'] ) ? trim( igp_pro_to_string( $data['eyebrow'] ) ) : '';
		$title       = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$description = isset( $data['description'] ) ? trim( igp_pro_to_string( $data['description'] ) ) : '';
		$layout      = igp_pro_enum( $data['layout'] ?? 'grid', array( 'grid', 'list', 'carousel', 'split' ), 'grid' );
		$columns     = igp_pro_int_range( $data['columns'] ?? 3, 3, 1, 4 );
		$spacing     = igp_pro_enum( $data['spacing'] ?? 'normal', array( 'compact', 'normal', 'relaxed' ), 'normal' );
		$variant     = igp_pro_enum( function_exists( 'igp_pro_get_legacy_visual_variant' ) ? igp_pro_get_legacy_visual_variant( 'section', $data, 'default' ) : 'default', array( 'default', 'contained', 'wide', 'panel', 'accent', 'dark' ), 'default' );
		$align       = igp_pro_enum( $data['align'] ?? 'left', array( 'left', 'center', 'right' ), 'left' );
		$background  = igp_pro_enum( $data['background'] ?? 'none', array( 'none', 'soft', 'white', 'sand', 'dark', 'brand' ), 'none' );

		$classes = array(
			'igp-pro-section',
			'igp-pro-section--layout-' . $layout,
			'igp-pro-section--spacing-' . $spacing,
			'igp-pro-section--variant-' . $variant,
			'igp-pro-section--columns-' . $columns,
			'igp-pro-section--align-' . $align,
			'igp-pro-section--bg-' . $background,
		);

		ob_start();
		?>
		<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="--igp-section-columns: <?php echo esc_attr( (string) $columns ); ?>;">
			<div class="igp-pro-section__inner">
				<?php if ( '' !== $eyebrow || '' !== $title || '' !== $description ) : ?>
					<header class="igp-pro-section__header">
						<?php if ( '' !== $eyebrow ) : ?><p class="igp-pro-section__eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
						<?php if ( '' !== $title ) : ?><h2 class="igp-pro-section__title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
						<?php if ( '' !== $description ) : ?><p class="igp-pro-section__description"><?php echo esc_html( $description ); ?></p><?php endif; ?>
					</header>
				<?php endif; ?>

				<div class="igp-pro-section__content">
					<?php echo wp_kses_post( $children ); ?>
				</div>
			</div>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_section_wrapper( $resolved_data ?? array(), isset( $context['children_html'] ) ? (string) $context['children_html'] : ( isset( $context['content'] ) ? (string) $context['content'] : '' ) );
