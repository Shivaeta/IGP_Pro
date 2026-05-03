<?php
/**
 * Tour archive template.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="igp-theme-archive-hero igp-theme-archive-hero--tour">
	<div class="igp-theme-container igp-theme-archive-hero__grid">
		<div>
			<p class="igp-theme-eyebrow"><?php esc_html_e( 'Tour marketplace', 'igp-travel-pro' ); ?></p>
			<h1><?php esc_html_e( 'Find your next journey', 'igp-travel-pro' ); ?></h1>
			<p><?php esc_html_e( 'Structured, high-intent travel packages rendered through the IGP Pro content engine.', 'igp-travel-pro' ); ?></p>
		</div>
		<form class="igp-theme-archive-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="igp-tour-search"><?php esc_html_e( 'Search tours', 'igp-travel-pro' ); ?></label>
			<input id="igp-tour-search" type="search" name="s" placeholder="<?php esc_attr_e( 'Search tours, destinations, themes…', 'igp-travel-pro' ); ?>">
			<input type="hidden" name="post_type" value="tour">
			<button type="submit"><?php esc_html_e( 'Search', 'igp-travel-pro' ); ?></button>
		</form>
	</div>
</section>
<section class="igp-theme-container igp-theme-grid-wrap">
	<?php if ( have_posts() ) : ?>
		<div class="igp-theme-grid">
			<?php while ( have_posts() ) : the_post(); ?><?php igp_travel_pro_post_card( get_post() ); ?><?php endwhile; ?>
		</div>
		<?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
	<?php else : ?>
		<p class="igp-theme-empty"><?php esc_html_e( 'No tours found.', 'igp-travel-pro' ); ?></p>
	<?php endif; ?>
</section>
<?php get_footer();
