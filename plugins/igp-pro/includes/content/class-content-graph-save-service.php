<?php
/**
 * Snapshot-aware Content Graph save service.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IGP_Content_Graph_Save_Service' ) ) {
	/**
	 * Central Content Graph write pipeline.
	 */
	class IGP_Content_Graph_Save_Service {
		/**
		 * Save a graph and all derived mirrors through one controlled path.
		 *
		 * @param int                 $post_id Post ID.
		 * @param array|string        $graph   Graph payload.
		 * @param array<string,mixed> $context Context.
		 * @return array<string,mixed>|WP_Error
		 */
		public static function save( int $post_id, $graph, array $context = array() ) {
			if ( $post_id <= 0 ) {
				return new WP_Error( 'igp_pro_invalid_post_id', __( 'A valid post ID is required.', 'igp-pro' ) );
			}

			if ( function_exists( 'get_post' ) && ! get_post( $post_id ) ) {
				return new WP_Error( 'igp_pro_post_not_found', __( 'Post could not be found.', 'igp-pro' ) );
			}

			$capability = isset( $context['capability'] ) ? (string) $context['capability'] : ( function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'content_editor' ) : 'edit_posts' );
			if ( function_exists( 'current_user_can' ) && ! empty( $context['check_capability'] ) ) {
				if ( ! current_user_can( $capability ) || ! current_user_can( 'edit_post', $post_id ) ) {
					return new WP_Error( 'igp_pro_missing_capability', __( 'You do not have permission to save this Content Graph.', 'igp-pro' ) );
				}
			}

			if ( is_string( $graph ) ) {
				$graph = function_exists( 'igp_pro_json_decode_array' ) ? igp_pro_json_decode_array( $graph ) : json_decode( $graph, true );
			}
			if ( is_wp_error( $graph ) ) {
				return $graph;
			}
			if ( ! is_array( $graph ) ) {
				return new WP_Error( 'igp_pro_invalid_graph', __( 'Content graph must be an array or valid JSON object.', 'igp-pro' ) );
			}

			if ( function_exists( 'igp_pro_canonicalize_content_graph' ) ) {
				$graph = igp_pro_canonicalize_content_graph( $graph );
				if ( is_wp_error( $graph ) ) {
					return $graph;
				}
			}

			$validation = function_exists( 'igp_pro_validate_content_graph' ) ? igp_pro_validate_content_graph( $graph ) : true;
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			if ( function_exists( 'igp_pro_sanitize_content_graph_payload' ) ) {
				$graph = igp_pro_sanitize_content_graph_payload( $graph );
				if ( function_exists( 'igp_pro_canonicalize_content_graph' ) ) {
					$graph = igp_pro_canonicalize_content_graph( $graph );
					if ( is_wp_error( $graph ) ) {
						return $graph;
					}
				}
			}

			$before_graph = function_exists( 'igp_pro_load_content_graph' ) ? igp_pro_load_content_graph( $post_id ) : get_post_meta( $post_id, IGP_PRO_CONTENT_GRAPH_META_KEY, true );
			if ( is_wp_error( $before_graph ) ) {
				$before_graph = null;
			}
			$before_post = function_exists( 'get_post' ) ? get_post( $post_id ) : null;
			$before_data = array(
				'graph'            => $before_graph,
				'post_content'     => $before_post instanceof WP_Post ? (string) $before_post->post_content : '',
				'meta_description' => function_exists( 'igp_pro_load_meta_description' ) ? igp_pro_load_meta_description( $post_id ) : get_post_meta( $post_id, '_igp_pro_meta_description', true ),
				'sync_status'      => get_post_meta( $post_id, '_igp_pro_graph_sync_status', true ),
			);

			$snapshot_id = '';
			if ( empty( $context['skip_snapshot'] ) && function_exists( 'igp_create_snapshot' ) ) {
				$snapshot = igp_create_snapshot(
					'content_graph',
					$post_id,
					$before_data,
					array(
						'source_module' => isset( $context['source_module'] ) ? sanitize_key( (string) $context['source_module'] ) : 'content-graph-save-service',
						'actor_type'    => isset( $context['actor_type'] ) ? sanitize_key( (string) $context['actor_type'] ) : 'human',
						'reason'        => isset( $context['reason'] ) ? sanitize_key( (string) $context['reason'] ) : 'content_graph_save',
					)
				);
				if ( is_wp_error( $snapshot ) ) {
					return $snapshot;
				}
				$snapshot_id = (string) $snapshot;
			}

			$save_graph = igp_pro_save_content_graph( $post_id, $graph );
			if ( is_wp_error( $save_graph ) ) {
				self::mark_sync_status( $post_id, 'failed', $save_graph );
				return $save_graph;
			}

			$meta_description = array_key_exists( 'meta_description', $context ) ? sanitize_textarea_field( (string) $context['meta_description'] ) : null;
			if ( null !== $meta_description && function_exists( 'igp_pro_save_meta_description' ) ) {
				$meta_result = igp_pro_save_meta_description( $post_id, $meta_description );
				if ( is_wp_error( $meta_result ) ) {
					self::restore_before_state( $post_id, $before_data );
					self::mark_sync_status( $post_id, 'failed', $meta_result );
					return $meta_result;
				}
			}

			$sync_result = function_exists( 'igp_pro_sync_content_graph_to_post_content' ) ? igp_pro_sync_content_graph_to_post_content( $post_id, $graph ) : true;
			if ( is_wp_error( $sync_result ) ) {
				self::restore_before_state( $post_id, $before_data );
				self::mark_sync_status( $post_id, 'failed', $sync_result );
				return $sync_result;
			}

			$reloaded = function_exists( 'igp_pro_load_content_graph' ) ? igp_pro_load_content_graph( $post_id ) : $graph;
			if ( is_wp_error( $reloaded ) ) {
				self::restore_before_state( $post_id, $before_data );
				self::mark_sync_status( $post_id, 'failed', $reloaded );
				return $reloaded;
			}

			$after_data = array(
				'graph'            => $reloaded,
				'post_content'     => ( function_exists( 'get_post' ) && get_post( $post_id ) instanceof WP_Post ) ? (string) get_post( $post_id )->post_content : '',
				'meta_description' => function_exists( 'igp_pro_load_meta_description' ) ? igp_pro_load_meta_description( $post_id ) : '',
				'sync_status'      => 'synced',
			);

			if ( '' !== $snapshot_id && function_exists( 'igp_pro_update_snapshot_after_data' ) ) {
				igp_pro_update_snapshot_after_data( $snapshot_id, $after_data );
			}

			self::mark_sync_status( $post_id, 'synced', null, $reloaded );

			if ( function_exists( 'igp_pro_log' ) ) {
				igp_pro_log(
					array(
						'actor_type'    => isset( $context['actor_type'] ) ? sanitize_key( (string) $context['actor_type'] ) : 'human',
						'operation'     => 'content_graph_save_service_saved',
						'object_type'   => 'content_graph',
						'object_id'     => $post_id,
						'source_module' => isset( $context['source_module'] ) ? sanitize_key( (string) $context['source_module'] ) : 'content-graph-save-service',
						'status'        => 'success',
						'summary'       => 'Content Graph saved through the canonical save service.',
						'snapshot_id'   => $snapshot_id,
					)
				);
			}

			return array(
				'graph'       => $reloaded,
				'snapshot_id' => $snapshot_id,
				'sync_status' => 'synced',
			);
		}

		/**
		 * Restore graph, post_content, and SEO meta after a failed derived write.
		 *
		 * @param int                 $post_id Post ID.
		 * @param array<string,mixed> $before  Before state.
		 * @return void
		 */
		private static function restore_before_state( int $post_id, array $before ): void {
			if ( array_key_exists( 'graph', $before ) && is_array( $before['graph'] ) ) {
				$encoded = wp_json_encode( $before['graph'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				if ( is_string( $encoded ) ) {
					update_post_meta( $post_id, IGP_PRO_CONTENT_GRAPH_META_KEY, $encoded );
				}
			}
			if ( function_exists( 'wp_update_post' ) && array_key_exists( 'post_content', $before ) ) {
				wp_update_post(
					array(
						'ID'           => $post_id,
						'post_content' => (string) $before['post_content'],
					),
					true
				);
			}
			if ( array_key_exists( 'meta_description', $before ) ) {
				update_post_meta( $post_id, IGP_PRO_META_DESCRIPTION_META_KEY, sanitize_textarea_field( (string) $before['meta_description'] ) );
			}
		}

		/**
		 * Mark graph sync status meta.
		 *
		 * @param int             $post_id Post ID.
		 * @param string          $status Status.
		 * @param WP_Error|null   $error Error.
		 * @param array|null      $graph Graph.
		 * @return void
		 */
		private static function mark_sync_status( int $post_id, string $status, ?WP_Error $error = null, ?array $graph = null ): void {
			update_post_meta( $post_id, '_igp_pro_graph_sync_status', sanitize_key( $status ) );
			update_post_meta( $post_id, '_igp_pro_graph_synced_at', gmdate( 'c' ) );
			if ( null !== $graph ) {
				update_post_meta( $post_id, '_igp_pro_content_graph_checksum', function_exists( 'igp_pro_content_graph_checksum' ) ? igp_pro_content_graph_checksum( $graph ) : '' );
			}
			$post = function_exists( 'get_post' ) ? get_post( $post_id ) : null;
			if ( $post instanceof WP_Post ) {
				update_post_meta( $post_id, '_igp_pro_post_content_checksum', function_exists( 'igp_pro_content_graph_checksum' ) ? igp_pro_content_graph_checksum( (string) $post->post_content ) : '' );
			}
			if ( $error instanceof WP_Error ) {
				update_post_meta( $post_id, '_igp_pro_graph_sync_error', $error->get_error_message() );
			} else {
				delete_post_meta( $post_id, '_igp_pro_graph_sync_error' );
			}
		}
	}
}
