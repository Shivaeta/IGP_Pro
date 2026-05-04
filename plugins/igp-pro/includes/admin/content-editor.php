<?php
/**
 * Schema-driven Content Graph editor admin screen.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register admin hooks for the Phase 3 Content Editor.
 */
function igp_pro_register_content_editor_admin(): void {
	add_action( 'admin_menu', 'igp_pro_register_content_editor_menu' );
	add_action( 'admin_enqueue_scripts', 'igp_pro_enqueue_content_editor_assets' );
	add_action( 'wp_ajax_igp_pro_content_editor_bootstrap', 'igp_pro_ajax_content_editor_bootstrap' );
	add_action( 'wp_ajax_igp_pro_search_content_editor_posts', 'igp_pro_ajax_search_content_editor_posts' );
	add_action( 'wp_ajax_igp_pro_load_content_graph', 'igp_pro_ajax_load_content_graph' );
	add_action( 'wp_ajax_igp_pro_save_content_graph', 'igp_pro_ajax_save_content_graph' );
	add_action( 'wp_ajax_igp_pro_import_content_graph', 'igp_pro_ajax_import_content_graph' );
	add_action( 'wp_ajax_igp_pro_export_content_graph', 'igp_pro_ajax_export_content_graph' );
}

/**
 * Register admin menu.
 */
function igp_pro_register_content_editor_menu(): void {
	add_menu_page(
		__( 'IGP Pro', 'igp-pro' ),
		__( 'IGP Pro', 'igp-pro' ),
		function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'content_editor' ) : 'edit_posts',
		'igp-pro-content-editor',
		'igp_pro_render_content_editor_page',
		'dashicons-location-alt',
		26
	);
}

/**
 * Render the admin app shell.
 */
function igp_pro_render_content_editor_page(): void {
	if ( ! current_user_can( function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'content_editor' ) : 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to access the IGP Pro content editor.', 'igp-pro' ) );
	}
	?>
	<div class="wrap igp-pro-admin-wrap">
		<h1><?php esc_html_e( 'IGP Pro Content Editor', 'igp-pro' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Schema-driven Content Graph editing for pages, tours, and destinations.', 'igp-pro' ); ?>
		</p>
		<div id="igp-pro-content-editor" class="igp-pro-content-editor-root">
			<div class="igp-pro-admin-card">
				<p><?php esc_html_e( 'Loading editor…', 'igp-pro' ); ?></p>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Enqueue the admin editor assets only on the IGP Pro page.
 *
 * @param string $hook Current admin hook.
 */
function igp_pro_enqueue_content_editor_assets( string $hook ): void {
	if ( 'toplevel_page_igp-pro-content-editor' !== $hook ) {
		return;
	}

	wp_enqueue_media();

	$css = 'assets/css/admin.css';
	$js  = 'assets/js/content-editor.js';

	if ( file_exists( igp_pro_path( $css ) ) ) {
		wp_enqueue_style( 'igp-pro-admin', igp_pro_url( $css ), array(), igp_pro_asset_version( $css ) );
	}

	if ( file_exists( igp_pro_path( $js ) ) ) {
		wp_enqueue_script( 'igp-pro-content-editor', igp_pro_url( $js ), array( 'jquery' ), igp_pro_asset_version( $js ), true );
		wp_localize_script(
			'igp-pro-content-editor',
			'igpProContentEditor',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'igp_pro_content_editor' ),
				'i18n'    => array(
					'loadError'       => __( 'Could not load content graph.', 'igp-pro' ),
					'saveError'       => __( 'Could not save content graph.', 'igp-pro' ),
					'importError'     => __( 'Import failed.', 'igp-pro' ),
					'confirmDelete'   => __( 'Remove this section?', 'igp-pro' ),
					'unsavedChanges'  => __( 'You have unsaved IGP Pro content graph changes.', 'igp-pro' ),
					'chooseImage'     => __( 'Choose image', 'igp-pro' ),
					'useImage'        => __( 'Use this image', 'igp-pro' ),
					'invalidJson'     => __( 'Invalid JSON.', 'igp-pro' ),
				),
			)
		);
	}
}

/**
 * Check the common nonce and capability for editor requests.
 *
 * @param int|null $post_id Optional post ID.
 * @return true|WP_Error
 */
