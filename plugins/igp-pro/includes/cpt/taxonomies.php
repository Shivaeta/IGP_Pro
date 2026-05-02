<?php
/**
 * Taxonomy registration.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Phase 1 taxonomies for travel content.
 */
function igp_pro_register_taxonomies(): void {
	if ( ! taxonomy_exists( 'tour_category' ) ) {
		register_taxonomy(
			'tour_category',
			array( 'tour' ),
			array(
				'labels'            => array(
					'name'          => __( 'Tour Categories', 'igp-pro' ),
					'singular_name' => __( 'Tour Category', 'igp-pro' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'tour-category',
					'with_front' => false,
				),
			)
		);
	}

	if ( ! taxonomy_exists( 'travel_region' ) ) {
		register_taxonomy(
			'travel_region',
			array( 'tour', 'destination' ),
			array(
				'labels'            => array(
					'name'          => __( 'Travel Regions', 'igp-pro' ),
					'singular_name' => __( 'Travel Region', 'igp-pro' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'travel-region',
					'with_front' => false,
				),
			)
		);
	}
}
