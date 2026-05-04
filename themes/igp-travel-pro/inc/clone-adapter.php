<?php
/**
 * Post-render clone adapter for the IGP UI Block Variant Library.
 *
 * This file does not edit IGP Pro plugin code. It configures the active theme
 * to receive the plugin's semantic/style wrappers and then adds the exact
 * reference vocabulary classes to renderable IGP Pro elements.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ensure the plugin emits the wrapper and variant classes required for theme cloning.
 *
 * The previous package missed this: when a site had old feature-flag options set
 * to false, the renderer returned only inner igp-pro-* markup, so variant CSS
 * such as .igp-block--hero.igp-variant--split-overlay could never match.
 */
function igp_travel_pro_ensure_clone_rendering_flags(): void {
	if ( ! function_exists( 'igp_update_feature_flag' ) ) {
		return;
	}

	foreach ( array( 'enable_semantic_outline', 'enable_smart_block_variants', 'enable_brand_engine' ) as $flag ) {
		igp_update_feature_flag( $flag, true );
	}
}
add_action( 'after_switch_theme', 'igp_travel_pro_ensure_clone_rendering_flags' );
add_action( 'init', 'igp_travel_pro_ensure_clone_rendering_flags', 2 );

/**
 * Return true when a post has a non-empty IGP Pro Content Graph.
 */
function igp_travel_pro_post_has_graph( int $post_id ): bool {
	if ( $post_id <= 0 || ! function_exists( 'igp_pro_load_content_graph' ) ) {
		return false;
	}
	$graph = igp_pro_load_content_graph( $post_id );
	return is_array( $graph ) && ! empty( $graph['sections'] ) && is_array( $graph['sections'] );
}

/**
 * Add a class token to every element that already contains a given class.
 */
function igp_travel_pro_add_clone_class( string $html, string $existing_class, string $new_class ): string {
	$existing_class = preg_quote( $existing_class, '/' );
	$new_class      = trim( $new_class );
	if ( '' === $new_class || '' === $html ) {
		return $html;
	}

	return (string) preg_replace_callback(
		'/class="([^"]*\b' . $existing_class . '\b[^"]*)"/i',
		static function ( array $matches ) use ( $new_class ): string {
			$classes = preg_split( '/\s+/', trim( (string) $matches[1] ) );
			$classes = is_array( $classes ) ? $classes : array();
			foreach ( preg_split( '/\s+/', $new_class ) as $class ) {
				$class = trim( (string) $class );
				if ( '' !== $class && ! in_array( $class, $classes, true ) ) {
					$classes[] = $class;
				}
			}
			return 'class="' . esc_attr( implode( ' ', $classes ) ) . '"';
		},
		$html
	);
}

/**
 * Mark the block wrapper with the exact reference ID used in the audit.
 */
function igp_travel_pro_add_reference_data_attributes( string $html ): string {
	$map = array(
		'hero' => '01-foundation-conversion.html#hero',
		'cta' => '01-foundation-conversion.html#cta',
		'rich-text' => '01-foundation-conversion.html#rich-text',
		'section' => '01-foundation-conversion.html#section-wrapper',
		'trust' => '01-foundation-conversion.html#trust',
		'brochure-cta' => '01-foundation-conversion.html#brochure-cta',
		'tour-cards' => '02-listing-card-systems.html#tour-cards',
		'destination-cards' => '02-listing-card-systems.html#destination-cards',
		'featured-listings' => '02-listing-card-systems.html#featured-listings',
		'related-tours' => '02-listing-card-systems.html#related-tours',
		'related-destinations' => '02-listing-card-systems.html#related-destinations',
		'gallery' => '03-visual-proof-local-context.html#gallery',
		'nearby-attractions' => '03-visual-proof-local-context.html#nearby-attractions',
		'expert-box' => '03-visual-proof-local-context.html#expert-box',
		'reviews-summary' => '03-visual-proof-local-context.html#reviews-summary',
		'best-time-to-visit' => '03-visual-proof-local-context.html#best-time-to-visit',
		'itinerary' => '04-journey-logistics.html#itinerary',
		'route-timeline' => '04-journey-logistics.html#route-timeline',
		'tour-facts' => '04-journey-logistics.html#tour-facts',
		'inclusions-exclusions' => '04-journey-logistics.html#inclusions-exclusions',
		'visa-requirements' => '04-journey-logistics.html#visa-requirements',
		'departure-dates' => '04-journey-logistics.html#departure-dates',
		'package-tiers' => '05-pricing-location-booking.html#package-tiers',
		'pricing-summary' => '05-pricing-location-booking.html#pricing-summary',
		'sticky-booking-cta' => '05-pricing-location-booking.html#sticky-booking-cta',
		'map' => '05-pricing-location-booking.html#map',
		'breadcrumb' => '05-pricing-location-booking.html#breadcrumb',
		'faq' => '06-interactive-utility.html#faq',
		'accordions' => '06-interactive-utility.html#accordions',
		'tabs' => '06-interactive-utility.html#tabs',
		'icon-list' => '06-interactive-utility.html#icon-list',
		'stats' => '06-interactive-utility.html#stats',
	);

	foreach ( $map as $block => $reference ) {
		$html = (string) preg_replace(
			'/<section([^>]*class="[^"]*\bigp-block--' . preg_quote( $block, '/' ) . '\b[^"]*"[^>]*)>/i',
			'<section$1 data-igp-clone-ref="' . esc_attr( $reference ) . '">',
			$html
		);
	}
	return $html;
}

