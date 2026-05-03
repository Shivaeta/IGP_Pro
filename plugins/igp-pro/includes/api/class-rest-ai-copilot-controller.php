<?php
/**
 * REST controller for AI Copilot YAML intake operations.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Exposes controlled AI Copilot endpoints backed by IGP_AI_Copilot_Service.
 */
class IGP_REST_AI_Copilot_Controller {
	private const REST_NAMESPACE = 'igp/v1';
	private const REST_BASE      = 'ai-copilot';

	/**
	 * Register WordPress REST hooks.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register AI Copilot REST endpoints.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/contract',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_contract' ),
					'permission_callback' => array( __CLASS__, 'permission_read' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/blocks',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_blocks' ),
					'permission_callback' => array( __CLASS__, 'permission_read' ),
				),
			)
		);

		foreach ( self::write_routes() as $route => $callback ) {
			register_rest_route(
				self::REST_NAMESPACE,
				'/' . self::REST_BASE . '/' . $route,
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( __CLASS__, $callback ),
						'permission_callback' => array( __CLASS__, 'permission_write' ),
						'args'                => self::yaml_args_schema( 'create-draft' === $route ),
					),
				)
			);
		}
	}

	/**
	 * Return write-route callback map.
	 *
	 * @return array<string,string>
	 */
	private static function write_routes(): array {
		return array(
			'parse'        => 'parse_yaml',
			'validate'     => 'validate_yaml',
			'compile'      => 'compile_yaml',
			'preview'      => 'preview_yaml',
			'create-draft'     => 'create_draft_from_yaml',
			'create-changeset' => 'create_changeset_from_yaml',
		);
	}

