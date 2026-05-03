<?php
/**
 * IGP MCP tool registry.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Describes safe MCP tools without exposing unsafe write shortcuts.
 */
class IGP_MCP_Tool_Registry {
	/** @var array<string,array<string,mixed>> */
	private static array $tools = array();

	/** Register a safe tool descriptor. */
	public static function register_tool( string $name, array $definition ): void {
		$name = sanitize_key( $name );
		if ( '' === $name || self::is_forbidden_tool( $name ) ) {
			return;
		}
		self::$tools[ $name ] = self::sanitize_definition( $definition );
	}

	/** Return all safe tool descriptors. */
	public static function get_tools(): array {
		return self::$tools;
	}

	/** Return a manifest for MCP clients. */
	public static function get_manifest(): array {
		return array(
			'name'              => 'igp-mcp-bridge',
			'version'           => defined( 'IGP_PRO_VERSION' ) ? IGP_PRO_VERSION : '1.0.0',
			'enabled'           => self::is_enabled(),
			'transport'         => 'external_stdio_node_server',
			'wordpress_rest'    => self::safe_rest_url( 'igp/v1/' ),
			'authority'         => 'IGP_AI_Copilot_Service',
			'forbidden_actions' => self::forbidden_tool_names(),
			'tools'             => array_values( self::get_tools_with_urls() ),
		);
	}

	/** MCP bridge feature flag state. */
	public static function is_enabled(): bool {
		return function_exists( 'igp_feature_enabled' ) ? igp_feature_enabled( 'enable_mcp_bridge' ) : false;
	}

	/** Forbidden tool names that must never be registered. */
	public static function forbidden_tool_names(): array {
		return array(
			'igp_write_post_meta',
			'igp_write_content_graph_json',
			'igp_execute_sql',
			'igp_edit_plugin_file',
			'igp_publish_without_review',
		);
	}

	private static function is_forbidden_tool( string $name ): bool {
		return in_array( sanitize_key( $name ), self::forbidden_tool_names(), true );
	}

	/**
	 * Sanitize a tool descriptor without calling rest_url().
	 *
	 * This method is intentionally bootstrap-safe. The registry is populated while
	 * the plugin loader is running, which can happen before the global
	 * $wp_rewrite object exists. Calling rest_url() at that point can fatal inside
	 * WordPress core. Store normalized REST paths here and resolve full URLs only
	 * when a manifest is requested after WordPress REST/bootstrap is available.
	 */
	private static function sanitize_definition( array $definition ): array {
		$rest_path = isset( $definition['rest_path'] ) ? self::normalize_rest_path( (string) $definition['rest_path'] ) : '';

		return array(
			'name'         => isset( $definition['name'] ) ? sanitize_key( (string) $definition['name'] ) : '',
			'title'        => isset( $definition['title'] ) ? sanitize_text_field( (string) $definition['title'] ) : '',
			'description'  => isset( $definition['description'] ) ? sanitize_textarea_field( (string) $definition['description'] ) : '',
			'method'       => isset( $definition['method'] ) ? strtoupper( sanitize_text_field( (string) $definition['method'] ) ) : 'POST',
			'rest_path'    => $rest_path,
			'safe_write'   => ! empty( $definition['safe_write'] ),
			'input_schema' => isset( $definition['input_schema'] ) && is_array( $definition['input_schema'] ) ? $definition['input_schema'] : array(),
		);
	}

	/**
	 * Return registered tools with lazily resolved REST URLs.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function get_tools_with_urls(): array {
		$tools = self::$tools;
		foreach ( $tools as $name => $tool ) {
			$path = isset( $tool['rest_path'] ) ? (string) $tool['rest_path'] : '';
			$tools[ $name ]['rest_url'] = '' !== $path ? self::safe_rest_url( $path ) : '';
		}
		return $tools;
	}

	/** Normalize a REST path to a leading-slash path under /wp-json. */
	private static function normalize_rest_path( string $path ): string {
		$path = trim( $path );
		$path = preg_replace( '#^https?://[^/]+/wp-json/#i', '', $path );
		$path = preg_replace( '#^/wp-json/#i', '', (string) $path );
		$path = ltrim( (string) $path, '/' );
		$path = preg_replace( '#[^A-Za-z0-9_./-]#', '', (string) $path );
		return '' === $path ? '' : '/' . $path;
	}

	/**
	 * Build a REST URL without fatalling during very early WordPress bootstrap.
	 */
	private static function safe_rest_url( string $path ): string {
		$path = self::normalize_rest_path( $path );

		if ( function_exists( 'rest_url' ) && isset( $GLOBALS['wp_rewrite'] ) && is_object( $GLOBALS['wp_rewrite'] ) ) {
			return esc_url_raw( rest_url( ltrim( $path, '/' ) ) );
		}

		if ( function_exists( 'home_url' ) ) {
			return esc_url_raw( home_url( '/wp-json' . $path ) );
		}

		return '/wp-json' . $path;
	}
}
