<?php
/**
 * AI Copilot YAML parser.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

class IGP_AI_Copilot_YAML_Parser {
	private const MAX_BYTES = 200000;

	/** Parse YAML into a PHP array. */
	public static function parse( string $yaml ): array|WP_Error {
		$yaml = str_replace( "\0", '', $yaml );
		$preflight = self::preflight( $yaml );
		if ( is_wp_error( $preflight ) ) {
			return $preflight;
		}

		try {
			if ( function_exists( 'yaml_parse' ) ) {
				$parsed = @yaml_parse( $yaml );
				if ( false === $parsed || null === $parsed || ! is_array( $parsed ) ) {
					return new WP_Error( 'igp_ai_yaml_malformed', __( 'Malformed YAML payload.', 'igp-pro' ) );
				}
			} else {
				$parsed = self::parse_subset( $yaml );
			}
		} catch ( Throwable $e ) {
			return new WP_Error( 'igp_ai_yaml_exception', sprintf( __( 'YAML parser failed safely: %s', 'igp-pro' ), $e->getMessage() ) );
		}

		$scan = self::scan_value_safety( $parsed );
		if ( is_wp_error( $scan ) ) {
			return $scan;
		}

		return $parsed;
	}

	/** Preflight raw YAML for unsupported features and unsafe content. */
	private static function preflight( string $yaml ) {
		if ( '' === trim( $yaml ) ) {
			return new WP_Error( 'igp_ai_yaml_empty', __( 'YAML payload cannot be empty.', 'igp-pro' ) );
		}
		if ( strlen( $yaml ) > self::MAX_BYTES ) {
			return new WP_Error( 'igp_ai_yaml_oversized', __( 'YAML payload exceeds the allowed size.', 'igp-pro' ) );
		}

		$patterns = array(
			'igp_ai_php_rejected'          => '/<\?(?:php|=)?/i',
			'igp_ai_script_rejected'       => '/<\s*\/?\s*script\b/i',
			'igp_ai_inline_event_rejected' => '/\son[a-z0-9_:-]+\s*=/i',
			'igp_ai_protocol_rejected'     => '/(?:javascript|vbscript|data|file|phar)\s*:/i',
			'igp_ai_custom_tag_rejected'   => '/(^|\s)![!<A-Za-z_]/m',
			'igp_ai_html_rejected'         => '/<\/?(?:iframe|object|embed|form|input|button|link|meta|style|svg|math|img|video|audio)\b/i',
			'igp_ai_shortcode_rejected'    => '/\[[A-Za-z0-9_\-]+\b[^\]]*\]/',
			'igp_ai_executable_rejected'   => '/\.(?:php[0-9]?|phtml|phar|cgi|pl|py|rb|sh|bash|zsh|exe|dll|bat|cmd|msi)\b/i',
		);
		foreach ( $patterns as $code => $pattern ) {
			if ( preg_match( $pattern, $yaml ) ) {
				return new WP_Error( $code, __( 'Unsafe or unsupported YAML content was rejected.', 'igp-pro' ) );
			}
		}
		if ( preg_match( '/(^|\n)\s*(?:[-?]\s*)?[A-Za-z0-9_\-]+\s*:\s*&[A-Za-z0-9_\-]+/m', $yaml ) || preg_match( '/(^|\n)\s*(?:[-?]\s*)?[A-Za-z0-9_\-]+\s*:\s*\*[A-Za-z0-9_\-]+/m', $yaml ) || preg_match( '/(^|\n)\s*<<\s*:/m', $yaml ) ) {
			return new WP_Error( 'igp_ai_yaml_alias_rejected', __( 'YAML anchors, aliases, and merge keys are unsupported.', 'igp-pro' ) );
		}
		if ( preg_match( '/\bbase64\b|;base64,|[A-Za-z0-9+\/]{220,}={0,2}/', $yaml ) ) {
			return new WP_Error( 'igp_ai_binary_rejected', __( 'Base64 or binary payloads are not accepted in AI YAML.', 'igp-pro' ) );
		}
		return true;
	}

	/** Strict fallback parser for the supported YAML subset. */
	private static function parse_subset( string $yaml ): array|WP_Error {
		$lines = preg_split( '/\r\n|\r|\n/', $yaml );
		$index = 0;
		$result = self::parse_block( $lines ?: array(), $index, 0 );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return is_array( $result ) ? $result : array();
	}

	private static function parse_block( array $lines, int &$index, int $indent ) {
		$out = array();
		$is_list = null;
		$count = count( $lines );

		while ( $index < $count ) {
			$raw = rtrim( (string) $lines[ $index ] );
			if ( '' === trim( $raw ) || str_starts_with( ltrim( $raw ), '#' ) ) {
				$index++;
				continue;
			}
			$current_indent = strlen( $raw ) - strlen( ltrim( $raw, ' ' ) );
			if ( $current_indent < $indent ) {
				break;
			}
			if ( $current_indent > $indent ) {
				return new WP_Error( 'igp_ai_yaml_malformed_indent', __( 'Malformed YAML indentation.', 'igp-pro' ) );
			}

			$line = ltrim( $raw );
			if ( str_starts_with( $line, '- ' ) ) {
				if ( false === $is_list ) {
					return new WP_Error( 'igp_ai_yaml_mixed_collection', __( 'YAML cannot mix object and list entries at the same level.', 'igp-pro' ) );
				}
				$is_list = true;
				$item_text = substr( $line, 2 );
				$index++;
				$item = self::parse_list_item( $item_text, $lines, $index, $indent + 2 );
				if ( is_wp_error( $item ) ) { return $item; }
				$out[] = $item;
				continue;
			}

			if ( true === $is_list ) {
				return new WP_Error( 'igp_ai_yaml_mixed_collection', __( 'YAML cannot mix list and object entries at the same level.', 'igp-pro' ) );
			}
			$is_list = false;
			if ( ! preg_match( '/^([^:]+):(?:\s*(.*))?$/', $line, $m ) ) {
				return new WP_Error( 'igp_ai_yaml_malformed', sprintf( __( 'Malformed YAML near line %d.', 'igp-pro' ), $index + 1 ) );
			}
			$key = trim( $m[1] );
			$val = $m[2] ?? '';
			$index++;
			if ( '' === trim( $val ) ) {
				$out[ $key ] = self::parse_block( $lines, $index, $indent + 2 );
			} elseif ( '|' === trim( $val ) || '>' === trim( $val ) ) {
				$out[ $key ] = self::parse_multiline( $lines, $index, $indent + 2 );
			} else {
				$out[ $key ] = self::parse_scalar( $val );
			}
		}
		return $out;
	}

	private static function parse_list_item( string $item_text, array $lines, int &$index, int $child_indent ) {
		$item_text = trim( $item_text );
		if ( '' === $item_text ) {
			return self::parse_block( $lines, $index, $child_indent );
		}
		if ( preg_match( '/^([^:]+):(?:\s*(.*))?$/', $item_text, $m ) ) {
			$item = array();
			$key = trim( $m[1] );
			$val = $m[2] ?? '';
			if ( '' === trim( $val ) ) {
				$item[ $key ] = self::parse_block( $lines, $index, $child_indent );
			} else {
				$item[ $key ] = self::parse_scalar( $val );
			}
			$nested = self::parse_block( $lines, $index, $child_indent );
			if ( is_wp_error( $nested ) ) { return $nested; }
			if ( is_array( $nested ) ) {
				$item = array_merge( $item, $nested );
			}
			return $item;
		}
		return self::parse_scalar( $item_text );
	}

	private static function parse_multiline( array $lines, int &$index, int $indent ): string {
		$out = array();
		$count = count( $lines );
		while ( $index < $count ) {
			$raw = rtrim( (string) $lines[ $index ] );
			$current_indent = strlen( $raw ) - strlen( ltrim( $raw, ' ' ) );
			if ( '' !== trim( $raw ) && $current_indent < $indent ) { break; }
			$out[] = substr( $raw, min( $indent, strlen( $raw ) ) );
			$index++;
		}
		return implode( "\n", $out );
	}

	private static function parse_scalar( string $value ) {
		$value = trim( $value );
		if ( '' === $value ) { return ''; }
		if ( ( str_starts_with( $value, '"' ) && str_ends_with( $value, '"' ) ) || ( str_starts_with( $value, "'" ) && str_ends_with( $value, "'" ) ) ) {
			return stripcslashes( substr( $value, 1, -1 ) );
		}
		$lower = strtolower( $value );
		if ( in_array( $lower, array( 'true', 'false' ), true ) ) { return 'true' === $lower; }
		if ( in_array( $lower, array( 'null', '~' ), true ) ) { return null; }
		if ( is_numeric( $value ) ) { return str_contains( $value, '.' ) ? (float) $value : (int) $value; }
		return $value;
	}

	private static function scan_value_safety( $value, string $path = 'root' ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$result = self::scan_value_safety( $v, $path . '.' . (string) $k );
				if ( is_wp_error( $result ) ) { return $result; }
			}
			return true;
		}
		if ( is_resource( $value ) || ( is_object( $value ) && ! $value instanceof stdClass ) ) {
			return new WP_Error( 'igp_ai_yaml_unsupported_type', __( 'Unsupported YAML value type.', 'igp-pro' ), array( 'field' => $path ) );
		}
		if ( is_string( $value ) ) {
			return self::preflight( 'x: ' . $value );
		}
		return true;
	}
}
