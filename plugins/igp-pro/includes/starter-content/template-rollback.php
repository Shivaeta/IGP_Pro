<?php
/**
 * Starter template rollback service for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Roll back the most recent starter template import batch.
 *
 * @param string $mode safe|force.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_rollback_last_starter_template_import( string $mode = 'safe' ) {
	$batch = function_exists( 'igp_pro_get_last_starter_template_import_batch' ) ? igp_pro_get_last_starter_template_import_batch() : null;
	if ( ! is_array( $batch ) || empty( $batch['batch_id'] ) ) {
		return new WP_Error( 'igp_pro_template_no_import_batch', __( 'No starter template import batch is available for rollback.', 'igp-pro' ) );
	}

	return igp_pro_rollback_starter_template_import( (string) $batch['batch_id'], $mode );
}

/**
 * Roll back a starter template import batch.
 *
 * @param string $batch_id Batch ID.
 * @param string $mode     safe|force.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_rollback_starter_template_import( string $batch_id, string $mode = 'safe' ) {
	$batch_id = sanitize_key( $batch_id );
	$mode     = in_array( sanitize_key( $mode ), array( 'safe', 'force' ), true ) ? sanitize_key( $mode ) : 'safe';
	$batch    = function_exists( 'igp_pro_get_starter_template_import_batch' ) ? igp_pro_get_starter_template_import_batch( $batch_id ) : null;

	if ( ! is_array( $batch ) ) {
		return new WP_Error( 'igp_pro_template_batch_not_found', __( 'Starter template import batch was not found.', 'igp-pro' ) );
	}

	$status = sanitize_key( (string) ( $batch['status'] ?? '' ) );
	if ( in_array( $status, array( 'rolled_back', 'rollback_partial' ), true ) ) {
		return new WP_Error( 'igp_pro_template_batch_already_rolled_back', __( 'This starter template batch was already rolled back.', 'igp-pro' ) );
	}

	$result = array(
		'batch_id'          => $batch_id,
		'mode'              => $mode,
		'success'           => true,
		'restored'          => array(),
		'trashed'           => array(),
		'skipped'           => array(),
		'conflicts'         => array(),
		'errors'            => array(),
	);

	$objects = isset( $batch['objects'] ) && is_array( $batch['objects'] ) ? array_reverse( $batch['objects'] ) : array();
	foreach ( $objects as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$action = sanitize_key( (string) ( $entry['action'] ?? '' ) );
		$post_id = absint( $entry['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			continue;
		}

		if ( 'relationships_updated' === $action || 'skipped_existing' === $action ) {
			$result['skipped'][] = array(
				'post_id' => $post_id,
				'action'  => $action,
				'reason'  => 'non_destructive_or_skipped_entry',
			);
			continue;
		}

		if ( 'created' === $action ) {
			$created_result = igp_pro_rollback_created_starter_template_object( $entry, $batch_id, $mode );
			if ( is_wp_error( $created_result ) ) {
				$data = $created_result->get_error_data();
				if ( is_array( $data ) && ! empty( $data['conflict_detected'] ) ) {
					$result['conflicts'][] = $data;
				} else {
					$result['errors'][] = $created_result->get_error_message();
				}
				$result['success'] = false;
				continue;
			}
			$result['trashed'][] = $post_id;
			continue;
		}

		if ( 'modified' === $action ) {
			$modified_result = igp_pro_rollback_modified_starter_template_object( $entry, $mode );
			if ( is_wp_error( $modified_result ) ) {
				$data = $modified_result->get_error_data();
				if ( is_array( $data ) && ! empty( $data['conflict_detected'] ) ) {
					$result['conflicts'][] = $data;
				} else {
					$result['errors'][] = $modified_result->get_error_message();
				}
				$result['success'] = false;
				continue;
			}
			$result['restored'][] = $post_id;
			continue;
		}
	}

	$batch['status']       = $result['success'] ? 'rolled_back' : 'rollback_partial';
	$batch['rolled_back_at'] = gmdate( 'c' );
	$batch['rollback_result'] = $result;
	if ( function_exists( 'igp_pro_store_starter_template_import_batch' ) ) {
		igp_pro_store_starter_template_import_batch( $batch );
	}

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => 'human',
				'operation'     => 'starter_template_rollback',
				'object_type'   => 'starter_template_batch',
				'object_id'     => 0,
				'source_module' => 'starter-template-rollback',
				'status'        => $result['success'] ? 'success' : 'warning',
				'summary'       => sprintf( 'Starter template batch %s rollback completed.', $batch_id ),
				'context'       => array(
					'trashed'   => count( $result['trashed'] ),
					'restored'  => count( $result['restored'] ),
					'conflicts' => count( $result['conflicts'] ),
					'errors'    => count( $result['errors'] ),
				),
			)
		);
	}

	return $result;
}

/**
 * Roll back a created object by trashing it where safe.
 *
 * @param array  $entry Entry.
 * @param string $batch_id Batch ID.
 * @param string $mode Mode.
 * @return true|WP_Error
 */
