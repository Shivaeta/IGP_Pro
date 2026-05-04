<?php
/**
 * AI Copilot service facade.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

class IGP_AI_Copilot_Service {
	public static function parse_yaml( string $yaml ): array|WP_Error {
		$parsed = class_exists( 'IGP_AI_Copilot_YAML_Parser' ) ? IGP_AI_Copilot_YAML_Parser::parse( $yaml ) : new WP_Error( 'igp_ai_parser_missing', __( 'AI YAML parser is unavailable.', 'igp-pro' ) );
		if ( is_wp_error( $parsed ) ) { self::log_error( $parsed, 'ai_copilot_parse' ); return $parsed; }
		$normalized = class_exists( 'IGP_AI_Copilot_Normalizer' ) ? IGP_AI_Copilot_Normalizer::normalize( $parsed ) : new WP_Error( 'igp_ai_normalizer_missing', __( 'AI normalizer is unavailable.', 'igp-pro' ) );
		if ( is_wp_error( $normalized ) ) { self::log_error( $normalized, 'ai_copilot_normalize' ); return $normalized; }
		return array( 'parsed' => $parsed, 'normalized' => $normalized );
	}

	public static function validate_yaml( string $yaml ): array|WP_Error {
		$parsed = self::parse_yaml( $yaml );
		if ( is_wp_error( $parsed ) ) { return $parsed; }
		$result = class_exists( 'IGP_AI_Copilot_Draft_Validator' ) ? IGP_AI_Copilot_Draft_Validator::validate( $parsed['normalized'] ) : new WP_Error( 'igp_ai_validator_missing', __( 'AI draft validator is unavailable.', 'igp-pro' ) );
		if ( is_wp_error( $result ) ) { self::log_error( $result, 'ai_copilot_validate' ); return $result; }
		return array( 'normalized' => $parsed['normalized'], 'validation' => $result );
	}

	public static function compile_yaml( string $yaml, array $context = array() ): array|WP_Error {
		$validated = self::validate_yaml( $yaml );
		if ( is_wp_error( $validated ) ) { return $validated; }
		if ( empty( $validated['validation']['valid'] ) ) {
			return new WP_Error( 'igp_ai_validation_failed', __( 'AI draft validation failed.', 'igp-pro' ), array( 'validation' => $validated['validation'] ) );
		}
		$compiled = class_exists( 'IGP_AI_Copilot_Compiler' ) ? IGP_AI_Copilot_Compiler::compile( $validated['normalized'], $context ) : new WP_Error( 'igp_ai_compiler_missing', __( 'AI compiler is unavailable.', 'igp-pro' ) );
		if ( is_wp_error( $compiled ) ) { self::log_error( $compiled, 'ai_copilot_compile' ); return $compiled; }
		return $compiled;
	}

	public static function preview_yaml( string $yaml, array $context = array() ): string|WP_Error {
		$compiled = self::compile_yaml( $yaml, $context );
		if ( is_wp_error( $compiled ) ) { return $compiled; }
		$preview = class_exists( 'IGP_AI_Copilot_Preview' ) ? IGP_AI_Copilot_Preview::render_preview( $compiled ) : new WP_Error( 'igp_ai_preview_missing', __( 'AI preview service is unavailable.', 'igp-pro' ) );
		if ( is_wp_error( $preview ) ) { self::log_error( $preview, 'ai_copilot_preview' ); return $preview; }
		return $preview;
	}

	public static function create_draft_from_yaml( string $yaml, array $context = array() ): array|WP_Error {
		$compiled = self::compile_yaml( $yaml, $context );
		if ( is_wp_error( $compiled ) ) { return $compiled; }
		if ( empty( $compiled['content_graph'] ) || ! is_array( $compiled['content_graph'] ) ) { return new WP_Error( 'igp_ai_missing_compiled_graph', __( 'Compiled Content Graph is missing.', 'igp-pro' ) ); }
		$post_type = self::content_type_to_post_type( (string) ( $compiled['content_type'] ?? '' ) );
		if ( ! post_type_exists( $post_type ) ) { return new WP_Error( 'igp_ai_post_type_unavailable', __( 'Target post type is unavailable.', 'igp-pro' ) ); }
		$capability = function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'ai_copilot' ) : 'edit_posts';
		if ( ! current_user_can( $capability ) ) { return new WP_Error( 'igp_ai_cannot_create_draft', __( 'You do not have permission to create AI Copilot drafts.', 'igp-pro' ) ); }
		$post_type_object = get_post_type_object( $post_type );
		if ( $post_type_object && isset( $post_type_object->cap->create_posts ) && ! current_user_can( $post_type_object->cap->create_posts ) ) { return new WP_Error( 'igp_ai_cannot_create_draft', __( 'You do not have permission to create drafts for this post type.', 'igp-pro' ) ); }

		$post_id = wp_insert_post( array( 'post_type' => $post_type, 'post_status' => 'draft', 'post_title' => sanitize_text_field( (string) ( $compiled['title'] ?? __( 'AI Copilot Draft', 'igp-pro' ) ) ), 'post_name' => ! empty( $compiled['slug'] ) ? sanitize_title( (string) $compiled['slug'] ) : '', 'post_content' => '' ), true );
		if ( is_wp_error( $post_id ) ) { self::log_error( $post_id, 'ai_copilot_create_draft' ); return $post_id; }
		$post_id = absint( $post_id );
		$seo = isset( $compiled['seo'] ) && is_array( $compiled['seo'] ) ? $compiled['seo'] : array();
		if ( ! class_exists( 'IGP_Content_Graph_Save_Service' ) ) { $error = new WP_Error( 'igp_ai_save_service_missing', __( 'Canonical Content Graph save service is unavailable.', 'igp-pro' ) ); wp_delete_post( $post_id, true ); return $error; }
		$save = IGP_Content_Graph_Save_Service::save(
			$post_id,
			$compiled['content_graph'],
			array(
				'check_capability' => false,
				'meta_description' => isset( $seo['description'] ) ? (string) $seo['description'] : ( isset( $seo['meta_description'] ) ? (string) $seo['meta_description'] : '' ),
				'source_module'    => 'ai_copilot',
				'actor_type'       => 'human',
				'reason'           => 'ai_copilot_create_draft',
			)
		);
		if ( is_wp_error( $save ) ) { wp_delete_post( $post_id, true ); self::log_error( $save, 'ai_copilot_save_draft_graph', $post_id ); return $save; }
		$snapshot_id = isset( $save['snapshot_id'] ) ? (string) $save['snapshot_id'] : '';
		if ( function_exists( 'igp_pro_log' ) ) { igp_pro_log( array( 'actor_type' => 'human', 'operation' => 'ai_copilot_create_draft', 'object_type' => 'post', 'object_id' => $post_id, 'source_module' => 'ai_copilot', 'status' => 'success', 'snapshot_id' => $snapshot_id, 'summary' => 'AI Copilot draft created through validated compiler pipeline and canonical Content Graph save service.' ) ); }
		return array( 'post_id' => $post_id, 'post_type' => $post_type, 'post_status' => 'draft', 'edit_link' => get_edit_post_link( $post_id, 'raw' ), 'snapshot_id' => $snapshot_id, 'compiled' => $compiled );
	}


	public static function create_changeset_from_yaml( string $yaml, array $context = array() ): array|WP_Error {
		if ( ! class_exists( 'IGP_AI_Copilot_Changeset' ) ) {
			return new WP_Error( 'igp_ai_changeset_service_missing', __( 'AI Copilot changeset service is unavailable.', 'igp-pro' ) );
		}
		$context['source']     = isset( $context['source'] ) ? sanitize_key( (string) $context['source'] ) : 'ai_copilot_service';
		$context['actor_type'] = isset( $context['actor_type'] ) ? sanitize_key( (string) $context['actor_type'] ) : ( is_user_logged_in() ? 'human' : 'mcp' );
		$result = IGP_AI_Copilot_Changeset::create_from_yaml( $yaml, $context );
		if ( is_wp_error( $result ) ) { self::log_error( $result, 'ai_copilot_create_changeset' ); return $result; }
		return $result;
	}

	private static function content_type_to_post_type( string $content_type ): string {
		$map = array( 'tour_page' => 'tour', 'destination_page' => 'destination', 'landing_page' => 'page', 'blog_support_page' => 'post', 'industry_template_page' => 'page' );
		return $map[ sanitize_key( $content_type ) ] ?? 'page';
	}

	private static function log_error( WP_Error $error, string $operation, int $object_id = 0 ): void {
		if ( function_exists( 'igp_pro_log_wp_error' ) ) { igp_pro_log_wp_error( $error, $operation, 'ai_copilot', 'ai_draft', $object_id ); return; }
		if ( function_exists( 'igp_pro_log' ) ) { igp_pro_log( array( 'actor_type' => is_user_logged_in() ? 'human' : 'anonymous', 'operation' => $operation, 'object_type' => 'ai_draft', 'object_id' => $object_id, 'source_module' => 'ai_copilot', 'status' => 'failure', 'error_code' => $error->get_error_code(), 'summary' => $error->get_error_message() ) ); }
	}
}