/**
 * Inject reference vocabulary classes into actual renderable elements.
 */
function igp_travel_pro_apply_reference_clone_classes( string $html ): string {
	if ( '' === trim( $html ) ) {
		return $html;
	}

	// Generic typography and primitives.
	$pairs = array(
		'igp-block__eyebrow' => 'kicker',
		'igp-pro-block-eyebrow' => 'kicker',
		'igp-block__heading' => 'title-lg',
		'igp-pro-block-title' => 'title-lg',
		'igp-pro-block-description' => 'text',
		'igp-pro-button' => 'btn',
		'igp-pro-button--secondary' => 'btn-secondary',
		'igp-pro-cta__button' => 'btn',
		'igp-pro-cta__button--secondary' => 'btn-secondary',
		'igp-pro-card__cta' => 'btn',
		'igp-pro-hero__cta' => 'btn',
		'igp-pro-card__badge' => 'chip',
		'igp-pro-departure-dates__status' => 'chip',
		'igp-pro-visa-requirements__status' => 'chip',

		// Cards/listing.
		'igp-pro-card' => 'listing-card',
		'igp-pro-card__media' => 'media',
		'igp-pro-card__body' => 'listing-body',
		'igp-pro-card__title' => 'title-sm',
		'igp-pro-card__excerpt' => 'text',
		'igp-pro-card__meta' => 'listing-meta',

		// Hero.
		'igp-pro-hero__media' => 'media',
		'igp-pro-hero__heading' => 'title-xl',
		'igp-pro-hero__subheading' => 'text',

		// Journey.
		'igp-pro-itinerary__day' => 'day-card',
		'igp-pro-itinerary__day-title' => 'title-sm',
		'igp-pro-itinerary__description' => 'text',
		'igp-pro-route-timeline__stop' => 'route-stop',
		'igp-pro-tour-facts__item' => 'fact',
		'igp-pro-tour-facts__value' => 'fact-value',

		// Proof/local.
		'igp-pro-gallery__item' => 'gallery-item',
		'igp-pro-gallery__caption' => 'text',
		'igp-pro-expert-box__card' => 'expert-card',
		'igp-pro-nearby-attraction-card' => 'nearby-mini',
		'igp-pro-review-card' => 'testimonial',
		'igp-pro-season-card' => 'panel',

		// Pricing/booking.
		'igp-pro-package-tier' => 'price-card',
		'igp-pro-package-tier--highlight' => 'highlight',
		'igp-pro-package-tier__duration' => 'kicker',
		'igp-pro-package-tier__price' => 'price',
		'igp-pro-pricing-summary' => 'summary-price',
		'igp-pro-map__iframe' => 'map-frame',
		'igp-pro-breadcrumb' => 'breadcrumb',

		// Utility.
		'igp-pro-faq__items' => 'faq-list',
		'igp-pro-faq__item' => 'faq-item',
		'igp-pro-accordions__items' => 'accordion-list',
		'igp-pro-accordions__item' => 'accordion-item',
		'igp-pro-tabs__nav' => 'tab-nav',
		'igp-pro-tabs__tab' => 'tab-btn',
		'igp-pro-tabs__panel' => 'tab-panel',
		'igp-pro-icon-list__items' => 'icon-grid',
		'igp-pro-icon-list__item' => 'icon-card',
		'igp-pro-icon-list__icon' => 'icon-dot',
		'igp-pro-stats__item' => 'stat-card',
		'igp-pro-stats__value' => 'metric',

		// Section/trust/brochure.
		'igp-pro-section__inner' => 'panel',
		'igp-pro-trust__item' => 'testimonial',
		'igp-pro-trust__logo' => 'logo-pill',
	);

	foreach ( $pairs as $existing => $new ) {
		$html = igp_travel_pro_add_clone_class( $html, $existing, $new );
	}

	return igp_travel_pro_add_reference_data_attributes( $html );
}
