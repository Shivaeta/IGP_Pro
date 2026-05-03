<?php
/**
 * Module loader for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load IGP Pro modules and register WordPress hooks.
 */
function igp_pro_load(): void {
	static $loaded = false;

	if ( $loaded ) {
		return;
	}

	$loaded = true;

	require_once IGP_PRO_PATH . 'includes/core/helpers.php';

	if ( file_exists( IGP_PRO_PATH . 'includes/core/feature-flags.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/core/feature-flags.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/core/capabilities.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/core/capabilities.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/core/logger.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/core/logger.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/api/rest-permissions.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/api/rest-permissions.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/core/assets.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/core/assets.php';
	}

	// Phase 5 cache primitives must be available before block rendering starts.
	if ( file_exists( IGP_PRO_PATH . 'includes/performance/cache.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/performance/cache.php';
	}

	require_once IGP_PRO_PATH . 'includes/cpt/taxonomies.php';
	require_once IGP_PRO_PATH . 'includes/cpt/register-cpt.php';
	require_once IGP_PRO_PATH . 'includes/blocks/registry.php';
	require_once IGP_PRO_PATH . 'includes/blocks/resolver.php';

	if ( file_exists( IGP_PRO_PATH . 'includes/blocks/heading-support.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/blocks/heading-support.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/blocks/style-support.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/blocks/style-support.php';
	}

	foreach ( array(
		'includes/design/token-validator.php',
		'includes/design/design-tokens.php',
		'includes/design/brand-profiles.php',
		'includes/design/css-generator.php',
	) as $igp_pro_design_file ) {
		if ( file_exists( IGP_PRO_PATH . $igp_pro_design_file ) ) {
			require_once IGP_PRO_PATH . $igp_pro_design_file;
		}
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/blocks/block-supports.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/blocks/block-supports.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/seo/outline-engine.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/seo/outline-engine.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/seo/heading-policy.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/seo/heading-policy.php';
	}

	require_once IGP_PRO_PATH . 'includes/blocks/renderer.php';

	if ( file_exists( IGP_PRO_PATH . 'includes/content/sanitizer.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/content/sanitizer.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/content/validator.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/content/validator.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/content/importer.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/content/importer.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/content/exporter.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/content/exporter.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/content/projection.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/content/projection.php';
	}

	if ( function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_starter_templates' ) ) {
		foreach ( array(
			'includes/starter-content/template-validator.php',
			'includes/starter-content/template-registry.php',
			'includes/starter-content/template-preview.php',
			'includes/starter-content/template-importer.php',
			'includes/starter-content/template-rollback.php',
		) as $igp_pro_starter_content_file ) {
			if ( file_exists( IGP_PRO_PATH . $igp_pro_starter_content_file ) ) {
				require_once IGP_PRO_PATH . $igp_pro_starter_content_file;
			}
		}
	}

	require_once IGP_PRO_PATH . 'includes/content/content-graph.php';

	if ( file_exists( IGP_PRO_PATH . 'includes/recovery/snapshots.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/recovery/snapshots.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/recovery/rollback.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/recovery/rollback.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/migration/schema-version-map.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/migration/schema-version-map.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/migration/block-migrations.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/migration/block-migrations.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/migration/content-graph-migrations.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/migration/content-graph-migrations.php';
	}

	if ( function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_relationship_layer' ) ) {
		foreach ( array(
			'includes/relationships/relationship-validator.php',
			'includes/relationships/relationships.php',
			'includes/relationships/relationship-queries.php',
			'includes/relationships/relationship-admin.php',
		) as $igp_pro_relationship_file ) {
			if ( file_exists( IGP_PRO_PATH . $igp_pro_relationship_file ) ) {
				require_once IGP_PRO_PATH . $igp_pro_relationship_file;
			}
		}
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/booking/pricing-engine.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/booking/pricing-engine.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/booking/payment-adapters/adapter-interface.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/booking/payment-adapters/adapter-interface.php';
	}

	foreach ( array( 'mock', 'razorpay', 'stripe', 'paypal' ) as $igp_pro_adapter ) {
		$igp_pro_adapter_file = IGP_PRO_PATH . 'includes/booking/payment-adapters/' . $igp_pro_adapter . '.php';
		if ( file_exists( $igp_pro_adapter_file ) ) {
			require_once $igp_pro_adapter_file;
		}
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/booking/booking-engine.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/booking/booking-engine.php';
	}

	// Phase 5 SEO and performance modules.
	if ( file_exists( IGP_PRO_PATH . 'includes/seo/schema-generator.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/seo/schema-generator.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/seo/seo-engine.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/seo/seo-engine.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/seo/seo-audit.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/seo/seo-audit.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/seo/internal-linking.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/seo/internal-linking.php';
	}

	if ( function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_rank_math_bridge' ) ) {
		foreach ( array(
			'includes/integrations/rank-math/content-provider.php',
			'includes/integrations/rank-math/schema-mapper.php',
			'includes/integrations/rank-math/rank-math-bridge.php',
		) as $igp_pro_rank_math_file ) {
			if ( file_exists( IGP_PRO_PATH . $igp_pro_rank_math_file ) ) {
				require_once IGP_PRO_PATH . $igp_pro_rank_math_file;
			}
		}
	}

	if ( function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_link_whisper_bridge' ) ) {
		foreach ( array(
			'includes/integrations/link-whisper/content-provider.php',
			'includes/integrations/link-whisper/opportunity-mapper.php',
			'includes/integrations/link-whisper/link-whisper-bridge.php',
		) as $igp_pro_link_whisper_file ) {
			if ( file_exists( IGP_PRO_PATH . $igp_pro_link_whisper_file ) ) {
				require_once IGP_PRO_PATH . $igp_pro_link_whisper_file;
			}
		}
	}

	if ( function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_media_optimizer' ) ) {
		foreach ( array(
			'includes/media/media-inventory.php',
			'includes/media/media-audit.php',
			'includes/media/webp-adapter.php',
			'includes/media/image-optimizer.php',
			'includes/media/lazy-loading-policy.php',
		) as $igp_pro_media_file ) {
			if ( file_exists( IGP_PRO_PATH . $igp_pro_media_file ) ) {
				require_once IGP_PRO_PATH . $igp_pro_media_file;
			}
		}
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/performance/cwv.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/performance/cwv.php';
	}


	// Phase 14.A AI Copilot intake services. Load after registry, renderer,
	// Content Graph, validator, logging, and snapshot foundations are available.
	foreach ( array(
		'includes/ai-copilot/block-aliases.php',
		'includes/ai-copilot/yaml-contract.php',
		'includes/ai-copilot/class-ai-copilot-yaml-parser.php',
		'includes/ai-copilot/class-ai-copilot-normalizer.php',
		'includes/ai-copilot/class-ai-copilot-block-map.php',
		'includes/ai-copilot/class-ai-copilot-draft-validator.php',
		'includes/ai-copilot/class-ai-copilot-content-mapper.php',
		'includes/ai-copilot/class-ai-copilot-compiler.php',
		'includes/ai-copilot/class-ai-copilot-preview.php',
		'includes/ai-copilot/class-ai-copilot-changeset.php',
		'includes/ai-copilot/class-ai-copilot-service.php',
	) as $igp_pro_ai_copilot_file ) {
		if ( file_exists( IGP_PRO_PATH . $igp_pro_ai_copilot_file ) ) {
			require_once IGP_PRO_PATH . $igp_pro_ai_copilot_file;
		}
	}

	// Phase 14 REST alignment. REST must call the AI Copilot service façade
	// and must not duplicate parser/compiler/save logic.
	if ( file_exists( IGP_PRO_PATH . 'includes/api/class-rest-ai-copilot-controller.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/api/class-rest-ai-copilot-controller.php';
	}

	// Phase 16 MCP bridge descriptors. The runtime bridge remains disabled
	// unless the enable_mcp_bridge feature flag is explicitly enabled.
	foreach ( array(
		'includes/mcp/class-mcp-tool-registry.php',
		'includes/mcp/class-mcp-ai-copilot-tools.php',
	) as $igp_pro_mcp_file ) {
		if ( file_exists( IGP_PRO_PATH . $igp_pro_mcp_file ) ) {
			require_once IGP_PRO_PATH . $igp_pro_mcp_file;
		}
	}

	if ( is_admin() && file_exists( IGP_PRO_PATH . 'includes/admin/ai-copilot-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/ai-copilot-panel.php';
	}

	if ( is_admin() && file_exists( IGP_PRO_PATH . 'includes/admin/ai-copilot-changesets-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/ai-copilot-changesets-panel.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/admin/content-editor.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/content-editor.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/admin/booking-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/booking-panel.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/admin/payment-settings.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/payment-settings.php';
	}

	if ( file_exists( IGP_PRO_PATH . 'includes/admin/seo-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/seo-panel.php';
	}

	if ( is_admin() && file_exists( IGP_PRO_PATH . 'includes/admin/settings.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/settings.php';
	}

	if ( is_admin() && file_exists( IGP_PRO_PATH . 'includes/admin/diagnostics-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/diagnostics-panel.php';
	}

	if ( is_admin() && file_exists( IGP_PRO_PATH . 'includes/admin/recovery-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/recovery-panel.php';
	}

	if ( is_admin() && function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_relationship_layer' ) && file_exists( IGP_PRO_PATH . 'includes/admin/relationships-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/relationships-panel.php';
	}

	if ( is_admin() && function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_brand_engine' ) && file_exists( IGP_PRO_PATH . 'includes/admin/brand-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/brand-panel.php';
	}

	if ( is_admin() && function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_media_optimizer' ) && file_exists( IGP_PRO_PATH . 'includes/admin/media-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/media-panel.php';
	}

	if ( is_admin() && function_exists( 'igp_feature_enabled' ) && igp_feature_enabled( 'enable_starter_templates' ) && file_exists( IGP_PRO_PATH . 'includes/admin/starter-content-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/starter-content-panel.php';
	}

	if ( is_admin() && function_exists( 'igp_feature_enabled' ) && ( igp_feature_enabled( 'enable_rank_math_bridge' ) || igp_feature_enabled( 'enable_link_whisper_bridge' ) ) && file_exists( IGP_PRO_PATH . 'includes/admin/integrations-panel.php' ) ) {
		require_once IGP_PRO_PATH . 'includes/admin/integrations-panel.php';
	}

	add_action( 'init', 'igp_pro_register_taxonomies', 0 );
	add_action( 'init', 'igp_pro_register_post_types', 1 );
	add_action( 'init', 'igp_pro_register_core_blocks', 9 );
	add_action( 'init', 'igp_pro_register_wordpress_blocks', 10 );
	add_action( 'enqueue_block_editor_assets', 'igp_pro_enqueue_block_editor_assets' );

	if ( function_exists( 'igp_pro_enqueue_frontend_assets' ) ) {
		add_action( 'wp_enqueue_scripts', 'igp_pro_enqueue_frontend_assets' );
	}

	if ( function_exists( 'igp_pro_ensure_content_has_page_h1' ) ) {
		add_filter( 'the_content', 'igp_pro_ensure_content_has_page_h1', 12 );
	}

	if ( function_exists( 'igp_pro_register_lazy_loading_policy' ) ) {
		igp_pro_register_lazy_loading_policy();
	}

	if ( function_exists( 'igp_pro_enqueue_editor_styles' ) ) {
		add_action( 'enqueue_block_editor_assets', 'igp_pro_enqueue_editor_styles', 20 );
	}

	if ( function_exists( 'igp_pro_register_cache_module' ) ) {
		igp_pro_register_cache_module();
	}

	if ( function_exists( 'igp_pro_register_seo_module' ) ) {
		igp_pro_register_seo_module();
	}

	if ( function_exists( 'igp_pro_register_rank_math_bridge' ) ) {
		igp_pro_register_rank_math_bridge();
	}

	if ( function_exists( 'igp_pro_register_link_whisper_bridge' ) ) {
		igp_pro_register_link_whisper_bridge();
	}


	if ( function_exists( 'igp_pro_register_booking_module' ) ) {
		igp_pro_register_booking_module();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_content_editor_admin' ) ) {
		igp_pro_register_content_editor_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_ai_copilot_admin' ) ) {
		igp_pro_register_ai_copilot_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_ai_copilot_changesets_admin' ) ) {
		igp_pro_register_ai_copilot_changesets_admin();
	}

	if ( class_exists( 'IGP_REST_AI_Copilot_Controller' ) ) {
		IGP_REST_AI_Copilot_Controller::register();
	}

	if ( class_exists( 'IGP_MCP_AI_Copilot_Tools' ) ) {
		IGP_MCP_AI_Copilot_Tools::register();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_booking_admin' ) ) {
		igp_pro_register_booking_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_payment_settings_admin' ) ) {
		igp_pro_register_payment_settings_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_seo_admin' ) ) {
		igp_pro_register_seo_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_settings_admin' ) ) {
		igp_pro_register_settings_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_diagnostics_admin' ) ) {
		igp_pro_register_diagnostics_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_recovery_admin' ) ) {
		igp_pro_register_recovery_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_relationship_admin' ) ) {
		igp_pro_register_relationship_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_brand_admin' ) ) {
		igp_pro_register_brand_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_media_admin' ) ) {
		igp_pro_register_media_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_starter_content_admin' ) ) {
		igp_pro_register_starter_content_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_integrations_admin' ) ) {
		igp_pro_register_integrations_admin();
	}

	if ( is_admin() && function_exists( 'igp_pro_register_relationships_panel_admin' ) ) {
		igp_pro_register_relationships_panel_admin();
	}
}