	/**
	 * Request schema for YAML endpoints.
	 *
	 * @param bool $draft Whether this route creates a draft.
	 * @return array<string,array<string,mixed>>
	 */
	private static function yaml_args_schema( bool $draft = false ): array {
		$args = array(
			'yaml'    => array(
				'description'       => __( 'AI Copilot YAML draft.', 'igp-pro' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => static function ( $value ) {
					return is_string( $value ) && '' !== trim( $value );
				},
			),
			'context' => array(
				'description'       => __( 'Optional compile/render context. Values are sanitized before reaching the service layer.', 'igp-pro' ),
				'type'              => 'object',
				'required'          => false,
				'validate_callback' => static function ( $value ) {
					return null === $value || is_array( $value ) || is_object( $value );
				},
			),
		);

		$args['target_post_id'] = array(
			'description'       => __( 'Optional existing post ID to attach a review changeset to. Omit or pass 0 to create a new draft on approval.', 'igp-pro' ),
			'type'              => 'integer',
			'required'          => false,
			'default'           => 0,
			'validate_callback' => static function ( $value ) {
				return is_numeric( $value ) && absint( $value ) >= 0;
			},
		);

		if ( $draft ) {
			$args['confirm_draft_only'] = array(
				'description'       => __( 'Required explicit confirmation that REST may only create a draft and never publish.', 'igp-pro' ),
				'type'              => 'boolean',
				'required'          => false,
				'default'           => true,
				'validate_callback' => static function ( $value ) {
					return is_bool( $value ) || in_array( $value, array( '1', '0', 1, 0, 'true', 'false' ), true );
				},
			);
		}

		return $args;
	}

	/**
	 * Permission callback for read endpoints.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public static function permission_read( WP_REST_Request $request ) {
		return self::check_permission( $request, false );
	}

	/**
	 * Permission callback for POST endpoints.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public static function permission_write( WP_REST_Request $request ) {
		return self::check_permission( $request, true );
	}

	/**
	 * Shared permission and authentication policy.
	 *
	 * POST routes require either a valid REST nonce for cookie-based sessions or
	 * an Authorization header for application-password/OAuth-style clients. The
	 * endpoint still requires the controlled AI Copilot capability in all cases.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $mutation Whether the route accepts a body/action.
	 * @return true|WP_Error
	 */
	private static function check_permission( WP_REST_Request $request, bool $mutation ) {
		$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'ai_copilot' ) : 'edit_posts';

		$capability_check = function_exists( 'igp_pro_rest_check_capability' )
			? igp_pro_rest_check_capability( $capability, $request )
			: ( current_user_can( $capability ) ? true : new WP_Error( 'igp_pro_rest_forbidden', __( 'You do not have permission to use IGP AI Copilot REST endpoints.', 'igp-pro' ), array( 'status' => rest_authorization_required_code() ) ) );

		if ( is_wp_error( $capability_check ) ) {
			return $capability_check;
		}

		if ( ! $mutation ) {
			return true;
		}

		if ( self::has_rest_nonce( $request ) || self::has_authorization_header( $request ) ) {
			return true;
		}

		self::log_rest_event(
			'rest_ai_copilot_auth_failed',
			'failure',
			0,
			'igp_ai_rest_auth_required',
			__( 'AI Copilot REST write-like request blocked because it had no REST nonce or application authentication header.', 'igp-pro' )
		);

		return new WP_Error(
			'igp_ai_rest_auth_required',
			__( 'AI Copilot REST write-like requests require a valid REST nonce or application authentication.', 'igp-pro' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Check for a syntactically valid REST nonce header/parameter.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	private static function has_rest_nonce( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'x-wp-nonce' );
		if ( '' === $nonce ) {
			$nonce = $request->get_header( 'x_wp_nonce' );
		}
		if ( '' === $nonce ) {
			$nonce = (string) $request->get_param( '_wpnonce' );
		}

		return '' !== $nonce && (bool) wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' );
	}

	/**
	 * Detect application/basic/bearer authentication.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	private static function has_authorization_header( WP_REST_Request $request ): bool {
		$authorization = $request->get_header( 'authorization' );
		if ( '' === $authorization && isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$authorization = (string) wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] );
		}

		if ( is_string( $authorization ) && preg_match( '/^\s*(Basic|Bearer)\s+/i', $authorization ) === 1 ) {
			return true;
		}

		return ! empty( $_SERVER['PHP_AUTH_USER'] );
	}

	/**
	 * GET /contract.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_contract( WP_REST_Request $request ) {
		unset( $request );

		if ( ! function_exists( 'igp_ai_copilot_get_yaml_contract' ) ) {
			return self::error_response( new WP_Error( 'igp_ai_contract_missing', __( 'AI Copilot YAML contract is unavailable.', 'igp-pro' ) ) );
		}

		return self::response(
			array(
				'contract' => igp_ai_copilot_get_yaml_contract(),
			)
		);
	}

	/**
	 * GET /blocks.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_blocks( WP_REST_Request $request ) {
		unset( $request );

		$aliases = class_exists( 'IGP_AI_Copilot_Block_Map' ) ? IGP_AI_Copilot_Block_Map::get_supported_aliases() : array();
		$registry = array();

		if ( function_exists( 'igp_pro_get_block_registry' ) ) {
			foreach ( igp_pro_get_block_registry() as $block_id => $definition ) {
				$registry[] = array(
					'id'          => sanitize_key( (string) $block_id ),
					'title'       => isset( $definition['title'] ) ? sanitize_text_field( (string) $definition['title'] ) : ( function_exists( 'igp_pro_block_id_to_title' ) ? igp_pro_block_id_to_title( (string) $block_id ) : (string) $block_id ),
					'description' => isset( $definition['description'] ) ? sanitize_text_field( (string) $definition['description'] ) : '',
					'category'    => isset( $definition['category'] ) ? sanitize_key( (string) $definition['category'] ) : 'content',
				);
			}
		}

		return self::response(
			array(
				'aliases'           => $aliases,
				'registered_blocks' => $registry,
			)
		);
	}

	/**
	 * POST /parse.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function parse_yaml( WP_REST_Request $request ) {
		$service = self::require_service();
		if ( is_wp_error( $service ) ) {
			return self::error_response( $service );
		}

		$result = IGP_AI_Copilot_Service::parse_yaml( self::get_yaml( $request ) );
		return self::service_result( $result, 'rest_ai_copilot_parse' );
	}

	/**
	 * POST /validate.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function validate_yaml( WP_REST_Request $request ) {
		$service = self::require_service();
		if ( is_wp_error( $service ) ) {
			return self::error_response( $service );
		}

		$result = IGP_AI_Copilot_Service::validate_yaml( self::get_yaml( $request ) );
		return self::service_result( $result, 'rest_ai_copilot_validate' );
	}

	/**
	 * POST /compile.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function compile_yaml( WP_REST_Request $request ) {
		$service = self::require_service();
		if ( is_wp_error( $service ) ) {
			return self::error_response( $service );
		}

		$result = IGP_AI_Copilot_Service::compile_yaml( self::get_yaml( $request ), self::get_context( $request ) );
		return self::service_result( $result, 'rest_ai_copilot_compile' );
	}

	/**
	 * POST /preview.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function preview_yaml( WP_REST_Request $request ) {
		$service = self::require_service();
		if ( is_wp_error( $service ) ) {
			return self::error_response( $service );
		}

		$yaml    = self::get_yaml( $request );
		$context = self::get_context( $request );
		$preview = IGP_AI_Copilot_Service::preview_yaml( $yaml, $context );

		if ( is_wp_error( $preview ) ) {
			return self::service_result( $preview, 'rest_ai_copilot_preview' );
		}

		$compiled = IGP_AI_Copilot_Service::compile_yaml( $yaml, $context );
		$data     = array(
			'preview'  => $preview,
			'compiled' => is_wp_error( $compiled ) ? null : $compiled,
		);

		return self::service_result( $data, 'rest_ai_copilot_preview' );
	}

	/**
	 * POST /create-draft.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_draft_from_yaml( WP_REST_Request $request ) {
		$service = self::require_service();
		if ( is_wp_error( $service ) ) {
			return self::error_response( $service );
		}

		$confirm_draft_only = $request->get_param( 'confirm_draft_only' );
		if ( null !== $confirm_draft_only && ! rest_sanitize_boolean( $confirm_draft_only ) ) {
			return self::error_response(
				new WP_Error(
					'igp_ai_rest_draft_only_required',
					__( 'AI Copilot REST can only create drafts in Phase 14. Direct publish is unavailable.', 'igp-pro' ),
					array( 'status' => 400 )
				)
			);
		}

		$result = IGP_AI_Copilot_Service::create_draft_from_yaml( self::get_yaml( $request ), self::get_context( $request ) );
		return self::service_result( $result, 'rest_ai_copilot_create_draft' );
	}


	/**
	 * POST /create-changeset.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_changeset_from_yaml( WP_REST_Request $request ) {
		$service = self::require_service();
		if ( is_wp_error( $service ) ) {
			return self::error_response( $service );
		}

		$result = IGP_AI_Copilot_Service::create_changeset_from_yaml( self::get_yaml( $request ), self::get_context( $request ) );
		return self::service_result( $result, 'rest_ai_copilot_create_changeset' );
	}

	/**
	 * Require the service facade.
	 *
	 * @return true|WP_Error
	 */
	private static function require_service() {
		if ( ! class_exists( 'IGP_AI_Copilot_Service' ) ) {
			return new WP_Error( 'igp_ai_service_missing', __( 'AI Copilot service facade is unavailable.', 'igp-pro' ), array( 'status' => 500 ) );
		}

		return true;
	}

	/**
	 * Extract raw YAML without sanitizing away unsafe content before parser checks.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return string
	 */
	private static function get_yaml( WP_REST_Request $request ): string {
		$yaml = $request->get_param( 'yaml' );
		return is_scalar( $yaml ) ? (string) $yaml : '';
	}

	/**
	 * Extract and sanitize optional context values.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	private static function get_context( WP_REST_Request $request ): array {
		$context = $request->get_param( 'context' );
		if ( is_object( $context ) ) {
			$context = (array) $context;
		}
		if ( ! is_array( $context ) ) {
			$context = array();
		}

		$context = self::sanitize_context( $context );
		$target_post_id = absint( $request->get_param( 'target_post_id' ) );
		if ( $target_post_id > 0 ) {
			$context['target_post_id'] = $target_post_id;
		}
		$context['source'] = 'rest_ai_copilot';
		$context['rest']   = true;

		return $context;
	}

	/**
	 * Recursively sanitize context data.
	 *
	 * @param mixed $value Raw context value.
	 * @return mixed
	 */
	private static function sanitize_context( $value ) {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $key => $item ) {
				$key = is_scalar( $key ) ? sanitize_key( (string) $key ) : '';
				if ( '' === $key ) {
					continue;
				}
				$out[ $key ] = self::sanitize_context( $item );
			}
			return $out;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return $value;
		}

		if ( is_scalar( $value ) ) {
			return sanitize_text_field( (string) $value );
		}

		return null;
	}