function igp_pro_content_editor_permission_check( ?int $post_id = null ) {
	$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'igp_pro_content_editor' ) ) {
		return new WP_Error( 'igp_pro_invalid_nonce', __( 'Security check failed.', 'igp-pro' ) );
	}

	if ( ! current_user_can( function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'content_editor' ) : 'edit_posts' ) ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log(
				array(
					'actor_type'    => is_user_logged_in() ? 'human' : 'anonymous',
					'operation'     => 'content_editor_permission_denied',
					'object_type'   => 'content_graph',
					'object_id'     => $post_id ? absint( $post_id ) : 0,
					'source_module' => 'content-editor',
					'status'        => 'failure',
					'error_code'    => 'igp_pro_missing_capability',
					'summary'       => 'Content editor request denied.',
				)
			);
		}
		return new WP_Error( 'igp_pro_missing_capability', __( 'You do not have permission to use the content editor.', 'igp-pro' ) );
	}

	if ( null !== $post_id && $post_id > 0 && ! current_user_can( 'edit_post', $post_id ) ) {
		return new WP_Error( 'igp_pro_cannot_edit_post', __( 'You do not have permission to edit this post.', 'igp-pro' ) );
	}

	return true;
}

/**
 * Send a WP_Error as a JSON error response.
 *
 * @param WP_Error $error Error.
 */
function igp_pro_send_json_error( WP_Error $error ): void {
	wp_send_json_error(
		array(
			'code'    => $error->get_error_code(),
			'message' => $error->get_error_message(),
		),
		400
	);
}

/**
 * AJAX: return post options and block definitions.
 */
function igp_pro_ajax_content_editor_bootstrap(): void {
	$permission = igp_pro_content_editor_permission_check();
	if ( is_wp_error( $permission ) ) {
		igp_pro_send_json_error( $permission );
	}

	wp_send_json_success(
		array(
			'posts'  => igp_pro_get_content_editor_post_options(),
			'blocks' => igp_pro_get_content_editor_block_options(),
		)
	);
}


/**
 * AJAX: search posts available to the content editor.
 */
function igp_pro_ajax_search_content_editor_posts(): void {
	$permission = igp_pro_content_editor_permission_check();
	if ( is_wp_error( $permission ) ) {
		igp_pro_send_json_error( $permission );
	}

	$search = isset( $_REQUEST['search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search'] ) ) : '';
	$limit  = isset( $_REQUEST['limit'] ) ? absint( $_REQUEST['limit'] ) : 80;
	$limit  = min( 120, max( 20, $limit ) );

	wp_send_json_success(
		array(
			'posts'  => igp_pro_get_content_editor_post_options( $search, $limit ),
			'search' => $search,
		)
	);
}

/**
 * AJAX: load graph for a selected post.
 */
function igp_pro_ajax_load_content_graph(): void {
	$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
	$permission = igp_pro_content_editor_permission_check( $post_id );
	if ( is_wp_error( $permission ) ) {
		igp_pro_send_json_error( $permission );
	}

	$loaded = function_exists( 'igp_pro_load_content_graph_for_editor' )
		? igp_pro_load_content_graph_for_editor( $post_id )
		: array(
			'graph'   => igp_pro_load_content_graph( $post_id ),
			'source'  => 'post_meta',
			'message' => __( 'Content Graph loaded.', 'igp-pro' ),
		);

	if ( is_wp_error( $loaded ) ) {
		igp_pro_send_json_error( $loaded );
	}

	$graph = $loaded['graph'] ?? igp_pro_get_empty_content_graph();
	if ( is_wp_error( $graph ) ) {
		igp_pro_send_json_error( $graph );
	}

	wp_send_json_success(
		array(
			'post'             => igp_pro_get_content_editor_post_summary( $post_id ),
			'graph'            => $graph,
			'source'           => $loaded['source'] ?? 'post_meta',
			'message'          => $loaded['message'] ?? __( 'Content Graph loaded.', 'igp-pro' ),
			'meta_description' => igp_pro_load_meta_description( $post_id ),
		)
	);
}

/**
 * AJAX: save graph for selected post.
 */
