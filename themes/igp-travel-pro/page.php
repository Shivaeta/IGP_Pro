<?php get_header(); ?>
<?php
$igp_has_graph = is_singular() && function_exists( 'igp_travel_pro_post_has_graph' ) && igp_travel_pro_post_has_graph( get_queried_object_id() );
?>
<?php if ( $igp_has_graph ) : ?>
	<div class="igp-graph-page">
<?php else : ?>
	<section class="igp-page-shell">
<?php endif; ?>
<?php if ( have_posts() ) : ?>
	<?php if ( ! is_singular() ) : ?><header class="igp-archive-header"><h1><?php the_archive_title(); ?></h1><?php the_archive_description( '<div class="igp-archive-description">', '</div>' ); ?></header><div class="igp-archive-grid"><?php endif; ?>
	<?php while ( have_posts() ) : the_post(); ?>
		<?php if ( is_singular() ) : get_template_part( 'template-parts/content' ); else : igp_travel_pro_post_card(); endif; ?>
	<?php endwhile; ?>
	<?php if ( ! is_singular() ) : ?></div><?php the_posts_pagination(); ?><?php endif; ?>
<?php else : ?>
	<article class="igp-empty"><h1><?php esc_html_e( 'No content found', 'igp-travel-pro' ); ?></h1></article>
<?php endif; ?>
<?php if ( $igp_has_graph ) : ?>
	</div>
<?php else : ?>
	</section>
<?php endif; ?>
<?php get_footer(); ?>
