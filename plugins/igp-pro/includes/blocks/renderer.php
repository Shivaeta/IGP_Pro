<?php
/**
 * Central block renderer.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a registered IGP Pro block through one controller.
 *
 * @param string $block_id Block ID.
 * @param array  $data     Block data.
 * @param array  $context  Render context.
 * @return string
 */
function igp_pro_render_block( string $block_id, array $data = array(), array $context = array() ): string {
	$render_callback = static function () use ( $block_id, $data, $context ): string {
		$block = igp_pro_get_registered_block( $block_id );

		if ( ! $block ) {
			return igp_pro_render_block_fallback( $block_id, 'missing_block' );
		}

		$render_path = isset( $block['render_path'] ) ? (string) $block['render_path'] : '';

		if ( '' === $render_path || ! file_exists( $render_path ) ) {
			return igp_pro_render_block_fallback( $block_id, 'missing_render_path' );
		}

		$resolved_data = igp_pro_resolve_block_data( $block, $data, $context );

		if ( function_exists( 'igp_pro_migrate_block_heading_data_for_render' ) ) {
			$resolved_data = igp_pro_migrate_block_heading_data_for_render( $block_id, $resolved_data );
		}

		if ( function_exists( 'igp_pro_migrate_block_style_data_for_render' ) ) {
			$resolved_data = igp_pro_migrate_block_style_data_for_render( $block_id, $resolved_data );
		}

		$validation = igp_pro_validate_block_data( $block, $resolved_data );

		if ( is_wp_error( $validation ) ) {
			return igp_pro_render_block_fallback( $block_id, $validation->get_error_code() );
		}

		if ( function_exists( 'igp_pro_prepare_semantic_block_context' ) ) {
			$context = igp_pro_prepare_semantic_block_context( $block_id, $resolved_data, $context );
		}

		$semantic_data = $resolved_data;
		if ( function_exists( 'igp_pro_prepare_legacy_heading_render_data' ) ) {
			$resolved_data = igp_pro_prepare_legacy_heading_render_data( $block_id, $resolved_data );
		}

		ob_start();
		$result = include $render_path;
		$output = ob_get_clean();

		if ( is_string( $result ) && '' !== $result ) {
			$output .= $result;
		}

		if ( '' === trim( $output ) ) {
			return igp_pro_render_block_fallback( $block_id, 'empty_output' );
		}

		if ( function_exists( 'igp_pro_apply_semantic_block_wrapper' ) ) {
			$output = igp_pro_apply_semantic_block_wrapper( $block_id, $output, $semantic_data, $context );
		}

		return $output;
	};

	if ( function_exists( 'igp_pro_cache_block_render' ) ) {
		return igp_pro_cache_block_render( $block_id, $data, $context, $render_callback );
	}

	return $render_callback();
}

/**
 * Render a safe fallback for missing or broken blocks.
 *
 * @param string $block_id Block ID.
 * @param string $reason   Failure reason.
 * @return string
 */
function igp_pro_render_block_fallback( string $block_id, string $reason ): string {
	return sprintf(
		'<!-- IGP Pro block fallback: %1$s (%2$s) --><div class="igp-pro-block igp-pro-block--fallback" data-igp-block="%1$s" data-igp-reason="%2$s" hidden></div>',
		esc_attr( sanitize_key( $block_id ) ),
		esc_attr( sanitize_key( $reason ) )
	);
}
