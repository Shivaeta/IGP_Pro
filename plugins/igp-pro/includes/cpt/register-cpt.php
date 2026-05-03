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


/**
 * Register read-only Post ID UI for Tour and Destination admin screens.
 */
function igp_pro_register_cpt_post_id_admin_ui(): void {
	add_action( 'add_meta_boxes_tour', 'igp_pro_add_cpt_post_id_meta_box' );
	add_action( 'add_meta_boxes_destination', 'igp_pro_add_cpt_post_id_meta_box' );

	add_filter( 'manage_tour_posts_columns', 'igp_pro_add_cpt_post_id_column' );
	add_filter( 'manage_destination_posts_columns', 'igp_pro_add_cpt_post_id_column' );
	add_action( 'manage_tour_posts_custom_column', 'igp_pro_render_cpt_post_id_column', 10, 2 );
	add_action( 'manage_destination_posts_custom_column', 'igp_pro_render_cpt_post_id_column', 10, 2 );
}

/**
 * Add the read-only Post ID meta box.
 *
 * @param WP_Post $post Current post.
 */
function igp_pro_add_cpt_post_id_meta_box( WP_Post $post ): void {
	add_meta_box(
		'igp_pro_post_id',
		__( 'IGP Post ID', 'igp-pro' ),
		'igp_pro_render_cpt_post_id_meta_box',
		sanitize_key( $post->post_type ),
		'side',
		'high'
	);
}

/**
 * Render the read-only Post ID meta box.
 *
 * @param WP_Post $post Current post.
 */
function igp_pro_render_cpt_post_id_meta_box( WP_Post $post ): void {
	?>
	<p class="description"><?php esc_html_e( 'Use this ID in IGP relationships, MCP changesets, and Content Graph targeting.', 'igp-pro' ); ?></p>
	<input type="text" readonly class="widefat code" value="<?php echo esc_attr( (string) $post->ID ); ?>" onclick="this.select();">
	<?php
}

/**
 * Add a compact Post ID column to Tour/Destination lists.
 *
 * @param array<string,string> $columns Existing columns.
 * @return array<string,string>
 */
function igp_pro_add_cpt_post_id_column( array $columns ): array {
	$next = array();
	foreach ( $columns as $key => $label ) {
		$next[ $key ] = $label;
		if ( 'cb' === $key ) {
			$next['igp_post_id'] = __( 'ID', 'igp-pro' );
		}
	}

	if ( ! isset( $next['igp_post_id'] ) ) {
		$next['igp_post_id'] = __( 'ID', 'igp-pro' );
	}

	return $next;
}

/**
 * Render the compact Post ID column.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function igp_pro_render_cpt_post_id_column( string $column, int $post_id ): void {
	if ( 'igp_post_id' !== $column ) {
		return;
	}

	echo '<code>' . esc_html( (string) absint( $post_id ) ) . '</code>';
}
