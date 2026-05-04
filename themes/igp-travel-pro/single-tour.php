<?php
/**
 * Single Tour template for IGP Travel Pro.
 *
 * Owns only the page layout and visual booking form placement. IGP Pro remains
 * the owner of booking logic, Ajax endpoints, checkout, payment, records, and
 * dashboard state.
 *
 * @package IGP_Travel_Pro
 */

get_header();

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		if ( function_exists( 'igp_travel_pro_render_tour_page' ) ) {
			igp_travel_pro_render_tour_page( get_the_ID() );
		} else {
			get_template_part( 'template-parts/content' );
		}
	endwhile;
endif;

get_footer();