function igp_pro_rollback_created_starter_template_object( array $entry, string $batch_id, string $mode ) {
	$post_id = absint( $entry['post_id'] ?? 0 );
	if ( $post_id <= 0 ) {
		return true;
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return true;
	}

	$stored_batch = sanitize_key( (string) get_post_meta( $post_id, IGP_PRO_TEMPLATE_META_BATCH, true ) );
	$stored_uuid  = sanitize_text_field( (string) get_post_meta( $post_id, IGP_PRO_TEMPLATE_META_UUID, true ) );
	$entry_uuid   = sanitize_text_field( (string) ( $entry['template_uuid'] ?? '' ) );

	if ( $stored_batch !== sanitize_key( $batch_id ) || $stored_uuid !== $entry_uuid ) {
		return new WP_Error( 'igp_pro_template_rollback_ownership_mismatch', __( 'Rollback skipped a created object because template ownership metadata no longer matches.', 'igp-pro' ) );
	}

	$current_hash  = function_exists( 'igp_pro_hash_starter_template_object_state' ) ? igp_pro_hash_starter_template_object_state( $post_id ) : '';
	$expected_hash = (string) ( $entry['post_hash_at_import'] ?? '' );
	if ( 'force' !== $mode && '' !== $expected_hash && '' !== $current_hash && $current_hash !== $expected_hash ) {
		$error = new WP_Error( 'igp_pro_template_rollback_created_conflict', __( 'Created template object changed after import. Rollback skipped it in safe mode.', 'igp-pro' ) );
		$error->add_data(
			array(
				'conflict_detected' => true,
				'post_id'           => $post_id,
				'action'            => 'created',
				'template_uuid'     => $entry_uuid,
			)
		);
		return $error;
	}

	$trashed = wp_trash_post( $post_id );
	if ( ! $trashed ) {
		return new WP_Error( 'igp_pro_template_rollback_trash_failed', __( 'Created template object could not be moved to Trash.', 'igp-pro' ) );
	}

	return true;
}

/**
 * Roll back a modified object via its pre-merge snapshot.
 *
 * @param array  $entry Entry.
 * @param string $mode Mode.
 * @return true|WP_Error
 */
function igp_pro_rollback_modified_starter_template_object( array $entry, string $mode ) {
	$snapshot_id = sanitize_key( (string) ( $entry['snapshot_id'] ?? '' ) );
	$post_id     = absint( $entry['post_id'] ?? 0 );
	if ( '' === $snapshot_id ) {
		return new WP_Error( 'igp_pro_template_rollback_missing_snapshot', __( 'Modified template object has no pre-merge snapshot.', 'igp-pro' ) );
	}

	if ( ! function_exists( 'igp_get_snapshot' ) ) {
		return new WP_Error( 'igp_pro_template_snapshot_service_missing', __( 'Snapshot service is unavailable.', 'igp-pro' ) );
	}

	$snapshot = igp_get_snapshot( $snapshot_id );
	if ( is_wp_error( $snapshot ) ) {
		return $snapshot;
	}

	$current_hash  = function_exists( 'igp_pro_hash_starter_template_object_state' ) && $post_id > 0 ? igp_pro_hash_starter_template_object_state( $post_id ) : '';
	$expected_hash = (string) ( $entry['post_hash_at_import'] ?? '' );
	if ( 'force' !== $mode && '' !== $expected_hash && '' !== $current_hash && $current_hash !== $expected_hash ) {
		$error = new WP_Error( 'igp_pro_template_rollback_modified_conflict', __( 'Modified template object changed after import. Rollback skipped it in safe mode.', 'igp-pro' ) );
		$error->add_data(
			array(
				'conflict_detected' => true,
				'post_id'           => $post_id,
				'action'            => 'modified',
				'snapshot_id'       => $snapshot_id,
				'template_uuid'     => sanitize_text_field( (string) ( $entry['template_uuid'] ?? '' ) ),
			)
		);
		return $error;
	}

	$before_data = isset( $snapshot['before_data'] ) && is_array( $snapshot['before_data'] ) ? $snapshot['before_data'] : array();
	if ( empty( $before_data ) || ! function_exists( 'igp_pro_restore_starter_template_object_state' ) ) {
		return new WP_Error( 'igp_pro_template_rollback_invalid_snapshot', __( 'Template snapshot lacks restorable object state.', 'igp-pro' ) );
	}

	$restore = igp_pro_restore_starter_template_object_state( $post_id, $before_data );
	if ( is_wp_error( $restore ) ) {
		return $restore;
	}

	$snapshot['rollback_status'] = 'restored';
	$snapshot['restored_at']     = gmdate( 'c' );
	$snapshot['restored_by']     = function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0;
	if ( function_exists( 'igp_pro_save_snapshot_record' ) ) {
		igp_pro_save_snapshot_record( $snapshot );
	}

	return true;
}
