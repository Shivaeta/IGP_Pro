<?php
/**
 * Singular fallback template.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$has_igp_hero = igp_travel_pro_has_igp_hero( get_post() );
	?>
	<article <?php post_class( 'igp-theme-single' ); ?>>
		<?php if ( ! $has_igp_hero ) : ?>
			<header class="igp-theme-single__header igp-theme-container">
				<?php $post_type_object = get_post_type_object( get_post_type() ); ?>
				<p class="igp-theme-eyebrow"><?php echo esc_html( $post_type_object ? $post_type_object->labels->singular_name : get_post_type() ); ?></p>
				<h1><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?><p><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
			</header>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="igp-theme-single__hero igp-theme-container"><?php the_post_thumbnail( 'igp-travel-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?></figure>
			<?php endif; ?>
		<?php endif; ?>
		<div class="igp-theme-content igp-theme-container--content-safe">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
