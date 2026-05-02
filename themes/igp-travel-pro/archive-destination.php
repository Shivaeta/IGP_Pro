<?php
/**
 * Destination archive template.
 *
 * @package IGP_Travel_Pro
 */
get_header();
?>
<section class="igp-theme-archive-hero">
	<div class="igp-theme-container">
		<p class="igp-theme-eyebrow"><?php esc_html_e( 'Destination listing', 'igp-travel-pro' ); ?></p>
		<h1><?php esc_html_e( 'Explore destinations', 'igp-travel-pro' ); ?></h1>
		<p><?php esc_html_e( 'Destination archive layout designed for IGP Pro.', 'igp-travel-pro' ); ?></p>
	</div>
</section>
<section class="igp-theme-container igp-theme-grid-wrap">
	<?php if ( have_posts() ) : ?>
		<div class="igp-theme-grid igp-theme-grid--destinations">
			<?php while ( have_posts() ) : the_post(); ?><?php igp_travel_pro_post_card( get_post() ); ?><?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No destinations found.', 'igp-travel-pro' ); ?></p>
	<?php endif; ?>
</section>
<?php get_footer();
