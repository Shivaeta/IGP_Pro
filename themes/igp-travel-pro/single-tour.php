<?php
/**
 * Single tour template.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$post         = get_post();
	$has_igp_hero = $post instanceof WP_Post ? igp_travel_pro_has_igp_hero( $post ) : false;
	$has_cta      = $post instanceof WP_Post ? igp_travel_pro_has_booking_cta( $post ) : false;
	$duration     = igp_travel_pro_meta_first( get_the_ID(), array( '_igp_duration', 'igp_duration', '_duration', 'duration' ) );
	$group        = igp_travel_pro_meta_first( get_the_ID(), array( '_igp_group_size', 'igp_group_size', '_group_size', 'group_size' ) );
	$rating       = igp_travel_pro_meta_first( get_the_ID(), array( '_igp_rating', 'igp_rating', '_rating', 'rating' ) );
	$destination  = igp_travel_pro_primary_destination_label( get_the_ID() );
	?>
	<article <?php post_class( 'igp-theme-tour' ); ?>>
		<?php if ( ! $has_igp_hero ) : ?>
			<header class="igp-theme-tour-hero">
				<div class="igp-theme-container igp-theme-tour-hero__grid">
					<div class="igp-theme-tour-hero__copy">
						<p class="igp-theme-eyebrow"><?php echo esc_html( $destination ? $destination : __( 'Tour detail', 'igp-travel-pro' ) ); ?></p>
						<h1><?php the_title(); ?></h1>
						<?php if ( has_excerpt() ) : ?><p><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
						<ul class="igp-theme-tour-facts">
							<?php if ( '' !== $rating ) : ?><li>★ <?php echo esc_html( $rating ); ?></li><?php endif; ?>
							<?php if ( '' !== $duration ) : ?><li><?php echo esc_html( $duration ); ?></li><?php endif; ?>
							<?php if ( '' !== $group ) : ?><li><?php echo esc_html( $group ); ?></li><?php endif; ?>
						</ul>
					</div>
					<?php if ( has_post_thumbnail() ) : ?><figure><?php the_post_thumbnail( 'igp-travel-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?></figure><?php endif; ?>
				</div>
			</header>
		<?php endif; ?>
		<div class="igp-theme-container igp-theme-tour-layout<?php echo $has_cta ? ' igp-theme-tour-layout--no-rail' : ''; ?>">
			<div class="igp-theme-tour-content igp-theme-content"><?php the_content(); ?></div>
			<?php if ( ! $has_cta ) : ?>
				<?php igp_travel_pro_booking_rail( get_the_ID() ); ?>
			<?php endif; ?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