	/**
	 * Format service result into a structured REST response.
	 *
	 * @param mixed  $result    Result or WP_Error.
	 * @param string $operation Operation slug.
	 * @return WP_REST_Response|WP_Error
	 */
	private static function service_result( $result, string $operation ) {
		if ( is_wp_error( $result ) ) {
			self::log_rest_event( $operation, 'failure', 0, $result->get_error_code(), $result->get_error_message() );
			return self::error_response( $result );
		}

		$object_id = is_array( $result ) && isset( $result['post_id'] ) ? absint( $result['post_id'] ) : 0;
		self::log_rest_event( $operation, 'success', $object_id );

		return self::response( $result );
	}

	/**
	 * Build a successful response envelope.
	 *
	 * @param mixed $data Payload.
	 * @return WP_REST_Response
	 */
	private static function response( $data ): WP_REST_Response {
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $data,
			)
		);
	}

	/**
	 * Build a structured REST error.
	 *
	 * @param WP_Error $error Error object.
	 * @return WP_Error
	 */
	private static function error_response( WP_Error $error ): WP_Error {
		$status = $error->get_error_data();
		$status = is_array( $status ) && isset( $status['status'] ) ? absint( $status['status'] ) : 400;

		return new WP_Error(
			$error->get_error_code(),
			$error->get_error_message(),
			array(
				'success' => false,
				'status'  => $status > 0 ? $status : 400,
				'error'   => array(
					'code'    => $error->get_error_code(),
					'message' => $error->get_error_message(),
					'data'    => $error->get_error_data(),
				),
			)
		);
	}

	/**
	 * Log REST Copilot events without duplicating parser/compiler logic.
	 *
	 * @param string $operation  Operation slug.
	 * @param string $status     success|failure.
	 * @param int    $object_id  Optional object ID.
	 * @param string $error_code Optional error code.
	 * @param string $summary    Optional summary.
	 */
	private static function log_rest_event( string $operation, string $status, int $object_id = 0, string $error_code = '', string $summary = '' ): void {
		if ( ! function_exists( 'igp_pro_log' ) ) {
			return;
		}

		igp_pro_log(
			array(
				'actor_type'    => is_user_logged_in() ? 'human' : 'anonymous',
				'operation'     => sanitize_key( $operation ),
				'object_type'   => 0 < $object_id ? 'post' : 'ai_draft',
				'object_id'     => $object_id,
				'source_module' => 'rest-ai-copilot',
				'source'        => 'rest',
				'status'        => 'success' === $status ? 'success' : 'failure',
				'error_code'    => sanitize_key( $error_code ),
				'summary'       => '' !== $summary ? sanitize_text_field( $summary ) : sprintf( 'AI Copilot REST operation %s.', 'success' === $status ? 'completed' : 'failed' ),
			)
		);
	}
}
