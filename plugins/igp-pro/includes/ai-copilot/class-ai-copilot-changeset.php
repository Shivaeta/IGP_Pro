<?php
/**
 * AI Copilot changeset storage and human review workflow.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_AI_COPILOT_CHANGESET_DIR' ) ) {
	define( 'IGP_AI_COPILOT_CHANGESET_DIR', IGP_PRO_PATH . 'storage/ai-changesets/' );
}

/**
 * Stores AI-generated compiled content as reviewable changesets.
 */
class IGP_AI_Copilot_Changeset {
	/**
	 * Create a reviewable changeset from YAML.
	 *
	 * @param string $yaml    AI Copilot YAML.
	 * @param array  $context Context: target_post_id, source, actor_type.
	 * @return array|WP_Error
	 */
	public static function create_from_yaml( string $yaml, array $context = array() ): array|WP_Error {
		if ( ! class_exists( 'IGP_AI_Copilot_Service' ) ) {
			return new WP_Error( 'igp_ai_service_missing', __( 'AI Copilot service is unavailable.', 'igp-pro' ) );
		}

		$context = self::sanitize_context( $context );
		$context['source'] = isset( $context['source'] ) && '' !== $context['source'] ? $context['source'] : 'ai_changeset';

		$compiled = IGP_AI_Copilot_Service::compile_yaml( $yaml, $context );
		if ( is_wp_error( $compiled ) ) {
			self::log_event( 'ai_changeset_create_failed', 'failure', 0, $compiled->get_error_code(), $compiled->get_error_message() );
			return $compiled;
		}

		if ( empty( $compiled['content_graph'] ) || ! is_array( $compiled['content_graph'] ) ) {
			return new WP_Error( 'igp_ai_changeset_missing_graph', __( 'Compiled Content Graph is missing.', 'igp-pro' ) );
		}

		$graph_validation = function_exists( 'igp_pro_validate_content_graph' ) ? igp_pro_validate_content_graph( $compiled['content_graph'] ) : true;
		if ( is_wp_error( $graph_validation ) ) {
			self::log_event( 'ai_changeset_create_failed', 'failure', 0, $graph_validation->get_error_code(), $graph_validation->get_error_message() );
			return $graph_validation;
		}

		$target_post_id = isset( $context['target_post_id'] ) ? absint( $context['target_post_id'] ) : 0;
		$target_post    = $target_post_id > 0 ? get_post( $target_post_id ) : null;
		if ( $target_post_id > 0 && ! $target_post instanceof WP_Post ) {
			return new WP_Error( 'igp_ai_changeset_target_missing', __( 'Target post for changeset was not found.', 'igp-pro' ) );
		}

		if ( $target_post instanceof WP_Post && ! current_user_can( 'edit_post', $target_post_id ) ) {
			return new WP_Error( 'igp_ai_changeset_forbidden', __( 'You do not have permission to create a changeset for this post.', 'igp-pro' ) );
		}

		if ( $target_post_id <= 0 && ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( 'igp_ai_changeset_forbidden', __( 'You do not have permission to create AI changesets.', 'igp-pro' ) );
		}

		$original_graph = array();
		if ( $target_post_id > 0 && function_exists( 'igp_pro_load_content_graph' ) ) {
			$loaded = igp_pro_load_content_graph( $target_post_id );
			if ( is_wp_error( $loaded ) ) {
				return $loaded;
			}
			$original_graph = $loaded;
		}

		$snapshot_id = '';
		if ( $target_post_id > 0 && function_exists( 'igp_create_snapshot' ) ) {
			$snapshot = igp_create_snapshot(
				'content_graph',
				$target_post_id,
				$original_graph,
				array(
					'source_module' => 'ai_copilot_changeset',
					'actor_type'    => self::actor_type( $context ),
					'reason'        => 'ai_changeset_created',
					'operation'     => 'create_changeset_from_yaml',
					'after_data'    => $compiled['content_graph'],
				)
			);
			if ( is_wp_error( $snapshot ) ) {
				return $snapshot;
			}
			$snapshot_id = is_string( $snapshot ) ? $snapshot : '';
		}

		$validation = class_exists( 'IGP_AI_Copilot_Service' ) ? IGP_AI_Copilot_Service::validate_yaml( $yaml ) : array();
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$changeset_id = self::generate_id();
		$record       = array(
			'changeset_id'               => $changeset_id,
			'status'                     => 'pending',
			'target_post_id'             => $target_post_id,
			'target_post_type'           => $target_post instanceof WP_Post ? $target_post->post_type : self::content_type_to_post_type( (string) ( $compiled['content_type'] ?? '' ) ),
			'target_post_title'          => $target_post instanceof WP_Post ? get_the_title( $target_post ) : '',
			'target_post_status'         => $target_post instanceof WP_Post ? $target_post->post_status : '',
			'original_content_reference' => array(
				'object_type' => $target_post_id > 0 ? 'post' : 'new_draft',
				'object_id'   => $target_post_id,
				'snapshot_id' => $snapshot_id,
			),
			'original_graph'             => $original_graph,
			'snapshot_id'                => $snapshot_id,
			'yaml_source'                => self::limit_yaml_for_storage( $yaml ),
			'compiled'                   => $compiled,
			'content_graph'              => $compiled['content_graph'],
			'mapping_report'             => isset( $compiled['mapping_report'] ) && is_array( $compiled['mapping_report'] ) ? $compiled['mapping_report'] : array(),
			'validation_result'          => $validation['validation'] ?? array(),
			'seo'                        => isset( $compiled['seo'] ) && is_array( $compiled['seo'] ) ? $compiled['seo'] : array(),
			'media_requirements'         => isset( $compiled['media_requirements'] ) && is_array( $compiled['media_requirements'] ) ? $compiled['media_requirements'] : array(),
			'actor_user_id'              => function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0,
			'actor_type'                 => self::actor_type( $context ),
			'source'                     => isset( $context['source'] ) ? sanitize_key( (string) $context['source'] ) : 'ai_changeset',
			'created_at'                 => gmdate( 'c' ),
			'updated_at'                 => gmdate( 'c' ),
			'approved_at'                => '',
			'rejected_at'                => '',
			'rolled_back_at'             => '',
			'approved_post_id'           => 0,
			'approval_snapshot_id'       => '',
			'rollback_result'            => array(),
			'notes'                      => isset( $context['notes'] ) ? sanitize_textarea_field( (string) $context['notes'] ) : '',
		);

		$save = self::save( $record );
		if ( is_wp_error( $save ) ) {
			return $save;
		}

		self::log_event( 'ai_changeset_created', 'success', $target_post_id, '', 'AI Copilot changeset created.', $changeset_id );

		return self::summarize( $record );
	}

