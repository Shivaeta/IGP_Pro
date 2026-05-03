<?php
/**
 * Snapshot storage for IGP Pro V2 recovery workflows.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_SNAPSHOT_DIR' ) ) {
	define( 'IGP_PRO_SNAPSHOT_DIR', IGP_PRO_PATH . 'storage/snapshots/' );
}

/**
 * Ensure snapshot storage exists and is guarded from directory browsing.
 *
 * @return bool
 */
function igp_pro_ensure_snapshot_storage(): bool {
	if ( ! function_exists( 'wp_mkdir_p' ) ) {
		return false;
	}

	if ( ! wp_mkdir_p( IGP_PRO_SNAPSHOT_DIR ) ) {
		return false;
	}

	$htaccess = IGP_PRO_SNAPSHOT_DIR . '.htaccess';
	if ( ! file_exists( $htaccess ) ) {
		@file_put_contents( $htaccess, "Deny from all\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	$index = IGP_PRO_SNAPSHOT_DIR . 'index.php';
	if ( ! file_exists( $index ) ) {
		@file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	return true;
}

/**
 * Normalize a snapshot object type.
 *
 * @param string $object_type Raw object type.
 * @return string
 */
function igp_pro_sanitize_snapshot_object_type( string $object_type ): string {
	$object_type = sanitize_key( $object_type );
	$allowed     = array( 'content_graph', 'seo_fields', 'relationship_data', 'template_import', 'mcp_edit', 'settings', 'generic' );

	return in_array( $object_type, $allowed, true ) ? $object_type : 'generic';
}

/**
 * Generate a stable hash for snapshot data.
 *
 * @param mixed $data Data to hash.
 * @return string
 */
function igp_pro_snapshot_data_hash( $data ): string {
	$encoded = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	return is_string( $encoded ) ? hash( 'sha256', $encoded ) : '';
}

/**
 * Create a snapshot before a data-changing operation.
 *
 * @param string              $object_type Object type.
 * @param int                 $object_id   Object ID.
 * @param mixed               $before_data Before-state data.
 * @param array<string,mixed> $context     Context data.
 * @return string|WP_Error Snapshot ID or error.
 */
function igp_create_snapshot( $object_type, $object_id, $before_data, $context = array() ) {
	$object_type = igp_pro_sanitize_snapshot_object_type( (string) $object_type );
	$object_id   = absint( $object_id );
	$context     = is_array( $context ) ? $context : array();

	if ( $object_id <= 0 && ! in_array( $object_type, array( 'settings', 'template_import', 'generic' ), true ) ) {
		return new WP_Error( 'igp_pro_snapshot_invalid_object_id', __( 'Snapshot object ID is invalid.', 'igp-pro' ) );
	}

	if ( ! igp_pro_ensure_snapshot_storage() ) {
		return new WP_Error( 'igp_pro_snapshot_storage_unavailable', __( 'Snapshot storage is unavailable.', 'igp-pro' ) );
	}

	$snapshot_id = 'snap_' . gmdate( 'Ymd_His' ) . '_' . wp_generate_password( 8, false, false );
	$snapshot_id = sanitize_key( $snapshot_id );

	$current_data = igp_pro_get_current_snapshot_object_data( $object_type, $object_id );
	if ( is_wp_error( $current_data ) ) {
		$current_data = null;
	}

	$snapshot = array(
		'snapshot_id'            => $snapshot_id,
		'object_type'            => $object_type,
		'object_id'              => $object_id,
		'before_data'            => $before_data,
		'after_data'             => isset( $context['after_data'] ) ? $context['after_data'] : null,
		'actor_user_id'          => function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0,
		'source_module'          => isset( $context['source_module'] ) ? sanitize_key( (string) $context['source_module'] ) : 'unknown',
		'created_at'             => gmdate( 'c' ),
		'rollback_status'        => 'not_restored',
		'before_hash'            => igp_pro_snapshot_data_hash( $before_data ),
		'after_hash'             => isset( $context['after_data'] ) ? igp_pro_snapshot_data_hash( $context['after_data'] ) : '',
		'current_hash_at_create' => igp_pro_snapshot_data_hash( null !== $current_data ? $current_data : $before_data ),
		'context'                => igp_pro_sanitize_snapshot_context( $context ),
	);

	$path    = igp_pro_snapshot_path( $snapshot_id );
	$encoded = wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	if ( ! is_string( $encoded ) ) {
		return new WP_Error( 'igp_pro_snapshot_encode_failed', __( 'Snapshot could not be encoded.', 'igp-pro' ) );
	}

	if ( false === @file_put_contents( $path, $encoded, LOCK_EX ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return new WP_Error( 'igp_pro_snapshot_write_failed', __( 'Snapshot could not be written.', 'igp-pro' ) );
	}

	if ( function_exists( 'igp_pro_log' ) ) {
		igp_pro_log(
			array(
				'actor_type'    => isset( $context['actor_type'] ) ? sanitize_key( (string) $context['actor_type'] ) : 'human',
				'operation'     => 'snapshot_created',
				'object_type'   => $object_type,
				'object_id'     => $object_id,
				'source_module' => $snapshot['source_module'],
				'status'        => 'success',
				'summary'       => sprintf( 'Snapshot %s created.', $snapshot_id ),
				'snapshot_id'   => $snapshot_id,
			)
		);
	}

	return $snapshot_id;
}

/**
 * Project-prefixed snapshot creation alias.
 *
 * @param string              $object_type Object type.
 * @param int                 $object_id   Object ID.
 * @param mixed               $before_data Before-state data.
 * @param array<string,mixed> $context     Context data.
 * @return string|WP_Error
 */
function igp_pro_create_snapshot( $object_type, $object_id, $before_data, $context = array() ) {
	return igp_create_snapshot( $object_type, $object_id, $before_data, $context );
}

/**
 * Sanitize snapshot context before storage.
 *
 * @param array<string,mixed> $context Raw context.
 * @return array<string,mixed>
 */
function igp_pro_sanitize_snapshot_context( array $context ): array {
	$allowed = array( 'source_module', 'actor_type', 'reason', 'operation', 'after_data' );
	$output  = array();

	foreach ( $context as $key => $value ) {
		$key = sanitize_key( (string) $key );
		if ( ! in_array( $key, $allowed, true ) || 'after_data' === $key ) {
			continue;
		}
		$output[ $key ] = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '[structured]';
	}

	return $output;
}

/**
 * Return a snapshot file path.
 *
 * @param string $snapshot_id Snapshot ID.
 * @return string
 */
function igp_pro_snapshot_path( string $snapshot_id ): string {
	return IGP_PRO_SNAPSHOT_DIR . sanitize_key( $snapshot_id ) . '.json';
}

/**
 * Fetch a snapshot by ID.
 *
 * @param string $snapshot_id Snapshot ID.
 * @return array<string,mixed>|WP_Error
 */
function igp_get_snapshot( $snapshot_id ) {
	$snapshot_id = sanitize_key( (string) $snapshot_id );
	$path        = igp_pro_snapshot_path( $snapshot_id );

	if ( '' === $snapshot_id || ! file_exists( $path ) || ! is_readable( $path ) ) {
		return new WP_Error( 'igp_pro_snapshot_not_found', __( 'Snapshot was not found.', 'igp-pro' ) );
	}

	$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$decoded  = is_string( $contents ) ? json_decode( $contents, true ) : null;

	if ( ! is_array( $decoded ) ) {
		return new WP_Error( 'igp_pro_snapshot_invalid', __( 'Snapshot is corrupt or unreadable.', 'igp-pro' ) );
	}

	return $decoded;
}

/**
 * Project-prefixed snapshot fetch alias.
 *
 * @param string $snapshot_id Snapshot ID.
 * @return array<string,mixed>|WP_Error
 */
function igp_pro_get_snapshot( $snapshot_id ) {
	return igp_get_snapshot( $snapshot_id );
}

/**
 * List snapshots.
 *
 * @param array<string,mixed> $args Filter args.
 * @return array<int,array<string,mixed>>
 */
function igp_list_snapshots( $args = array() ): array {
	$args = is_array( $args ) ? $args : array();
	if ( ! igp_pro_ensure_snapshot_storage() ) {
		return array();
	}

	$files = glob( IGP_PRO_SNAPSHOT_DIR . '*.json' );
	if ( ! is_array( $files ) ) {
		return array();
	}

	$snapshots = array();
	foreach ( $files as $file ) {
		$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$decoded  = is_string( $contents ) ? json_decode( $contents, true ) : null;
		if ( ! is_array( $decoded ) ) {
			continue;
		}

		if ( isset( $args['object_type'] ) && sanitize_key( (string) $args['object_type'] ) !== ( $decoded['object_type'] ?? '' ) ) {
			continue;
		}
		if ( isset( $args['object_id'] ) && absint( $args['object_id'] ) !== absint( $decoded['object_id'] ?? 0 ) ) {
			continue;
		}

		$snapshots[] = $decoded;
	}

	usort(
		$snapshots,
		static function ( array $a, array $b ): int {
			return strcmp( (string) ( $b['created_at'] ?? '' ), (string) ( $a['created_at'] ?? '' ) );
		}
	);

	$limit = isset( $args['limit'] ) ? absint( $args['limit'] ) : 100;
	$limit = max( 1, min( 500, $limit ) );

	return array_slice( $snapshots, 0, $limit );
}

/**
 * Project-prefixed snapshot list alias.
 *
 * @param array<string,mixed> $args Filter args.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_list_snapshots( $args = array() ): array {
	return igp_list_snapshots( $args );
}

/**
 * Persist a modified snapshot record.
 *
 * @param array<string,mixed> $snapshot Snapshot.
 * @return true|WP_Error
 */
function igp_pro_save_snapshot_record( array $snapshot ) {
	$snapshot_id = isset( $snapshot['snapshot_id'] ) ? sanitize_key( (string) $snapshot['snapshot_id'] ) : '';
	if ( '' === $snapshot_id ) {
		return new WP_Error( 'igp_pro_snapshot_missing_id', __( 'Snapshot ID is missing.', 'igp-pro' ) );
	}

	$encoded = wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( ! is_string( $encoded ) ) {
		return new WP_Error( 'igp_pro_snapshot_encode_failed', __( 'Snapshot could not be encoded.', 'igp-pro' ) );
	}

	return false === @file_put_contents( igp_pro_snapshot_path( $snapshot_id ), $encoded, LOCK_EX ) // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		? new WP_Error( 'igp_pro_snapshot_write_failed', __( 'Snapshot could not be written.', 'igp-pro' ) )
		: true;
}

/**
 * Add after-state data to a snapshot after a controlled write.
 *
 * @param string $snapshot_id Snapshot ID.
 * @param mixed  $after_data  After-state data.
 * @return true|WP_Error
 */
function igp_pro_update_snapshot_after_data( string $snapshot_id, $after_data ) {
	$snapshot = igp_get_snapshot( $snapshot_id );
	if ( is_wp_error( $snapshot ) ) {
		return $snapshot;
	}

	$snapshot['after_data'] = $after_data;
	$snapshot['after_hash'] = igp_pro_snapshot_data_hash( $after_data );

	return igp_pro_save_snapshot_record( $snapshot );
}

/**
 * Get current data for an object type.
 *
 * @param string $object_type Object type.
 * @param int    $object_id   Object ID.
 * @return mixed|WP_Error
 */
function igp_pro_get_current_snapshot_object_data( string $object_type, int $object_id ) {
	$object_type = igp_pro_sanitize_snapshot_object_type( $object_type );

	if ( 'content_graph' === $object_type ) {
		return function_exists( 'igp_pro_load_content_graph' ) ? igp_pro_load_content_graph( $object_id ) : get_post_meta( $object_id, IGP_PRO_CONTENT_GRAPH_META_KEY, true );
	}

	if ( 'seo_fields' === $object_type ) {
		return array(
			'meta_description' => function_exists( 'igp_pro_load_meta_description' ) ? igp_pro_load_meta_description( $object_id ) : get_post_meta( $object_id, '_igp_pro_meta_description', true ),
		);
	}

	if ( 'relationship_data' === $object_type ) {
		return function_exists( 'igp_pro_get_relationships' ) ? igp_pro_get_relationships( $object_id, true ) : get_post_meta( $object_id );
	}

	if ( 'attachment_media_seo' === $object_type ) {
		return array(
			'_wp_attachment_image_alt' => (string) get_post_meta( $object_id, '_wp_attachment_image_alt', true ),
		);
	}

	if ( 'settings' === $object_type ) {
		return array(
			'feature_flags'     => function_exists( 'igp_get_feature_flags' ) ? igp_get_feature_flags() : array(),
			'role_capabilities' => function_exists( 'igp_pro_get_role_capability_grants' ) ? igp_pro_get_role_capability_grants() : array(),
		);
	}

	return get_post_meta( $object_id );
}
