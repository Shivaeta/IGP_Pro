<?php
/**
 * Rollback service for IGP Pro V2 recovery workflows.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Restore a snapshot.
 *
 * Modes:
 * - preview: do not write; return conflict metadata.
 * - safe: restore only if there is no newer-change conflict when after_hash exists.
 * - force: restore even when current content differs from expected after-state.
 *
 * @param string $snapshot_id Snapshot ID.
 * @param string $mode        Restore mode.
 * @return array<string,mixed>|WP_Error
 */
function igp_restore_snapshot( $snapshot_id, $mode = 'safe' ) {
	$mode = sanitize_key( (string) $mode );
	if ( ! in_array( $mode, array( 'preview', 'safe', 'force' ), true ) ) {
		$mode = 'safe';
	}

	if ( ! function_exists( 'igp_get_snapshot' ) ) {
		return new WP_Error( 'igp_pro_snapshot_service_missing', __( 'Snapshot service is unavailable.', 'igp-pro' ) );
	}

	$snapshot = igp_get_snapshot( $snapshot_id );
	if ( is_wp_error( $snapshot ) ) {
		return $snapshot;
	}

	$object_type  = isset( $snapshot['object_type'] ) ? sanitize_key( (string) $snapshot['object_type'] ) : 'generic';
	$object_id    = isset( $snapshot['object_id'] ) ? absint( $snapshot['object_id'] ) : 0;
	$before_data  = $snapshot['before_data'] ?? null;
	$current_data = function_exists( 'igp_pro_get_current_snapshot_object_data' ) ? igp_pro_get_current_snapshot_object_data( $object_type, $object_id ) : null;

	if ( is_wp_error( $current_data ) ) {
		return $current_data;
	}

	$current_hash = function_exists( 'igp_pro_snapshot_data_hash' ) ? igp_pro_snapshot_data_hash( $current_data ) : '';
	$after_hash   = isset( $snapshot['after_hash'] ) ? (string) $snapshot['after_hash'] : '';
	$created_hash = isset( $snapshot['current_hash_at_create'] ) ? (string) $snapshot['current_hash_at_create'] : '';

	$conflict = false;
	$expected_hash = '' !== $after_hash ? $after_hash : $created_hash;
	if ( '' !== $expected_hash && '' !== $current_hash && $current_hash !== $expected_hash ) {
		$conflict = true;
	}

	$result = array(
		'snapshot_id'       => isset( $snapshot['snapshot_id'] ) ? (string) $snapshot['snapshot_id'] : sanitize_key( (string) $snapshot_id ),
		'object_type'       => $object_type,
		'object_id'         => $object_id,
		'mode'              => $mode,
		'conflict_detected' => $conflict,
		'restored'          => false,
		'message'           => $conflict ? __( 'Current content differs from the snapshot expected state.', 'igp-pro' ) : __( 'Snapshot can be restored.', 'igp-pro' ),
	);

	if ( 'preview' === $mode ) {
		return $result;
	}

	if ( $conflict && 'force' !== $mode ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log(
				array(
					'actor_type'    => 'human',
					'operation'     => 'snapshot_restore_conflict',
					'object_type'   => $object_type,
					'object_id'     => $object_id,
					'source_module' => 'rollback',
					'status'        => 'warning',
					'error_code'    => 'igp_pro_snapshot_conflict',
					'summary'       => 'Snapshot restore blocked because current content changed after the expected state.',
					'snapshot_id'   => $result['snapshot_id'],
				)
			);
		}

		$error = new WP_Error( 'igp_pro_snapshot_conflict', __( 'Current content changed after this snapshot was created. Use force mode only after reviewing the conflict.', 'igp-pro' ) );
		$error->add_data( $result );
		return $error;
	}

	$restore = igp_pro_restore_snapshot_data( $object_type, $object_id, $before_data );
	if ( is_wp_error( $restore ) ) {
		if ( function_exists( 'igp_pro_log_wp_error' ) ) {
			igp_pro_log_wp_error( $restore, 'snapshot_restore_failed', 'rollback', $object_type, $object_id );
		}
		return $restore;
	}

	$snapshot['rollback_status'] = 'restored';
	$snapshot['restored_at']     = gmdate( 'c' );
	$snapshot['restored_by']     = function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0;

	if ( function_exists( 'igp_pro_save_snapshot_record' ) ) {
		igp_pro_save_snapshot_record( $snapshot );
	}

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => 'human',
				'operation'     => 'snapshot_restored',
				'object_type'   => $object_type,
				'object_id'     => $object_id,
				'source_module' => 'rollback',
				'status'        => 'success',
				'summary'       => sprintf( 'Snapshot %s restored.', $result['snapshot_id'] ),
				'snapshot_id'   => $result['snapshot_id'],
			)
		);
	}

	$result['restored'] = true;
	$result['message']  = __( 'Snapshot restored.', 'igp-pro' );

	return $result;
}