	/**
	 * Approve a pending changeset and save as draft/update draft-safe content.
	 *
	 * @param string $changeset_id Changeset ID.
	 * @param array  $args         Approval args.
	 * @return array|WP_Error
	 */
	public static function approve( string $changeset_id, array $args = array() ): array|WP_Error {
		$record = self::get( $changeset_id );
		if ( is_wp_error( $record ) ) {
			return $record;
		}

		if ( 'pending' !== ( $record['status'] ?? '' ) ) {
			return new WP_Error( 'igp_ai_changeset_not_pending', __( 'Only pending AI changesets can be approved.', 'igp-pro' ) );
		}

		$graph = isset( $record['content_graph'] ) && is_array( $record['content_graph'] ) ? $record['content_graph'] : array();
		if ( empty( $graph ) ) {
			return new WP_Error( 'igp_ai_changeset_missing_graph', __( 'Changeset has no Content Graph to approve.', 'igp-pro' ) );
		}

		$validation = function_exists( 'igp_pro_validate_content_graph' ) ? igp_pro_validate_content_graph( $graph ) : true;
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$target_post_id = absint( $record['target_post_id'] ?? 0 );
		$compiled       = isset( $record['compiled'] ) && is_array( $record['compiled'] ) ? $record['compiled'] : array();
		$post_type      = self::content_type_to_post_type( (string) ( $compiled['content_type'] ?? ( $record['target_post_type'] ?? '' ) ) );
		$approved_id    = $target_post_id;
		$snapshot_id    = '';

		if ( $approved_id > 0 ) {
			$post = get_post( $approved_id );
			if ( ! $post instanceof WP_Post ) {
				return new WP_Error( 'igp_ai_changeset_target_missing', __( 'Target post for this changeset no longer exists.', 'igp-pro' ) );
			}
			if ( ! current_user_can( 'edit_post', $approved_id ) ) {
				return new WP_Error( 'igp_ai_changeset_forbidden', __( 'You do not have permission to approve this changeset.', 'igp-pro' ) );
			}

			$before = function_exists( 'igp_pro_load_content_graph' ) ? igp_pro_load_content_graph( $approved_id ) : array();
			if ( is_wp_error( $before ) ) {
				return $before;
			}
			if ( function_exists( 'igp_create_snapshot' ) ) {
				$snapshot = igp_create_snapshot(
					'content_graph',
					$approved_id,
					$before,
					array(
						'source_module' => 'ai_copilot_changeset',
						'actor_type'    => 'human',
						'reason'        => 'ai_changeset_approved',
						'operation'     => 'approve_ai_changeset',
						'after_data'    => $graph,
					)
				);
				if ( is_wp_error( $snapshot ) ) {
					return $snapshot;
				}
				$snapshot_id = is_string( $snapshot ) ? $snapshot : '';
			}
		} else {
			if ( ! current_user_can( 'edit_posts' ) ) {
				return new WP_Error( 'igp_ai_changeset_forbidden', __( 'You do not have permission to approve this changeset.', 'igp-pro' ) );
			}
			if ( ! post_type_exists( $post_type ) ) {
				return new WP_Error( 'igp_ai_post_type_unavailable', __( 'Target post type is unavailable.', 'igp-pro' ) );
			}
			$approved_id = wp_insert_post(
				array(
					'post_type'    => $post_type,
					'post_status'  => 'draft',
					'post_title'   => sanitize_text_field( (string) ( $compiled['title'] ?? __( 'AI Copilot Draft', 'igp-pro' ) ) ),
					'post_name'    => ! empty( $compiled['slug'] ) ? sanitize_title( (string) $compiled['slug'] ) : '',
					'post_content' => '',
				),
				true
			);
			if ( is_wp_error( $approved_id ) ) {
				return $approved_id;
			}
			$approved_id = absint( $approved_id );
			if ( function_exists( 'igp_create_snapshot' ) ) {
				$snapshot = igp_create_snapshot(
					'content_graph',
					$approved_id,
					function_exists( 'igp_pro_get_empty_content_graph' ) ? igp_pro_get_empty_content_graph() : array(),
					array(
						'source_module' => 'ai_copilot_changeset',
						'actor_type'    => 'human',
						'reason'        => 'ai_changeset_approved_new_draft',
						'operation'     => 'approve_ai_changeset',
						'after_data'    => $graph,
					)
				);
				if ( is_string( $snapshot ) ) {
					$snapshot_id = $snapshot;
				}
			}
		}

		if ( ! function_exists( 'igp_pro_save_content_graph' ) ) {
			return new WP_Error( 'igp_ai_save_service_missing', __( 'Content Graph save service is unavailable.', 'igp-pro' ) );
		}

		$save = igp_pro_save_content_graph( $approved_id, $graph );
		if ( is_wp_error( $save ) ) {
			return $save;
		}

		if ( function_exists( 'igp_pro_sync_content_graph_to_post_content' ) ) {
			$sync = igp_pro_sync_content_graph_to_post_content( $approved_id, $graph );
			if ( is_wp_error( $sync ) ) {
				return $sync;
			}
		}

		$seo = isset( $record['seo'] ) && is_array( $record['seo'] ) ? $record['seo'] : array();
		if ( function_exists( 'igp_pro_save_meta_description' ) && ! empty( $seo['meta_description'] ) ) {
			igp_pro_save_meta_description( $approved_id, (string) $seo['meta_description'] );
		}

		$record['status']               = 'approved';
		$record['approved_at']          = gmdate( 'c' );
		$record['approved_by']          = function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0;
		$record['approved_post_id']     = $approved_id;
		$record['approval_snapshot_id'] = $snapshot_id;
		$record['updated_at']           = gmdate( 'c' );

		$persist = self::save( $record );
		if ( is_wp_error( $persist ) ) {
			return $persist;
		}

		self::log_event( 'ai_changeset_approved', 'success', $approved_id, '', 'AI Copilot changeset approved and saved as draft-safe content.', $record['changeset_id'] );

		return self::summarize( $record );
	}

