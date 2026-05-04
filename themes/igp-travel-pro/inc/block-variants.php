<?php
/**
 * Phase 1 UI block variation map.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;

function igp_travel_pro_get_block_variants(): array {
	$path = IGP_TRAVEL_PRO_DIR . 'assets/data/block-variants.json';
	if ( ! file_exists( $path ) ) {
		return array();
	}
	$data = json_decode( (string) file_get_contents( $path ), true );
	return is_array( $data ) ? $data : array();
}

add_action( 'wp_enqueue_scripts', 'igp_travel_pro_expose_variant_map', 30 );
function igp_travel_pro_expose_variant_map(): void {
	$map = igp_travel_pro_get_block_variants();
	if ( empty( $map ) ) {
		return;
	}
	wp_register_script( 'igp-travel-pro-variants', '', array(), IGP_TRAVEL_PRO_VERSION, true );
	wp_enqueue_script( 'igp-travel-pro-variants' );
	wp_add_inline_script(
		'igp-travel-pro-variants',
		'window.IGPTravelProVariants = ' . wp_json_encode( $map ) . ';',
		'before'
	);
}
