<?php
/**
 * Theme footer.
 *
 * @package IGP_Travel_Pro
 */
?>
</main>
<footer class="igp-theme-footer">
	<div class="igp-theme-footer__inner">
		<div>
			<strong><?php bloginfo( 'name' ); ?></strong>
			<p><?php esc_html_e( 'A fast travel site foundation powered by IGP Pro.', 'igp-travel-pro' ); ?></p>
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
