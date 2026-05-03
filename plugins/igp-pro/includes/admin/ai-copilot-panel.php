<?php
/**
 * AI Copilot admin panel.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

function igp_pro_register_ai_copilot_admin(): void {
	add_action( 'admin_menu', 'igp_pro_register_ai_copilot_menu' );
	add_action( 'admin_enqueue_scripts', 'igp_pro_enqueue_ai_copilot_assets' );
	foreach ( array( 'parse', 'validate', 'compile', 'preview', 'create_draft', 'create_changeset' ) as $action ) {
		add_action( 'wp_ajax_igp_pro_ai_copilot_' . $action, 'igp_pro_ajax_ai_copilot_' . $action );
	}
}

function igp_pro_register_ai_copilot_menu(): void {
	add_submenu_page(
		'igp-pro-content-editor',
		__( 'IGP AI Copilot', 'igp-pro' ),
		__( 'AI Copilot', 'igp-pro' ),
		function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'ai_copilot' ) : 'edit_posts',
		'igp-pro-ai-copilot',
		'igp_pro_render_ai_copilot_page'
	);
}

function igp_pro_render_ai_copilot_page(): void {
	if ( ! current_user_can( function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'ai_copilot' ) : 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to use IGP AI Copilot.', 'igp-pro' ) );
	}
	$example = function_exists( 'igp_ai_copilot_get_valid_yaml_example' ) ? igp_ai_copilot_get_valid_yaml_example() : '';
	?>
	<div class="wrap igp-pro-admin-wrap igp-ai-copilot-wrap">
		<h1><?php esc_html_e( 'IGP AI Copilot', 'igp-pro' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Paste AI-generated YAML. IGP Pro will parse, validate, compile, preview, and save draft content through controlled services only. No direct publish is available in Phase 14.A.', 'igp-pro' ); ?></p>
		<div class="igp-ai-copilot-grid">
			<section class="igp-pro-admin-card igp-ai-copilot-input-card">
				<h2><?php esc_html_e( 'YAML Draft', 'igp-pro' ); ?></h2>
				<textarea id="igp-ai-copilot-yaml" rows="24" spellcheck="false"><?php echo esc_textarea( $example ); ?></textarea>
				<div class="igp-ai-copilot-actions">
					<button type="button" class="button" data-igp-ai-action="parse"><?php esc_html_e( 'Parse', 'igp-pro' ); ?></button>
					<button type="button" class="button" data-igp-ai-action="validate"><?php esc_html_e( 'Validate', 'igp-pro' ); ?></button>
					<button type="button" class="button" data-igp-ai-action="compile"><?php esc_html_e( 'Compile', 'igp-pro' ); ?></button>
					<button type="button" class="button button-primary" data-igp-ai-action="preview"><?php esc_html_e( 'Preview', 'igp-pro' ); ?></button>
					<button type="button" class="button" data-igp-ai-action="create_changeset"><?php esc_html_e( 'Create Changeset', 'igp-pro' ); ?></button>
					<button type="button" class="button" data-igp-ai-action="create_draft"><?php esc_html_e( 'Save as Draft', 'igp-pro' ); ?></button>
				</div>
				<p class="description"><?php esc_html_e( 'Save as Draft is blocked unless parse, validation, compiler, and Content Graph checks pass.', 'igp-pro' ); ?></p>
			</section>
			<section class="igp-pro-admin-card igp-ai-copilot-results-card">
				<h2><?php esc_html_e( 'Results', 'igp-pro' ); ?></h2>
				<div id="igp-ai-copilot-status" class="notice inline hidden"></div>
				<div class="igp-ai-copilot-panels">
					<?php foreach ( array( 'validation' => __( 'Validation', 'igp-pro' ), 'mapping' => __( 'Mapping Report', 'igp-pro' ), 'warnings' => __( 'Warnings / Errors', 'igp-pro' ), 'media' => __( 'Media Requirements', 'igp-pro' ), 'seo' => __( 'SEO Summary', 'igp-pro' ), 'graph' => __( 'Compiled Content Graph', 'igp-pro' ), 'preview' => __( 'Preview', 'igp-pro' ) ) as $key => $label ) : ?>
						<div class="igp-ai-copilot-panel">
							<h3><?php echo esc_html( $label ); ?></h3>
							<div id="igp-ai-copilot-<?php echo esc_attr( $key ); ?>" class="igp-ai-copilot-panel-body"><p><?php esc_html_e( 'No data yet.', 'igp-pro' ); ?></p></div>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
	</div>
	<?php
}

function igp_pro_enqueue_ai_copilot_assets( string $hook ): void {
	if ( 'igp-pro_page_igp-pro-ai-copilot' !== $hook ) { return; }
	$css = 'assets/css/ai-copilot-panel.css';
	$js = 'assets/js/ai-copilot-panel.js';
	if ( file_exists( igp_pro_path( $css ) ) ) { wp_enqueue_style( 'igp-pro-ai-copilot', igp_pro_url( $css ), array(), igp_pro_asset_version( $css ) ); }
	if ( file_exists( igp_pro_path( $js ) ) ) {
		wp_enqueue_script( 'igp-pro-ai-copilot', igp_pro_url( $js ), array( 'jquery' ), igp_pro_asset_version( $js ), true );
		wp_localize_script( 'igp-pro-ai-copilot', 'igpProAiCopilot', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'igp_pro_ai_copilot' ), 'i18n' => array( 'working' => __( 'Working…', 'igp-pro' ), 'error' => __( 'Request failed.', 'igp-pro' ) ) ) );
	}
}

function igp_pro_ai_copilot_permission_check(): bool|WP_Error {
	$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'igp_pro_ai_copilot' ) ) { return new WP_Error( 'igp_ai_invalid_nonce', __( 'Security check failed.', 'igp-pro' ) ); }
	if ( ! current_user_can( function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'ai_copilot' ) : 'edit_posts' ) ) { return new WP_Error( 'igp_ai_missing_capability', __( 'You do not have permission to use IGP AI Copilot.', 'igp-pro' ) ); }
	return true;
}

function igp_pro_ai_copilot_request_yaml(): string { return isset( $_POST['yaml'] ) ? (string) wp_unslash( $_POST['yaml'] ) : ''; }
function igp_pro_ai_copilot_send_error( WP_Error $error ): void { wp_send_json_error( array( 'code' => $error->get_error_code(), 'message' => $error->get_error_message(), 'data' => $error->get_error_data() ), 400 ); }
function igp_pro_ai_copilot_check_or_die(): void { $permission = igp_pro_ai_copilot_permission_check(); if ( is_wp_error( $permission ) ) { igp_pro_ai_copilot_send_error( $permission ); } }

function igp_pro_ajax_ai_copilot_parse(): void { igp_pro_ai_copilot_check_or_die(); $result = IGP_AI_Copilot_Service::parse_yaml( igp_pro_ai_copilot_request_yaml() ); if ( is_wp_error( $result ) ) { igp_pro_ai_copilot_send_error( $result ); } wp_send_json_success( $result ); }
function igp_pro_ajax_ai_copilot_validate(): void { igp_pro_ai_copilot_check_or_die(); $result = IGP_AI_Copilot_Service::validate_yaml( igp_pro_ai_copilot_request_yaml() ); if ( is_wp_error( $result ) ) { igp_pro_ai_copilot_send_error( $result ); } wp_send_json_success( $result ); }
function igp_pro_ajax_ai_copilot_compile(): void { igp_pro_ai_copilot_check_or_die(); $result = IGP_AI_Copilot_Service::compile_yaml( igp_pro_ai_copilot_request_yaml() ); if ( is_wp_error( $result ) ) { igp_pro_ai_copilot_send_error( $result ); } wp_send_json_success( $result ); }
function igp_pro_ajax_ai_copilot_preview(): void { igp_pro_ai_copilot_check_or_die(); $preview = IGP_AI_Copilot_Service::preview_yaml( igp_pro_ai_copilot_request_yaml() ); if ( is_wp_error( $preview ) ) { igp_pro_ai_copilot_send_error( $preview ); } $compiled = IGP_AI_Copilot_Service::compile_yaml( igp_pro_ai_copilot_request_yaml() ); wp_send_json_success( array( 'preview' => $preview, 'compiled' => is_wp_error( $compiled ) ? null : $compiled ) ); }
function igp_pro_ajax_ai_copilot_create_draft(): void { igp_pro_ai_copilot_check_or_die(); $result = IGP_AI_Copilot_Service::create_draft_from_yaml( igp_pro_ai_copilot_request_yaml() ); if ( is_wp_error( $result ) ) { igp_pro_ai_copilot_send_error( $result ); } wp_send_json_success( $result ); }
function igp_pro_ajax_ai_copilot_create_changeset(): void { igp_pro_ai_copilot_check_or_die(); $context = array( 'source' => 'admin_ai_copilot', 'actor_type' => 'human' ); $result = IGP_AI_Copilot_Service::create_changeset_from_yaml( igp_pro_ai_copilot_request_yaml(), $context ); if ( is_wp_error( $result ) ) { igp_pro_ai_copilot_send_error( $result ); } wp_send_json_success( $result ); }
