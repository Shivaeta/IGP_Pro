<?php
/**
 * Safe MCP tool descriptors and REST status/log endpoints.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers AI Copilot MCP bridge metadata.
 */
class IGP_MCP_AI_Copilot_Tools {
	private const REST_NAMESPACE = 'igp/v1';
	private const REST_BASE      = 'mcp';

	/** Bootstrap safe MCP tool registry. */
	public static function register(): void {
		self::register_tools();
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/** Register MCP status/tool/log routes. */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'status' ),
					'permission_callback' => array( __CLASS__, 'permission' ),
				),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/tools',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'tools' ),
					'permission_callback' => array( __CLASS__, 'permission' ),
				),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/log',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'log_tool_call' ),
					'permission_callback' => array( __CLASS__, 'permission' ),
					'args'                => array(
						'tool'   => array( 'type' => 'string', 'required' => true ),
						'status' => array( 'type' => 'string', 'required' => true ),
						'summary'=> array( 'type' => 'string', 'required' => false ),
					),
				),
			)
		);
	}

	/** Permission check for MCP metadata/log routes. */
	public static function permission( WP_REST_Request $request ) {
		$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'ai_copilot' ) : 'edit_posts';
		$allowed = function_exists( 'igp_pro_rest_check_capability' ) ? igp_pro_rest_check_capability( $capability, $request ) : ( current_user_can( $capability ) ? true : new WP_Error( 'igp_mcp_forbidden', __( 'You do not have permission to use the IGP MCP Bridge.', 'igp-pro' ), array( 'status' => rest_authorization_required_code() ) ) );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		return true;
	}

	/** Return bridge status. */
	public static function status( WP_REST_Request $request ) {
		unset( $request );
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'enabled'      => class_exists( 'IGP_MCP_Tool_Registry' ) && IGP_MCP_Tool_Registry::is_enabled(),
					'feature_flag' => 'enable_mcp_bridge',
					'message'      => class_exists( 'IGP_MCP_Tool_Registry' ) && IGP_MCP_Tool_Registry::is_enabled() ? __( 'IGP MCP Bridge is enabled.', 'igp-pro' ) : __( 'IGP MCP Bridge is disabled by feature flag.', 'igp-pro' ),
				),
			)
		);
	}

	/** Return MCP tool manifest only when enabled. */
	public static function tools( WP_REST_Request $request ) {
		unset( $request );
		if ( ! class_exists( 'IGP_MCP_Tool_Registry' ) || ! IGP_MCP_Tool_Registry::is_enabled() ) {
			return new WP_Error( 'igp_mcp_disabled', __( 'IGP MCP Bridge is disabled by feature flag.', 'igp-pro' ), array( 'status' => 403 ) );
		}
		return rest_ensure_response( array( 'success' => true, 'data' => IGP_MCP_Tool_Registry::get_manifest() ) );
	}

	/** Log MCP tool calls/write attempts from the external server. */
	public static function log_tool_call( WP_REST_Request $request ) {
		if ( function_exists( 'igp_pro_log' ) ) {
			igp_pro_log(
				array(
					'actor_type'    => 'mcp',
					'operation'     => 'mcp_tool_call',
					'object_type'   => 'mcp_tool',
					'object_id'     => 0,
					'source_module' => 'mcp_bridge',
					'source'        => 'mcp',
					'status'        => 'success' === sanitize_key( (string) $request->get_param( 'status' ) ) ? 'success' : 'failure',
					'summary'       => sanitize_textarea_field( (string) $request->get_param( 'summary' ) ),
					'tool'          => sanitize_key( (string) $request->get_param( 'tool' ) ),
				)
			);
		}
		return rest_ensure_response( array( 'success' => true, 'data' => array( 'logged' => true ) ) );
	}

	/** Register exactly the safe Phase 16 tools. */
	private static function register_tools(): void {
		if ( ! class_exists( 'IGP_MCP_Tool_Registry' ) ) {
			return;
		}
		$tools = array(
			array( 'name' => 'igp_ai_get_yaml_contract', 'title' => 'Get YAML Contract', 'method' => 'GET', 'rest_path' => 'igp/v1/ai-copilot/contract', 'safe_write' => false, 'description' => 'Return the current AI Copilot YAML contract.' ),
			array( 'name' => 'igp_ai_get_supported_blocks', 'title' => 'Get Supported Blocks', 'method' => 'GET', 'rest_path' => 'igp/v1/ai-copilot/blocks', 'safe_write' => false, 'description' => 'Return supported AI block aliases and registered IGP blocks.' ),
			array( 'name' => 'igp_ai_validate_yaml', 'title' => 'Validate YAML', 'method' => 'POST', 'rest_path' => 'igp/v1/ai-copilot/validate', 'safe_write' => false, 'description' => 'Validate YAML without saving content.' ),
			array( 'name' => 'igp_ai_compile_yaml', 'title' => 'Compile YAML', 'method' => 'POST', 'rest_path' => 'igp/v1/ai-copilot/compile', 'safe_write' => false, 'description' => 'Compile YAML into Content Graph without saving.' ),
			array( 'name' => 'igp_ai_preview_yaml', 'title' => 'Preview YAML', 'method' => 'POST', 'rest_path' => 'igp/v1/ai-copilot/preview', 'safe_write' => false, 'description' => 'Render a central-renderer preview without saving.' ),
			array( 'name' => 'igp_ai_create_draft_from_yaml', 'title' => 'Create Draft From YAML', 'method' => 'POST', 'rest_path' => 'igp/v1/ai-copilot/create-draft', 'safe_write' => true, 'description' => 'Create a WordPress draft only after Copilot validation and compile checks pass. Never publishes.' ),
			array( 'name' => 'igp_ai_create_changeset_from_yaml', 'title' => 'Create Changeset From YAML', 'method' => 'POST', 'rest_path' => 'igp/v1/ai-copilot/create-changeset', 'safe_write' => true, 'description' => 'Create a reviewable AI changeset. Human approval is required before content is saved.' ),
		);
		foreach ( $tools as $tool ) {
			IGP_MCP_Tool_Registry::register_tool( $tool['name'], $tool );
		}
	}
}
