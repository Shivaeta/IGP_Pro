<?php
/**
 * Rich Text block render.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

$eyebrow   = isset( $resolved_data['eyebrow'] ) ? sanitize_text_field( (string) $resolved_data['eyebrow'] ) : '';
$title     = isset( $resolved_data['title'] ) ? sanitize_text_field( (string) $resolved_data['title'] ) : '';
$content   = isset( $resolved_data['content'] ) ? wp_kses_post( (string) $resolved_data['content'] ) : '';
$alignment = igp_pro_enum( $resolved_data['alignment'] ?? 'left', array( 'left', 'center', 'right' ), 'left' );
$width     = igp_pro_enum( $resolved_data['width'] ?? 'normal', array( 'normal', 'wide', 'narrow' ), 'normal' );
$variant   = igp_pro_enum( function_exists( 'igp_pro_get_legacy_visual_variant' ) ? igp_pro_get_legacy_visual_variant( 'rich_text', $resolved_data, 'default' ) : 'default', array( 'default', 'lead', 'panel', 'quote' ), 'default' );

if ( '' === trim( wp_strip_all_tags( $content ) ) && '' === $title && '' === $eyebrow ) {
	return '';
}
?>
<section class="igp-pro-block igp-pro-rich-text igp-pro-rich-text--<?php echo esc_attr( $variant ); ?> igp-pro-rich-text--<?php echo esc_attr( $alignment ); ?> igp-pro-rich-text--<?php echo esc_attr( $width ); ?>">
	<div class="igp-pro-rich-text__inner">
		<?php if ( '' !== $eyebrow ) : ?>
			<p class="igp-pro-rich-text__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $title ) : ?>
			<h2 class="igp-pro-rich-text__title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>
		<?php if ( '' !== trim( $content ) ) : ?>
			<div class="igp-pro-rich-text__content"><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
		<?php endif; ?>
	</div>
</section>
