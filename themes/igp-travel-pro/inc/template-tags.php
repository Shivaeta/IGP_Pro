<?php
/**
 * Template helpers.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;

function igp_travel_pro_maybe_render_graph( int $post_id ): string {
	if ( ! function_exists( 'igp_pro_load_content_graph' ) ) {
		return '';
	}

	$graph = igp_pro_load_content_graph( $post_id );
	if ( ! is_array( $graph ) || empty( $graph['sections'] ) ) {
		return '';
	}

	if ( function_exists( 'igp_travel_pro_render_exact_graph' ) && apply_filters( 'igp_travel_pro_use_exact_reference_renderer', true, $post_id, $graph ) ) {
		$html = igp_travel_pro_render_exact_graph( $graph, array( 'post_id' => $post_id ) );
		if ( '' !== trim( (string) $html ) ) {
			return (string) $html;
		}
	}

	if ( function_exists( 'igp_pro_render_content_graph' ) ) {
		$html = igp_pro_render_content_graph( $graph, array( 'post_id' => $post_id ) );
		if ( is_wp_error( $html ) ) {
			return '';
		}
		return function_exists( 'igp_travel_pro_apply_reference_clone_classes' ) ? igp_travel_pro_apply_reference_clone_classes( (string) $html ) : (string) $html;
	}

	return '';
}

function igp_travel_pro_post_card(): void {
	?>
	<article <?php post_class( 'igp-archive-card' ); ?>>
		<?php if ( has_post_thumbnail() ) : ?>
			<a class="igp-archive-card__media" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?></a>
		<?php endif; ?>
		<div class="igp-archive-card__body">
			<h2 class="igp-archive-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<div class="igp-archive-card__excerpt"><?php the_excerpt(); ?></div>
			<a class="igp-button" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View Details', 'igp-travel-pro' ); ?></a>
		</div>
	</article>
	<?php
}