	/**
	 * Reject a pending changeset without modifying content.
	 *
	 * @param string $changeset_id Changeset ID.
	 * @param string $reason       Optional reason.
	 * @return array|WP_Error
	 */
	public static function reject( string $changeset_id, string $reason = '' ): array|WP_Error {
		$record = self::get( $changeset_id );
		if ( is_wp_error( $record ) ) {
			return $record;
		}
		if ( 'pending' !== ( $record['status'] ?? '' ) ) {
			return new WP_Error( 'igp_ai_changeset_not_pending', __( 'Only pending AI changesets can be rejected.', 'igp-pro' ) );
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( 'igp_ai_changeset_forbidden', __( 'You do not have permission to reject AI changesets.', 'igp-pro' ) );
		}

		$record['status']      = 'rejected';
		$record['rejected_at'] = gmdate( 'c' );
		$record['rejected_by'] = function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0;
		$record['reject_reason'] = sanitize_textarea_field( $reason );
		$record['updated_at']  = gmdate( 'c' );

		$save = self::save( $record );
		if ( is_wp_error( $save ) ) {
			return $save;
		}

		self::log_event( 'ai_changeset_rejected', 'success', absint( $record['target_post_id'] ?? 0 ), '', 'AI Copilot changeset rejected without changing content.', $record['changeset_id'] );
		return self::summarize( $record );
	}