/**
 * Project-prefixed restore alias.
 *
 * @param string $snapshot_id Snapshot ID.
 * @param string $mode        Restore mode.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_restore_snapshot( $snapshot_id, $mode = 'safe' ) {
	return igp_restore_snapshot( $snapshot_id, $mode );
}

/**
 * Restore snapshot data through object-specific adapters.
 *
 * @param string $object_type Object type.
 * @param int    $object_id   Object ID.
 * @param mixed  $before_data Before data.
 * @return true|WP_Error
 */
function igp_pro_restore_snapshot_data( string $object_type, int $object_id, $before_data ) {
	$object_type = function_exists( 'igp_pro_sanitize_snapshot_object_type' ) ? igp_pro_sanitize_snapshot_object_type( $object_type ) : sanitize_key( $object_type );

	if ( 'content_graph' === $object_type ) {
		$graph = is_array( $before_data ) && isset( $before_data['graph'] ) && is_array( $before_data['graph'] ) ? $before_data['graph'] : $before_data;

		if ( ! is_array( $graph ) ) {
			return new WP_Error( 'igp_pro_snapshot_invalid_graph', __( 'Snapshot does not contain a restorable Content Graph.', 'igp-pro' ) );
		}

		if ( ! function_exists( 'igp_pro_save_content_graph' ) ) {
			return new WP_Error( 'igp_pro_content_graph_service_missing', __( 'Content Graph service is unavailable.', 'igp-pro' ) );
		}

		$save = igp_pro_save_content_graph( $object_id, $graph );
		if ( is_wp_error( $save ) ) {
			return $save;
		}

		if ( function_exists( 'igp_pro_sync_content_graph_to_post_content' ) ) {
			$sync = igp_pro_sync_content_graph_to_post_content( $object_id, $graph );
			if ( is_wp_error( $sync ) ) {
				return $sync;
			}
		}

		return true;
	}

	if ( 'seo_fields' === $object_type ) {
		if ( is_array( $before_data ) && array_key_exists( 'meta_description', $before_data ) && function_exists( 'igp_pro_save_meta_description' ) ) {
			return igp_pro_save_meta_description( $object_id, (string) $before_data['meta_description'] );
		}
		return new WP_Error( 'igp_pro_snapshot_invalid_seo_data', __( 'Snapshot does not contain restorable SEO fields.', 'igp-pro' ) );
	}

	if ( 'relationship_data' === $object_type ) {
		if ( ! is_array( $before_data ) || ! function_exists( 'igp_pro_save_relationships' ) ) {
			return new WP_Error( 'igp_pro_snapshot_invalid_relationship_data', __( 'Snapshot does not contain restorable relationship data.', 'igp-pro' ) );
		}

		return igp_pro_save_relationships(
			$object_id,
			$before_data,
			array(
				'actor_type'    => 'human',
				'source_module' => 'rollback',
				'reason'        => 'relationship_snapshot_restore',
			)
		);
	}

	if ( 'settings' === $object_type ) {
		if ( ! is_array( $before_data ) ) {
			return new WP_Error( 'igp_pro_snapshot_invalid_settings_data', __( 'Snapshot does not contain restorable settings.', 'igp-pro' ) );
		}

		if ( isset( $before_data['feature_flags'] ) && function_exists( 'igp_pro_update_feature_flags' ) ) {
			igp_pro_update_feature_flags( $before_data['feature_flags'] );
		}

		if ( isset( $before_data['role_capabilities'] ) && function_exists( 'igp_pro_update_role_capability_grants' ) ) {
			igp_pro_update_role_capability_grants( $before_data['role_capabilities'] );
		}

		return true;
	}

	if ( 'starter_template_object' === $object_type ) {
		if ( ! is_array( $before_data ) || ! function_exists( 'igp_pro_restore_starter_template_object_state' ) ) {
			return new WP_Error( 'igp_pro_snapshot_invalid_template_object', __( 'Snapshot does not contain restorable starter template object data.', 'igp-pro' ) );
		}

		return igp_pro_restore_starter_template_object_state( $object_id, $before_data );
	}

	return new WP_Error( 'igp_pro_snapshot_restore_unsupported', __( 'This snapshot object type does not yet have a restore adapter.', 'igp-pro' ) );
}
