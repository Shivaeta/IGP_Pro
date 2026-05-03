<?php
/**
 * AI Copilot preview service.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

class IGP_AI_Copilot_Preview {
	/** Render compiled result through central renderer with review panels. */
	public static function render_preview( array $compiled_result ): string|WP_Error {
		if ( empty( $compiled_result['content_graph'] ) || ! is_array( $compiled_result['content_graph'] ) ) {
			return new WP_Error( 'igp_ai_preview_missing_graph', __( 'Compiled Content Graph is missing.', 'igp-pro' ) );
		}
		$validation = function_exists( 'igp_pro_validate_content_graph' ) ? igp_pro_validate_content_graph( $compiled_result['content_graph'] ) : ( function_exists( 'igp_pro_validate_content_graph_payload' ) ? igp_pro_validate_content_graph_payload( $compiled_result['content_graph'] ) : true );
		if ( is_wp_error( $validation ) ) { return $validation; }
		if ( ! function_exists( 'igp_pro_render_content_graph' ) ) {
			return new WP_Error( 'igp_ai_renderer_missing', __( 'Central Content Graph renderer is unavailable.', 'igp-pro' ) );
		}

		$html = igp_pro_render_content_graph( $compiled_result['content_graph'], array( 'preview' => true, 'source' => 'ai_copilot' ) );
		return self::report_html( $compiled_result ) . '<div class="igp-ai-copilot-rendered-preview">' . $html . '</div>';
	}

	private static function report_html( array $compiled ): string {
		ob_start();
		?>
		<div class="igp-ai-copilot-preview-report" role="region" aria-label="<?php esc_attr_e( 'AI Copilot preview report', 'igp-pro' ); ?>">
			<h2><?php esc_html_e( 'AI Copilot Preview Report', 'igp-pro' ); ?></h2>
			<p><strong><?php esc_html_e( 'Confidence:', 'igp-pro' ); ?></strong> <?php echo esc_html( (string) ( $compiled['confidence'] ?? 0 ) ); ?></p>
			<?php self::render_list_panel( __( 'Warnings', 'igp-pro' ), $compiled['warnings'] ?? array() ); ?>
			<?php self::render_list_panel( __( 'Mapping Report', 'igp-pro' ), $compiled['mapping_report'] ?? array() ); ?>
			<?php self::render_list_panel( __( 'Media Requirements', 'igp-pro' ), $compiled['media_requirements'] ?? array() ); ?>
			<?php self::render_list_panel( __( 'SEO Summary', 'igp-pro' ), $compiled['seo'] ?? array() ); ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_list_panel( string $title, $items ): void {
		?>
		<div class="igp-ai-copilot-report-panel">
			<h3><?php echo esc_html( $title ); ?></h3>
			<?php if ( empty( $items ) ) : ?>
				<p><?php esc_html_e( 'None.', 'igp-pro' ); ?></p>
			<?php else : ?>
				<pre><?php echo esc_html( wp_json_encode( $items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre>
			<?php endif; ?>
		</div>
		<?php
	}
}