	/**
	 * Roll back an approved changeset.
	 *
	 * @param string $changeset_id Changeset ID.
	 * @param string $mode         safe|force|preview.
	 * @return array|WP_Error
	 */
	public static function rollback( string $changeset_id, string $mode = 'safe' ): array|WP_Error {
		$record = self::get( $changeset_id );
		if ( is_wp_error( $record ) ) {
			return $record;
		}
		if ( 'approved' !== ( $record['status'] ?? '' ) ) {
			return new WP_Error( 'igp_ai_changeset_not_approved', __( 'Only approved AI changesets can be rolled back.', 'igp-pro' ) );
		}

		$approved_post_id = absint( $record['approved_post_id'] ?? 0 );
		if ( $approved_post_id > 0 && ! current_user_can( 'edit_post', $approved_post_id ) ) {
			return new WP_Error( 'igp_ai_changeset_forbidden', __( 'You do not have permission to roll back this changeset.', 'igp-pro' ) );
		}

		$snapshot_id = sanitize_key( (string) ( $record['approval_snapshot_id'] ?? '' ) );
		$result      = array();

		if ( '' !== $snapshot_id && function_exists( 'igp_restore_snapshot' ) ) {
			$restore = igp_restore_snapshot( $snapshot_id, $mode );
			if ( is_wp_error( $restore ) ) {
				return $restore;
			}
			$result = $restore;
		} elseif ( $approved_post_id > 0 && 0 === absint( $record['target_post_id'] ?? 0 ) ) {
			$trashed = wp_trash_post( $approved_post_id );
			if ( ! $trashed ) {
				return new WP_Error( 'igp_ai_changeset_rollback_failed', __( 'New draft created from changeset could not be moved to trash.', 'igp-pro' ) );
			}
			$result = array(
				'object_type' => 'post',
				'object_id'   => $approved_post_id,
				'restored'    => true,
				'message'     => __( 'New draft created from the AI changeset was moved to trash.', 'igp-pro' ),
			);
		} else {
			return new WP_Error( 'igp_ai_changeset_rollback_unavailable', __( 'No rollback snapshot is available for this changeset.', 'igp-pro' ) );
		}

		if ( 'preview' !== sanitize_key( $mode ) ) {
			$record['status']          = 'rolled_back';
			$record['rolled_back_at']  = gmdate( 'c' );
			$record['rolled_back_by']  = function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0;
			$record['rollback_result'] = $result;
			$record['updated_at']      = gmdate( 'c' );
			$save = self::save( $record );
			if ( is_wp_error( $save ) ) {
				return $save;
			}
			self::log_event( 'ai_changeset_rolled_back', 'success', $approved_post_id, '', 'AI Copilot changeset rolled back.', $record['changeset_id'] );
		}

		$summary = self::summarize( $record );
		$summary['rollback_result'] = $result;
		return $summary;
	}

