<?php
/**
 * Search template.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="igp-theme-archive-hero">
	<div class="igp-theme-container">
		<p class="igp-theme-eyebrow"><?php esc_html_e( 'Search', 'igp-travel-pro' ); ?></p>
		<h1><?php printf( esc_html__( 'Results for “%s”', 'igp-travel-pro' ), esc_html( get_search_query() ) ); ?></h1>
	</div>
</section>
<section class="igp-theme-container igp-theme-grid-wrap">
	<?php if ( have_posts() ) : ?>
		<div class="igp-theme-grid">
			<?php while ( have_posts() ) : the_post(); ?><?php igp_travel_pro_post_card( get_post() ); ?><?php endwhile; ?>
		</div>
		<?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
	<?php else : ?>
		<p class="igp-theme-empty"><?php esc_html_e( 'No matching content found.', 'igp-travel-pro' ); ?></p>
	<?php endif; ?>
</section>
<?php get_footer();
