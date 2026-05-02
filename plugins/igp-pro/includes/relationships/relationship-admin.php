<?php
/**
 * Relationship edit surfaces for Tours and Destinations.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register relationship meta boxes and save hooks.
 */
function igp_pro_register_relationship_admin(): void {
	add_action( 'add_meta_boxes', 'igp_pro_add_relationship_meta_boxes' );
	add_action( 'save_post_tour', 'igp_pro_save_relationship_meta_box', 10, 2 );
	add_action( 'save_post_destination', 'igp_pro_save_relationship_meta_box', 10, 2 );
}

/**
 * Add relationship meta boxes to tour and destination edit screens.
 */
function igp_pro_add_relationship_meta_boxes(): void {
	add_meta_box(
		'igp-pro-relationships',
		__( 'IGP Relationships', 'igp-pro' ),
		'igp_pro_render_relationship_meta_box',
		array( 'tour', 'destination' ),
		'normal',
		'default'
	);
}

/**
 * Render relationship meta box.
 *
 * @param WP_Post $post Current post.
 */
function igp_pro_render_relationship_meta_box( WP_Post $post ): void {
	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'content_editor' ) : 'edit_posts';
	if ( ! current_user_can( $capability ) || ! current_user_can( 'edit_post', $post->ID ) ) {
		echo '<p>' . esc_html__( 'You do not have permission to edit IGP relationships.', 'igp-pro' ) . '</p>';
		return;
	}

	wp_nonce_field( 'igp_pro_save_relationships_' . $post->ID, 'igp_pro_relationships_nonce' );

	$relationships = igp_pro_get_relationships( $post->ID, true );
	igp_pro_render_relationship_fields( $post->ID, get_post_type( $post ), $relationships, 'igp_pro_relationships' );
}

/**
 * Save relationship meta box data through the service layer.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function igp_pro_save_relationship_meta_box( int $post_id, WP_Post $post ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST['igp_pro_relationships_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['igp_pro_relationships_nonce'] ) ) : '';
	if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'igp_pro_save_relationships_' . $post_id ) ) {
		return;
	}

	$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'content_editor' ) : 'edit_posts';
	if ( ! current_user_can( $capability ) || ! current_user_can( 'edit_post', $post_id ) ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log(
				array(
					'actor_type'    => is_user_logged_in() ? 'human' : 'anonymous',
					'operation'     => 'relationship_save_permission_denied',
					'object_type'   => 'relationships',
					'object_id'     => $post_id,
					'source_module' => 'relationships',
					'status'        => 'failure',
					'error_code'    => 'igp_pro_missing_capability',
					'summary'       => 'Relationship save denied from post edit screen.',
				)
			);
		}
		return;
	}

	$payload = isset( $_POST['igp_pro_relationships'] ) && is_array( $_POST['igp_pro_relationships'] ) ? wp_unslash( $_POST['igp_pro_relationships'] ) : array();
	$result  = igp_pro_save_relationships(
		$post_id,
		$payload,
		array(
			'actor_type'    => 'human',
			'source_module' => 'relationship-admin',
			'reason'        => 'post_edit_save',
		)
	);

	if ( is_wp_error( $result ) && function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => is_user_logged_in() ? 'human' : 'anonymous',
				'operation'     => 'relationship_save_failed',
				'object_type'   => 'relationships',
				'object_id'     => $post_id,
				'source_module' => 'relationships',
				'status'        => 'failure',
				'error_code'    => $result->get_error_code(),
				'summary'       => $result->get_error_message(),
			)
		);
	}
}

/**
 * Render relationship fields shared by meta box and panel.
 *
 * @param int   $object_id      Current object ID.
 * @param string $object_type   Current post type.
 * @param array $relationships  Relationship data.
 * @param string $field_prefix  Input name prefix.
 */
