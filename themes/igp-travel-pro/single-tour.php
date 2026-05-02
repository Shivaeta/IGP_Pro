<?php
/**
 * Single Tour template.
 *
 * @package IGP_Travel_Pro
 */
get_header();
while ( have_posts() ) :
	the_post();
	$price    = igp_travel_pro_meta_first( get_the_ID(), array( '_igp_price', 'igp_price', '_price', 'price' ) );
	$duration = igp_travel_pro_meta_first( get_the_ID(), array( '_igp_duration', 'igp_duration', '_duration', 'duration' ) );
	$group    = igp_travel_pro_meta_first( get_the_ID(), array( '_igp_group_size', 'igp_group_size', '_group_size', 'group_size' ) );
	$rating   = igp_travel_pro_meta_first( get_the_ID(), array( '_igp_rating', 'igp_rating', '_rating', 'rating' ) );
	?>
	<article <?php post_class( 'igp-theme-tour' ); ?>>
		<header class="igp-theme-tour-hero">
			<div class="igp-theme-container igp-theme-tour-hero__grid">
				<div>
					<p class="igp-theme-eyebrow"><?php esc_html_e( 'Tour detail', 'igp-travel-pro' ); ?></p>
					<h1><?php the_title(); ?></h1>
					<?php if ( has_excerpt() ) : ?><p><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
					<ul class="igp-theme-tour-facts">
						<?php if ( '' !== $rating ) : ?><li>★ <?php echo esc_html( $rating ); ?></li><?php endif; ?>
						<?php if ( '' !== $duration ) : ?><li><?php echo esc_html( $duration ); ?></li><?php endif; ?>
						<?php if ( '' !== $group ) : ?><li><?php echo esc_html( $group ); ?></li><?php endif; ?>
					</ul>
				</div>
				<?php if ( has_post_thumbnail() ) : ?><figure><?php the_post_thumbnail( 'igp-travel-hero' ); ?></figure><?php endif; ?>
			</div>
		</header>
		<div class="igp-theme-container igp-theme-tour-layout">
			<div class="igp-theme-tour-content"><?php the_content(); ?></div>
			<aside class="igp-theme-booking-card" aria-label="<?php esc_attr_e( 'Booking summary', 'igp-travel-pro' ); ?>">
				<?php if ( '' !== $price ) : ?><p><?php esc_html_e( 'from', 'igp-travel-pro' ); ?> <strong><?php echo esc_html( $price ); ?></strong></p><?php endif; ?>
				<a class="igp-theme-button igp-theme-button--full" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Send enquiry', 'igp-travel-pro' ); ?></a>
				<a class="igp-theme-button igp-theme-button--ghost igp-theme-button--full" href="<?php echo esc_url( get_post_type_archive_link( 'tour' ) ); ?>"><?php esc_html_e( 'View all tours', 'igp-travel-pro' ); ?></a>
			</aside>
		</div>
	</article>
	<?php
endwhile;
get_footer();
