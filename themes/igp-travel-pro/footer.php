
</main>
<footer class="igp-site-footer">
	<div class="igp-site-footer__inner">
		<div class="igp-footer-brand">
			<strong><?php bloginfo( 'name' ); ?></strong>
			<p><?php bloginfo( 'description' ); ?></p>
		</div>

		<section class="igp-footer-column" aria-labelledby="igp-footer-plan">
			<h2 id="igp-footer-plan"><?php esc_html_e( 'Plan', 'igp-travel-pro' ); ?></h2>
			<ul>
				<li><a href="#"><?php esc_html_e( 'Private tours', 'igp-travel-pro' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'Group departures', 'igp-travel-pro' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'Custom itinerary', 'igp-travel-pro' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'Travel consultation', 'igp-travel-pro' ); ?></a></li>
			</ul>
		</section>

		<section class="igp-footer-column" aria-labelledby="igp-footer-destinations">
			<h2 id="igp-footer-destinations"><?php esc_html_e( 'Destinations', 'igp-travel-pro' ); ?></h2>
			<ul>
				<li><a href="#"><?php esc_html_e( 'Rajasthan', 'igp-travel-pro' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'Agra', 'igp-travel-pro' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'Varanasi', 'igp-travel-pro' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'Kerala', 'igp-travel-pro' ); ?></a></li>
			</ul>
		</section>

		<section class="igp-footer-column" aria-labelledby="igp-footer-company">
			<h2 id="igp-footer-company"><?php esc_html_e( 'Company', 'igp-travel-pro' ); ?></h2>
			<ul>
				<li><a href="#"><?php esc_html_e( 'About', 'igp-travel-pro' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'Reviews', 'igp-travel-pro' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'Partners', 'igp-travel-pro' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'Contact', 'igp-travel-pro' ); ?></a></li>
			</ul>
		</section>

		<section class="igp-footer-column" aria-labelledby="igp-footer-support">
			<h2 id="igp-footer-support"><?php esc_html_e( 'Support', 'igp-travel-pro' ); ?></h2>
			<ul>
				<li><a href="#"><?php esc_html_e( 'Booking help', 'igp-travel-pro' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'Cancellation policy', 'igp-travel-pro' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'Privacy policy', 'igp-travel-pro' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'Terms of service', 'igp-travel-pro' ); ?></a></li>
			</ul>
		</section>

		<?php if ( is_active_sidebar( 'footer' ) ) : ?><div class="igp-site-footer__widgets"><?php dynamic_sidebar( 'footer' ); ?></div><?php endif; ?>

		<nav class="igp-site-footer__nav" aria-label="<?php esc_attr_e( 'Footer navigation', 'igp-travel-pro' ); ?>">
			<?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ) ); ?>
		</nav>

		<div class="igp-footer-bottom">
			<span><?php echo esc_html( sprintf( __( '© %1$s %2$s. All rights reserved.', 'igp-travel-pro' ), date_i18n( 'Y' ), get_bloginfo( 'name' ) ) ); ?></span>
			<span><?php esc_html_e( 'Powered by IGP Pro structured travel engine.', 'igp-travel-pro' ); ?></span>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
