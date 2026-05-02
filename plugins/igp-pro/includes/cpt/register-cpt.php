<?php
/**
 * Custom post type registration.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Tours and Destinations custom post types.
 */
function igp_pro_register_post_types(): void {
	if ( ! post_type_exists( 'tour' ) ) {
		register_post_type(
			'tour',
			array(
				'labels'              => array(
					'name'               => __( 'Tours', 'igp-pro' ),
					'singular_name'      => __( 'Tour', 'igp-pro' ),
					'add_new_item'       => __( 'Add New Tour', 'igp-pro' ),
					'edit_item'          => __( 'Edit Tour', 'igp-pro' ),
					'new_item'           => __( 'New Tour', 'igp-pro' ),
					'view_item'          => __( 'View Tour', 'igp-pro' ),
					'search_items'       => __( 'Search Tours', 'igp-pro' ),
					'not_found'          => __( 'No tours found.', 'igp-pro' ),
					'not_found_in_trash' => __( 'No tours found in Trash.', 'igp-pro' ),
				),
				'public'              => true,
				'has_archive'         => 'tours',
				'rewrite'             => array(
					'slug'       => 'tours',
					'with_front' => false,
				),
				'menu_icon'           => 'dashicons-location-alt',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
				'show_in_rest'        => true,
				'show_in_nav_menus'   => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => false,
			)
		);
	}

	if ( ! post_type_exists( 'destination' ) ) {
		register_post_type(
			'destination',
			array(
				'labels'              => array(
					'name'               => __( 'Destinations', 'igp-pro' ),
					'singular_name'      => __( 'Destination', 'igp-pro' ),
					'add_new_item'       => __( 'Add New Destination', 'igp-pro' ),
					'edit_item'          => __( 'Edit Destination', 'igp-pro' ),
					'new_item'           => __( 'New Destination', 'igp-pro' ),
					'view_item'          => __( 'View Destination', 'igp-pro' ),
					'search_items'       => __( 'Search Destinations', 'igp-pro' ),
					'not_found'          => __( 'No destinations found.', 'igp-pro' ),
					'not_found_in_trash' => __( 'No destinations found in Trash.', 'igp-pro' ),
				),
				'public'              => true,
				'has_archive'         => 'destinations',
				'rewrite'             => array(
					'slug'       => 'destinations',
					'with_front' => false,
				),
				'menu_icon'           => 'dashicons-admin-site-alt3',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
				'show_in_rest'        => true,
				'show_in_nav_menus'   => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => false,
			)
		);
	}
}
