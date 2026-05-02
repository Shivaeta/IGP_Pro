<?php
get_header();
?>
<section class="igp-theme-container igp-theme-404">
	<p class="igp-theme-eyebrow"><?php esc_html_e( '404', 'igp-travel-pro' ); ?></p>
	<h1><?php esc_html_e( 'Page not found', 'igp-travel-pro' ); ?></h1>
	<p><?php esc_html_e( 'The requested page could not be found.', 'igp-travel-pro' ); ?></p>
	<a class="igp-theme-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Return home', 'igp-travel-pro' ); ?></a>
</section>
<?php get_footer();
