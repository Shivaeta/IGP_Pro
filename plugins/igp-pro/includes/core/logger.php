<?php
/**
 * Structured logger for IGP Pro V2 critical operations.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_LOG_DIR' ) ) {
	define( 'IGP_PRO_LOG_DIR', IGP_PRO_PATH . 'storage/logs/' );
}

if ( ! defined( 'IGP_PRO_LOG_FILE' ) ) {
	define( 'IGP_PRO_LOG_FILE', IGP_PRO_LOG_DIR . 'igp-pro.log.jsonl' );
}

/**
 * Ensure the log directory exists and contains basic web-server guards.
 *
 * @return bool
 */
function igp_pro_ensure_log_storage(): bool {
	if ( ! function_exists( 'wp_mkdir_p' ) ) {
		return false;
	}

	if ( ! wp_mkdir_p( IGP_PRO_LOG_DIR ) ) {
		return false;
	}

	$htaccess = IGP_PRO_LOG_DIR . '.htaccess';
	if ( ! file_exists( $htaccess ) ) {
		@file_put_contents( $htaccess, "Deny from all\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	$index = IGP_PRO_LOG_DIR . 'index.php';
	if ( ! file_exists( $index ) ) {
		@file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	return true;
}

/**
 * Return keys that should never be logged raw.
 *
 * @return string[]
 */
function igp_pro_get_sensitive_log_key_patterns(): array {
	return array(
		'password',
		'passwd',
		'secret',
		'token',
		'api_key',
		'apikey',
		'authorization',
		'auth',
		'credential',
		'client_secret',
		'razorpay',
		'stripe',
		'paypal',
		'card',
		'cvv',
	);
}

/**
 * Determine whether a log key is sensitive.
 *
 * @param string $key Key.
 * @return bool
 */
function igp_pro_is_sensitive_log_key( string $key ): bool {
	$key = strtolower( $key );

	foreach ( igp_pro_get_sensitive_log_key_patterns() as $pattern ) {
		if ( false !== strpos( $key, $pattern ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Redact sensitive data recursively before logging.
 *
 * @param mixed $value Raw value.
 * @param string $key  Parent key.
 * @return mixed
 */
function igp_pro_redact_log_value( $value, string $key = '' ) {
	if ( '' !== $key && igp_pro_is_sensitive_log_key( $key ) ) {
		return '[redacted]';
	}

	if ( is_array( $value ) ) {
		$redacted = array();
		foreach ( $value as $child_key => $child_value ) {
			$redacted[ $child_key ] = igp_pro_redact_log_value( $child_value, (string) $child_key );
		}
		return $redacted;
	}

	if ( is_object( $value ) ) {
		return '[object]';
	}

	if ( is_string( $value ) ) {
		return sanitize_text_field( substr( $value, 0, 2000 ) );
	}

	if ( is_scalar( $value ) || null === $value ) {
		return $value;
	}

	return '[unloggable]';
}

/**
 * Normalize a structured log entry.
 *
 * @param array<string,mixed> $entry Raw entry.
 * @return array<string,mixed>
 */
function igp_pro_normalize_log_entry( array $entry ): array {
	$actor_type = isset( $entry['actor_type'] ) ? sanitize_key( (string) $entry['actor_type'] ) : 'system';
	$allowed_actor_types = array( 'human', 'import', 'rest', 'mcp', 'system', 'anonymous' );
	if ( ! in_array( $actor_type, $allowed_actor_types, true ) ) {
		$actor_type = 'system';
	}

	$status = isset( $entry['status'] ) ? sanitize_key( (string) $entry['status'] ) : 'info';
	if ( ! in_array( $status, array( 'success', 'failure', 'warning', 'info' ), true ) ) {
		$status = 'info';
	}

	$normalized = array(
		'timestamp'     => gmdate( 'c' ),
		'actor_user_id' => isset( $entry['actor_user_id'] ) ? absint( $entry['actor_user_id'] ) : ( function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0 ),
		'actor_type'    => $actor_type,
		'operation'     => isset( $entry['operation'] ) ? sanitize_key( (string) $entry['operation'] ) : 'unknown_operation',
		'object_type'   => isset( $entry['object_type'] ) ? sanitize_key( (string) $entry['object_type'] ) : 'unknown',
		'object_id'     => isset( $entry['object_id'] ) ? absint( $entry['object_id'] ) : 0,
		'source_module' => isset( $entry['source_module'] ) ? sanitize_key( (string) $entry['source_module'] ) : 'unknown',
		'status'        => $status,
		'error_code'    => isset( $entry['error_code'] ) ? sanitize_key( (string) $entry['error_code'] ) : '',
		'summary'       => isset( $entry['summary'] ) ? sanitize_text_field( substr( (string) $entry['summary'], 0, 500 ) ) : '',
		'snapshot_id'   => isset( $entry['snapshot_id'] ) ? sanitize_key( (string) $entry['snapshot_id'] ) : '',
	);

	if ( isset( $entry['context'] ) ) {
		$normalized['context'] = igp_pro_redact_log_value( $entry['context'], 'context' );
	}

	return $normalized;
}

/**
 * Write a structured log entry.
 *
 * @param array<string,mixed> $entry Raw structured log data.
 * @return bool|WP_Error
 */
function igp_pro_log( array $entry ) {
	$entry = igp_pro_normalize_log_entry( $entry );

	if ( ! igp_pro_ensure_log_storage() ) {
		return new WP_Error( 'igp_pro_log_storage_unavailable', __( 'IGP Pro log storage is unavailable.', 'igp-pro' ) );
	}

	$encoded = wp_json_encode( $entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( ! is_string( $encoded ) ) {
		return new WP_Error( 'igp_pro_log_encode_failed', __( 'IGP Pro log entry could not be encoded.', 'igp-pro' ) );
	}

	$result = @file_put_contents( IGP_PRO_LOG_FILE, $encoded . PHP_EOL, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

	if ( false === $result ) {
		return new WP_Error( 'igp_pro_log_write_failed', __( 'IGP Pro log entry could not be written.', 'igp-pro' ) );
	}

	return true;
}

/**
 * Alias for structured logging.
 *
 * @param array<string,mixed> $entry Raw structured log data.
 * @return bool|WP_Error
 */
function igp_log( array $entry ) {
	return igp_pro_log( $entry );
}

/**
 * Read recent structured log entries.
 *
 * @param int $limit Maximum entries.
 * @return array<int,array<string,mixed>>
 */
function igp_pro_get_recent_logs( int $limit = 50 ): array {
	$limit = max( 1, min( 200, $limit ) );

	if ( ! file_exists( IGP_PRO_LOG_FILE ) || ! is_readable( IGP_PRO_LOG_FILE ) ) {
		return array();
	}

	$lines = file( IGP_PRO_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file
	if ( ! is_array( $lines ) ) {
		return array();
	}

	$lines = array_slice( $lines, -1 * $limit );
	$logs  = array();

	foreach ( array_reverse( $lines ) as $line ) {
		$decoded = json_decode( $line, true );
		if ( is_array( $decoded ) ) {
			$logs[] = $decoded;
		}
	}

	return $logs;
}

/**
 * Log a WP_Error in a consistent structure.
 *
 * @param WP_Error $error         Error object.
 * @param string   $operation     Operation key.
 * @param string   $source_module Source module.
 * @param string   $object_type   Object type.
 * @param int      $object_id     Object ID.
 * @return bool|WP_Error
 */
function igp_pro_log_wp_error( WP_Error $error, string $operation, string $source_module, string $object_type = 'unknown', int $object_id = 0 ) {
	return igp_pro_log(
		array(
			'actor_type'    => 'system',
			'operation'     => $operation,
			'object_type'   => $object_type,
			'object_id'     => $object_id,
			'source_module' => $source_module,
			'satus'         => 'failure',
			'status'        => 'failure',
			'error_code'    => $error->get_error_code(),
			'summary'       => $error->get_error_message(),
		)
	);
}
