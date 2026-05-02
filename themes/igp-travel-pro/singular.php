<?php
/**
 * Singular post template.
 *
 * @package IGP_Travel_Pro
 */
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'igp-theme-single igp-theme-container' ); ?>>
		<header class="igp-theme-single__header">
			<p class="igp-theme-eyebrow"><?php echo esc_html( get_post_type() ); ?></p>
			<h1><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?><p><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="igp-theme-single__hero"><?php the_post_thumbnail( 'igp-travel-hero' ); ?></figure>
		<?php endif; ?>
		<div class="igp-theme-single__content">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