function igp_pro_ajax_save_content_graph(): void {
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$permission = igp_pro_content_editor_permission_check( $post_id );
	if ( is_wp_error( $permission ) ) {
		igp_pro_send_json_error( $permission );
	}

	$graph_json = isset( $_POST['graph'] ) ? wp_unslash( $_POST['graph'] ) : '';
	$graph      = igp_pro_json_decode_array( (string) $graph_json );
	if ( is_wp_error( $graph ) ) {
		igp_pro_send_json_error( $graph );
	}

	$meta_description = isset( $_POST['meta_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['meta_description'] ) ) : '';

	if ( ! class_exists( 'IGP_Content_Graph_Save_Service' ) ) {
		igp_pro_send_json_error( new WP_Error( 'igp_pro_save_service_missing', __( 'Canonical Content Graph save service is unavailable.', 'igp-pro' ) ) );
	}

	$result = IGP_Content_Graph_Save_Service::save(
		$post_id,
		$graph,
		array(
			'check_capability' => true,
			'capability'       => function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'content_editor' ) : 'edit_posts',
			'meta_description' => $meta_description,
			'source_module'    => 'content-editor',
			'actor_type'       => 'human',
			'reason'           => 'content_editor_save',
		)
	);

	if ( is_wp_error( $result ) ) {
		igp_pro_send_json_error( $result );
	}

	$saved_graph = isset( $result['graph'] ) && is_array( $result['graph'] ) ? $result['graph'] : igp_pro_load_content_graph( $post_id );
	if ( is_wp_error( $saved_graph ) ) {
		igp_pro_send_json_error( $saved_graph );
	}

	wp_send_json_success(
		array(
			'graph'            => $saved_graph,
			'source'           => 'post_meta',
			'snapshot_id'      => $result['snapshot_id'] ?? '',
			'sync_status'      => $result['sync_status'] ?? 'synced',
			'meta_description' => igp_pro_load_meta_description( $post_id ),
			'message'          => __( 'Content Graph saved through the canonical save service and synced to WordPress content.', 'igp-pro' ),
		)
	);
}

/**
 * AJAX: validate and normalize an import payload.
 */
function igp_pro_ajax_import_content_graph(): void {
	$permission = igp_pro_content_editor_permission_check();
	if ( is_wp_error( $permission ) ) {
		igp_pro_send_json_error( $permission );
	}

	$import_capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'import_content' ) : 'edit_posts';
	if ( ! current_user_can( $import_capability ) ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log(
				array(
					'actor_type'    => is_user_logged_in() ? 'human' : 'anonymous',
					'operation'     => 'content_import_permission_denied',
					'object_type'   => 'content_graph',
					'object_id'     => 0,
					'source_module' => 'content-editor',
					'status'        => 'failure',
					'error_code'    => 'igp_pro_missing_capability',
					'summary'       => 'Content Graph import denied.',
				)
			);
		}
		igp_pro_send_json_error( new WP_Error( 'igp_pro_missing_import_capability', __( 'You do not have permission to import Content Graph data.', 'igp-pro' ) ) );
	}

	$payload = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
	$graph   = igp_pro_import_content_graph_payload( (string) $payload );
	if ( is_wp_error( $graph ) ) {
		igp_pro_send_json_error( $graph );
	}

	$description   = '';
	$relationships = null;
	$decoded       = igp_pro_json_decode_array( (string) $payload );
	if ( is_array( $decoded ) && isset( $decoded['meta']['description'] ) ) {
		$description = sanitize_textarea_field( (string) $decoded['meta']['description'] );
	}
	if ( function_exists( 'igp_pro_import_relationship_payload' ) ) {
		$relationships = igp_pro_import_relationship_payload( (string) $payload );
		if ( is_wp_error( $relationships ) ) {
			igp_pro_send_json_error( $relationships );
		}
	}

	wp_send_json_success(
		array(
			'graph'            => $graph,
			'meta_description' => $description,
			'relationships'    => is_array( $relationships ) ? $relationships : null,
			'message'          => __( 'Import validated.', 'igp-pro' ),
		)
	);
}

/**
 * AJAX: export graph for selected post.
 */
function igp_pro_ajax_export_content_graph(): void {
	$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
	$permission = igp_pro_content_editor_permission_check( $post_id );
	if ( is_wp_error( $permission ) ) {
		igp_pro_send_json_error( $permission );
	}

	$export = igp_pro_export_content_graph( $post_id );
	if ( is_wp_error( $export ) ) {
		igp_pro_send_json_error( $export );
	}

	wp_send_json_success( $export );
}

