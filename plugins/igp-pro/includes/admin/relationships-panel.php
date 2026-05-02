<?php
/**
 * Relationship management panel for IGP Pro V2.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register relationship panel hooks.
 */
function igp_pro_register_relationships_panel_admin(): void {
	add_action( 'admin_menu', 'igp_pro_register_relationships_panel_menu' );
	add_action( 'admin_enqueue_scripts', 'igp_pro_enqueue_relationships_panel_assets' );
	add_action( 'admin_post_igp_pro_save_relationships_panel', 'igp_pro_handle_save_relationships_panel' );
}

/**
 * Register submenu.
 */
function igp_pro_register_relationships_panel_menu(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'content_editor' ) : 'edit_posts';

	add_submenu_page(
		'igp-pro-content-editor',
		__( 'IGP Relationships', 'igp-pro' ),
		__( 'Relationships', 'igp-pro' ),
		$capability,
		'igp-pro-relationships',
		'igp_pro_render_relationships_panel_page'
	);
}

/**
 * Enqueue relationship panel assets.
 *
 * @param string $hook Admin hook.
 */
function igp_pro_enqueue_relationships_panel_assets( string $hook ): void {
	if ( 'igp-pro_page_igp-pro-relationships' !== $hook ) {
		return;
	}

	$css = 'assets/css/admin.css';
	$js  = 'assets/js/admin-relationships.js';

	if ( file_exists( igp_pro_path( $css ) ) ) {
		wp_enqueue_style( 'igp-pro-admin', igp_pro_url( $css ), array(), igp_pro_asset_version( $css ) );
	}

	if ( file_exists( igp_pro_path( $js ) ) ) {
		wp_enqueue_script( 'igp-pro-admin-relationships', igp_pro_url( $js ), array(), igp_pro_asset_version( $js ), true );
	}
}

/**
 * Render relationships management page.
 */
function igp_pro_render_relationships_panel_page(): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'content_editor' ) : 'edit_posts';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'You do not have permission to manage IGP relationships.', 'igp-pro' ) );
	}

	$object_id = isset( $_GET['object_id'] ) ? absint( $_GET['object_id'] ) : 0;
	$object    = $object_id > 0 ? get_post( $object_id ) : null;
	if ( $object instanceof WP_Post && ! in_array( get_post_type( $object ), array( 'tour', 'destination' ), true ) ) {
		$object = null;
	}

	$tours        = function_exists( 'igp_pro_get_relationship_tour_options' ) ? igp_pro_get_relationship_tour_options() : array();
	$destinations = function_exists( 'igp_pro_get_relationship_destination_options' ) ? igp_pro_get_relationship_destination_options() : array();
	$objects      = array_merge( $tours, $destinations );
	?>
	<div class="wrap igp-pro-admin-wrap">
		<h1><?php esc_html_e( 'IGP Relationships', 'igp-pro' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Manage structured tour/destination relationships through the IGP service layer. This panel does not use WordPress post_parent as the relationship source of truth.', 'igp-pro' ); ?></p>

		<?php if ( isset( $_GET['relationships-updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Relationships saved.', 'igp-pro' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['relationship-error'] ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( wp_unslash( $_GET['relationship-error'] ) ); ?></p></div>
		<?php endif; ?>

		<div class="igp-pro-admin-card">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="igp-pro-relationship-object-picker">
				<input type="hidden" name="page" value="igp-pro-relationships">
				<label for="igp-pro-relationship-object"><strong><?php esc_html_e( 'Choose tour or destination', 'igp-pro' ); ?></strong></label>
				<select id="igp-pro-relationship-object" name="object_id">
					<option value="0"><?php esc_html_e( 'Select an item', 'igp-pro' ); ?></option>
					<?php foreach ( $objects as $item ) : ?>
						<?php if ( ! $item instanceof WP_Post || ! current_user_can( 'edit_post', $item->ID ) ) { continue; } ?>
						<option value="<?php echo esc_attr( (string) $item->ID ); ?>" <?php selected( $object_id, $item->ID ); ?>>
							<?php echo esc_html( ucfirst( (string) get_post_type( $item ) ) . ': ' . get_the_title( $item ) . ' (#' . $item->ID . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Load relationships', 'igp-pro' ), 'secondary', '', false ); ?>
			</form>
		</div>

		<?php if ( $object instanceof WP_Post ) : ?>
			<?php if ( ! current_user_can( 'edit_post', $object->ID ) ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'You cannot edit the selected item.', 'igp-pro' ); ?></p></div>
			<?php else : ?>
				<div class="igp-pro-admin-card">
					<h2><?php echo esc_html( get_the_title( $object ) ); ?> <code><?php echo esc_html( get_post_type( $object ) . ' #' . $object->ID ); ?></code></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="igp_pro_save_relationships_panel">
						<input type="hidden" name="object_id" value="<?php echo esc_attr( (string) $object->ID ); ?>">
						<?php wp_nonce_field( 'igp_pro_save_relationships_panel_' . $object->ID ); ?>
						<?php igp_pro_render_relationship_fields( $object->ID, (string) get_post_type( $object ), igp_pro_get_relationships( $object->ID, true ), 'igp_pro_relationships' ); ?>
						<?php submit_button( __( 'Save relationships', 'igp-pro' ) ); ?>
					</form>

					<?php if ( 'destination' === get_post_type( $object ) && class_exists( 'IGP_Relationships' ) ) : ?>
						<h3><?php esc_html_e( 'Tours currently linked to this destination', 'igp-pro' ); ?></h3>
						<?php $linked_tours = IGP_Relationships::get_tours_for_destination( $object->ID, array( 'posts_per_page' => 20 ) ); ?>
						<?php if ( $linked_tours->have_posts() ) : ?>
							<ul>
								<?php foreach ( $linked_tours->posts as $tour ) : ?>
									<?php if ( $tour instanceof WP_Post ) : ?>
										<li><a href="<?php echo esc_url( get_edit_post_link( $tour->ID ) ); ?>"><?php echo esc_html( get_the_title( $tour ) ); ?></a> <code>#<?php echo esc_html( (string) $tour->ID ); ?></code></li>
									<?php endif; ?>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p><?php esc_html_e( 'No tours are currently linked to this destination.', 'igp-pro' ); ?></p>
						<?php endif; ?>
						<?php wp_reset_postdata(); ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Handle relationship panel save.
 */
function igp_pro_handle_save_relationships_panel(): void {
	$object_id = isset( $_POST['object_id'] ) ? absint( $_POST['object_id'] ) : 0;
	check_admin_referer( 'igp_pro_save_relationships_panel_' . $object_id );

	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'content_editor' ) : 'edit_posts';
	if ( $object_id <= 0 || ! current_user_can( $capability ) || ! current_user_can( 'edit_post', $object_id ) ) {
		wp_die( esc_html__( 'You do not have permission to save IGP relationships.', 'igp-pro' ) );
	}

	$payload = isset( $_POST['igp_pro_relationships'] ) && is_array( $_POST['igp_pro_relationships'] ) ? wp_unslash( $_POST['igp_pro_relationships'] ) : array();
	$result  = igp_pro_save_relationships(
		$object_id,
		$payload,
		array(
			'actor_type'    => 'human',
			'source_module' => 'relationships-panel',
			'reason'        => 'panel_save',
		)
	);

	if ( is_wp_error( $result ) ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'               => 'igp-pro-relationships',
					'object_id'          => $object_id,
					'relationship-error' => rawurlencode( $result->get_error_message() ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                  => 'igp-pro-relationships',
				'object_id'             => $object_id,
				'relationships-updated' => '1',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
