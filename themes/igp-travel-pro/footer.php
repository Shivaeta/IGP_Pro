<?php
/**
 * Theme footer.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;
?>
</main>
<footer class="igp-theme-footer">
	<div class="igp-theme-footer__inner">
		<div class="igp-theme-footer__brand">
			<strong><?php bloginfo( 'name' ); ?></strong>
			<p><?php esc_html_e( 'A high-performance travel commerce layer powered by IGP Pro.', 'igp-travel-pro' ); ?></p>
		</div>
		<nav aria-label="<?php esc_attr_e( 'Footer menu', 'igp-travel-pro' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'fallback_cb'    => false,
					'menu_class'     => 'igp-theme-footer-menu',
					'depth'          => 1,
				)
			);
			?>
		</nav>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