/**
 * Return posts available to the content editor.
 *
 * @return array
 */
function igp_pro_get_content_editor_post_options( string $search = '', int $limit = 200 ): array {
	$search = trim( wp_strip_all_tags( $search ) );
	$limit  = min( 250, max( 20, absint( $limit ) ) );

	$args = array(
		'post_type'              => array( 'page', 'tour', 'destination' ),
		'post_status'            => array( 'publish', 'draft', 'private', 'pending', 'future' ),
		'posts_per_page'         => $limit,
		'orderby'                => 'modified',
		'order'                  => 'DESC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);

	if ( '' !== $search ) {
		$maybe_id = ltrim( $search, '# ' );
		if ( ctype_digit( $maybe_id ) ) {
			$args['post__in'] = array( absint( $maybe_id ) );
			$args['orderby']  = 'post__in';
		} else {
			$args['s'] = $search;
		}
	}

	$query   = new WP_Query( $args );
	$options = array();

	foreach ( $query->posts as $post ) {
		if ( ! $post instanceof WP_Post || ! current_user_can( 'edit_post', $post->ID ) ) {
			continue;
		}

		$options[] = igp_pro_get_content_editor_post_summary( $post->ID );
	}

	return $options;
}

/**
 * Return a compact post summary.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function igp_pro_get_content_editor_post_summary( int $post_id ): array {
	return array(
		'id'        => $post_id,
		'title'     => get_the_title( $post_id ),
		'post_type' => get_post_type( $post_id ),
		'status'    => get_post_status( $post_id ),
		'edit_url'  => get_edit_post_link( $post_id, 'raw' ),
		'view_url'  => get_permalink( $post_id ),
	);
}

/**
 * Return block schema metadata for the content editor.
 *
 * @return array
 */
function igp_pro_get_content_editor_block_options(): array {
	$blocks = array();

	foreach ( igp_pro_get_block_registry() as $block_id => $definition ) {
		$schema = igp_pro_get_block_schema( $definition );
		if ( is_wp_error( $schema ) ) {
			continue;
		}

		$blocks[] = array(
			'id'          => $block_id,
			'title'       => $definition['title'] ?? igp_pro_block_id_to_title( $block_id ),
			'description' => $definition['description'] ?? '',
			'category'    => $definition['category'] ?? ( $schema['category'] ?? 'content' ),
			'schema'      => $schema,
			'defaults'    => igp_pro_content_editor_get_defaults_from_schema( $schema ),
		);
	}

	return $blocks;
}

/**
 * Derive defaults recursively from schema fields.
 *
 * @param array $schema Schema.
 * @return array
 */
function igp_pro_content_editor_get_defaults_from_schema( array $schema ): array {
	$defaults = isset( $schema['defaults'] ) && is_array( $schema['defaults'] ) ? $schema['defaults'] : array();
	$fields   = isset( $schema['fields'] ) && is_array( $schema['fields'] ) ? $schema['fields'] : array();

	foreach ( $fields as $field_name => $field_schema ) {
		if ( array_key_exists( $field_name, $defaults ) || ! is_array( $field_schema ) ) {
			continue;
		}

		$type = isset( $field_schema['type'] ) ? (string) $field_schema['type'] : 'string';

		if ( array_key_exists( 'default', $field_schema ) ) {
			$defaults[ $field_name ] = $field_schema['default'];
		} elseif ( 'object' === $type ) {
			$defaults[ $field_name ] = igp_pro_content_editor_get_defaults_from_schema( array( 'fields' => $field_schema['fields'] ?? array() ) );
		} elseif ( in_array( $type, array( 'array', 'repeater', 'relationship' ), true ) ) {
			$defaults[ $field_name ] = array();
		} elseif ( 'image' === $type ) {
			$defaults[ $field_name ] = array( 'url' => '', 'alt' => '' );
		} elseif ( 'boolean' === $type ) {
			$defaults[ $field_name ] = false;
		} elseif ( 'number' === $type ) {
			$defaults[ $field_name ] = 0;
		} else {
			$defaults[ $field_name ] = '';
		}
	}

	return $defaults;
}
