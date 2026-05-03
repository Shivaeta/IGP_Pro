<?php
/**
 * Page template.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$has_igp_hero = igp_travel_pro_has_igp_hero( get_post() );
	?>
	<article <?php post_class( 'igp-theme-page' ); ?>>
		<?php if ( ! $has_igp_hero ) : ?>
			<header class="igp-theme-page-header igp-theme-container">
				<p class="igp-theme-eyebrow"><?php esc_html_e( 'Travel planning', 'igp-travel-pro' ); ?></p>
				<h1><?php the_title(); ?></h1>
			</header>
		<?php endif; ?>
		<div class="igp-theme-content">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