function igp_pro_render_relationship_fields( int $object_id, string $object_type, array $relationships, string $field_prefix ): void {
	$destinations = function_exists( 'igp_pro_get_relationship_destination_options' ) ? igp_pro_get_relationship_destination_options() : array();
	$tours        = function_exists( 'igp_pro_get_relationship_tour_options' ) ? igp_pro_get_relationship_tour_options() : array();
	?>
	<div class="igp-pro-relationship-fields" data-igp-object-type="<?php echo esc_attr( $object_type ); ?>">
		<?php if ( 'tour' === $object_type ) : ?>
			<p class="description"><?php esc_html_e( 'Use structured relationships instead of relying on WordPress parent/child hierarchy.', 'igp-pro' ); ?></p>

			<p>
				<label for="igp-pro-primary-destination"><strong><?php esc_html_e( 'Primary destination', 'igp-pro' ); ?></strong></label><br>
				<select id="igp-pro-primary-destination" name="<?php echo esc_attr( $field_prefix ); ?>[primary_destination_id]" class="widefat">
					<option value="0"><?php esc_html_e( 'No primary destination', 'igp-pro' ); ?></option>
					<?php foreach ( $destinations as $destination ) : ?>
						<?php if ( ! $destination instanceof WP_Post ) { continue; } ?>
						<option value="<?php echo esc_attr( (string) $destination->ID ); ?>" <?php selected( absint( $relationships['primary_destination_id'] ?? 0 ), $destination->ID ); ?>>
							<?php echo esc_html( get_the_title( $destination ) ); ?> (#<?php echo esc_html( (string) $destination->ID ); ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<?php igp_pro_render_relationship_multi_select( $field_prefix . '[destination_ids][]', __( 'Secondary destinations', 'igp-pro' ), $destinations, (array) ( $relationships['destination_ids'] ?? array() ), $object_id ); ?>
			<?php igp_pro_render_relationship_multi_select( $field_prefix . '[route_stop_ids][]', __( 'Route stops', 'igp-pro' ), $destinations, (array) ( $relationships['route_stop_ids'] ?? array() ), $object_id ); ?>
			<?php igp_pro_render_relationship_multi_select( $field_prefix . '[related_tour_ids][]', __( 'Related tours', 'igp-pro' ), $tours, (array) ( $relationships['related_tour_ids'] ?? array() ), $object_id ); ?>
			<?php igp_pro_render_relationship_multi_select( $field_prefix . '[related_destination_ids][]', __( 'Related destinations', 'igp-pro' ), $destinations, (array) ( $relationships['related_destination_ids'] ?? array() ), $object_id ); ?>
		<?php elseif ( 'destination' === $object_type ) : ?>
			<p class="description"><?php esc_html_e( 'Destination pages can expose related destinations. Tours for this destination are derived from tour relationship data.', 'igp-pro' ); ?></p>
			<?php igp_pro_render_relationship_multi_select( $field_prefix . '[related_destination_ids][]', __( 'Related destinations', 'igp-pro' ), $destinations, (array) ( $relationships['related_destination_ids'] ?? array() ), $object_id ); ?>
			<input type="hidden" name="<?php echo esc_attr( $field_prefix ); ?>[primary_destination_id]" value="0">
			<input type="hidden" name="<?php echo esc_attr( $field_prefix ); ?>[destination_ids]" value="">
			<input type="hidden" name="<?php echo esc_attr( $field_prefix ); ?>[route_stop_ids]" value="">
			<input type="hidden" name="<?php echo esc_attr( $field_prefix ); ?>[related_tour_ids]" value="">
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render a multiple select relationship field.
 *
 * @param string    $name       Field name.
 * @param string    $label      Field label.
 * @param WP_Post[] $posts      Candidate posts.
 * @param int[]     $selected   Selected IDs.
 * @param int       $current_id Current owner ID.
 */
function igp_pro_render_relationship_multi_select( string $name, string $label, array $posts, array $selected, int $current_id = 0 ): void {
	$selected = array_values( array_unique( array_filter( array_map( 'absint', $selected ) ) ) );
	?>
	<p>
		<label><strong><?php echo esc_html( $label ); ?></strong></label><br>
		<select name="<?php echo esc_attr( $name ); ?>" class="widefat igp-pro-relationship-select" multiple size="6">
			<?php foreach ( $posts as $post ) : ?>
				<?php if ( ! $post instanceof WP_Post || $post->ID === $current_id ) { continue; } ?>
				<option value="<?php echo esc_attr( (string) $post->ID ); ?>" <?php selected( in_array( $post->ID, $selected, true ) ); ?>>
					<?php echo esc_html( get_the_title( $post ) ); ?> (#<?php echo esc_html( (string) $post->ID ); ?>)
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<?php
}