	/**
	 * Read a changeset by ID.
	 *
	 * @param string $changeset_id Changeset ID.
	 * @return array|WP_Error
	 */
	public static function get( string $changeset_id ): array|WP_Error {
		$changeset_id = sanitize_key( $changeset_id );
		$path         = self::path( $changeset_id );
		if ( '' === $changeset_id || ! file_exists( $path ) || ! is_readable( $path ) ) {
			return new WP_Error( 'igp_ai_changeset_not_found', __( 'AI changeset was not found.', 'igp-pro' ) );
		}
		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$decoded = is_string( $raw ) ? json_decode( $raw, true ) : null;
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'igp_ai_changeset_invalid', __( 'AI changeset is corrupt or unreadable.', 'igp-pro' ) );
		}
		return $decoded;
	}

	/**
	 * List recent changesets.
	 *
	 * @param array $args Args: status, limit.
	 * @return array<int,array<string,mixed>>
	 */
	public static function list( array $args = array() ): array {
		if ( ! self::ensure_storage() ) {
			return array();
		}
		$files = glob( IGP_AI_COPILOT_CHANGESET_DIR . '*.json' );
		if ( ! is_array( $files ) ) {
			return array();
		}
		$status = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$out    = array();
		foreach ( $files as $file ) {
			$raw = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$record = is_string( $raw ) ? json_decode( $raw, true ) : null;
			if ( ! is_array( $record ) ) {
				continue;
			}
			if ( '' !== $status && $status !== ( $record['status'] ?? '' ) ) {
				continue;
			}
			$out[] = self::summarize( $record );
		}
		usort(
			$out,
			static function ( array $a, array $b ): int {
				return strcmp( (string) ( $b['updated_at'] ?? '' ), (string) ( $a['updated_at'] ?? '' ) );
			}
		);
		$limit = isset( $args['limit'] ) ? absint( $args['limit'] ) : 50;
		return array_slice( $out, 0, max( 1, min( 200, $limit ) ) );
	}

	/**
	 * Save a changeset record.
	 *
	 * @param array $record Record.
	 * @return true|WP_Error
	 */
	public static function save( array $record ) {
		if ( ! self::ensure_storage() ) {
			return new WP_Error( 'igp_ai_changeset_storage_unavailable', __( 'AI changeset storage is unavailable.', 'igp-pro' ) );
		}
		$changeset_id = isset( $record['changeset_id'] ) ? sanitize_key( (string) $record['changeset_id'] ) : '';
		if ( '' === $changeset_id ) {
			return new WP_Error( 'igp_ai_changeset_missing_id', __( 'Changeset ID is missing.', 'igp-pro' ) );
		}
		$encoded = wp_json_encode( $record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( ! is_string( $encoded ) ) {
			return new WP_Error( 'igp_ai_changeset_encode_failed', __( 'Changeset could not be encoded.', 'igp-pro' ) );
		}
		return false === @file_put_contents( self::path( $changeset_id ), $encoded, LOCK_EX ) // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			? new WP_Error( 'igp_ai_changeset_write_failed', __( 'Changeset could not be written.', 'igp-pro' ) )
			: true;
	}

	/** Ensure storage directory exists and is protected. */
	public static function ensure_storage(): bool {
		if ( ! function_exists( 'wp_mkdir_p' ) ) {
			return false;
		}
		if ( ! wp_mkdir_p( IGP_AI_COPILOT_CHANGESET_DIR ) ) {
			return false;
		}
		$htaccess = IGP_AI_COPILOT_CHANGESET_DIR . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			@file_put_contents( $htaccess, "Deny from all\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		$index = IGP_AI_COPILOT_CHANGESET_DIR . 'index.php';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		return true;
	}

	/**
	 * Summarize a changeset for API/admin lists without hiding review data.
	 *
	 * @param array $record Record.
	 * @return array
	 */
	public static function summarize( array $record ): array {
		$compiled = isset( $record['compiled'] ) && is_array( $record['compiled'] ) ? $record['compiled'] : array();
		return array(
			'changeset_id'         => (string) ( $record['changeset_id'] ?? '' ),
			'status'               => (string) ( $record['status'] ?? '' ),
			'target_post_id'       => absint( $record['target_post_id'] ?? 0 ),
			'target_post_type'     => sanitize_key( (string) ( $record['target_post_type'] ?? '' ) ),
			'target_post_title'    => sanitize_text_field( (string) ( $record['target_post_title'] ?? '' ) ),
			'title'                => sanitize_text_field( (string) ( $compiled['title'] ?? '' ) ),
			'content_type'         => sanitize_key( (string) ( $compiled['content_type'] ?? '' ) ),
			'snapshot_id'          => sanitize_key( (string) ( $record['snapshot_id'] ?? '' ) ),
			'approval_snapshot_id' => sanitize_key( (string) ( $record['approval_snapshot_id'] ?? '' ) ),
			'approved_post_id'     => absint( $record['approved_post_id'] ?? 0 ),
			'actor_user_id'        => absint( $record['actor_user_id'] ?? 0 ),
			'actor_type'           => sanitize_key( (string) ( $record['actor_type'] ?? '' ) ),
			'source'               => sanitize_key( (string) ( $record['source'] ?? '' ) ),
			'created_at'           => sanitize_text_field( (string) ( $record['created_at'] ?? '' ) ),
			'updated_at'           => sanitize_text_field( (string) ( $record['updated_at'] ?? '' ) ),
			'approved_at'          => sanitize_text_field( (string) ( $record['approved_at'] ?? '' ) ),
			'rejected_at'          => sanitize_text_field( (string) ( $record['rejected_at'] ?? '' ) ),
			'rolled_back_at'       => sanitize_text_field( (string) ( $record['rolled_back_at'] ?? '' ) ),
			'mapping_report'       => isset( $record['mapping_report'] ) && is_array( $record['mapping_report'] ) ? $record['mapping_report'] : array(),
			'validation_result'    => isset( $record['validation_result'] ) && is_array( $record['validation_result'] ) ? $record['validation_result'] : array(),
			'media_requirements'   => isset( $record['media_requirements'] ) && is_array( $record['media_requirements'] ) ? $record['media_requirements'] : array(),
			'edit_link'            => absint( $record['approved_post_id'] ?? 0 ) ? get_edit_post_link( absint( $record['approved_post_id'] ), 'raw' ) : '',
		);
	}

	private static function generate_id(): string {
		return sanitize_key( 'ai_changeset_' . gmdate( 'Ymd_His' ) . '_' . wp_generate_password( 8, false, false ) );
	}

	private static function path( string $changeset_id ): string {
		return IGP_AI_COPILOT_CHANGESET_DIR . sanitize_key( $changeset_id ) . '.json';
	}

	private static function content_type_to_post_type( string $content_type ): string {
		$map = array( 'tour_page' => 'tour', 'destination_page' => 'destination', 'landing_page' => 'page', 'blog_support_page' => 'post', 'industry_template_page' => 'page' );
		return $map[ sanitize_key( $content_type ) ] ?? 'page';
	}

	private static function sanitize_context( array $context ): array {
		$out = array();
		foreach ( $context as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$out[ $key ] = self::sanitize_context( $value );
			} elseif ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
				$out[ $key ] = $value;
			} elseif ( is_scalar( $value ) ) {
				$out[ $key ] = 'notes' === $key ? sanitize_textarea_field( (string) $value ) : sanitize_text_field( (string) $value );
			}
		}
		return $out;
	}

	private static function actor_type( array $context ): string {
		$actor = isset( $context['actor_type'] ) ? sanitize_key( (string) $context['actor_type'] ) : '';
		return in_array( $actor, array( 'human', 'mcp', 'rest', 'system' ), true ) ? $actor : ( is_user_logged_in() ? 'human' : 'mcp' );
	}

	private static function limit_yaml_for_storage( string $yaml ): string {
		$yaml = (string) $yaml;
		return strlen( $yaml ) > 200000 ? substr( $yaml, 0, 200000 ) : $yaml;
	}

	private static function log_event( string $operation, string $status, int $object_id = 0, string $error_code = '', string $summary = '', string $changeset_id = '' ): void {
		if ( ! function_exists( 'igp_pro_log' ) ) {
			return;
		}
		igp_pro_log(
			array(
				'actor_type'    => is_user_logged_in() ? 'human' : 'mcp',
				'operation'     => sanitize_key( $operation ),
				'object_type'   => 'ai_changeset',
				'object_id'     => $object_id,
				'source_module' => 'ai_copilot_changeset',
				'source'        => 'ai_copilot',
				'status'        => 'success' === $status ? 'success' : 'failure',
				'error_code'    => sanitize_key( $error_code ),
				'summary'       => '' !== $summary ? sanitize_text_field( $summary ) : sanitize_text_field( $operation ),
				'changeset_id'  => sanitize_key( $changeset_id ),
			)
		);
	}
}
