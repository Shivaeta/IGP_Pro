<?php
/**
 * Page template.
 *
 * @package IGP_Travel_Pro
 */
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'igp-theme-page' ); ?>>
		<?php if ( ! has_block( 'igp-pro/hero', get_post() ) ) : ?>
			<header class="igp-theme-page-header igp-theme-container">
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
