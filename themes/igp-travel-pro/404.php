<?php
/**
 * 404 template.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="igp-theme-container igp-theme-404">
	<p class="igp-theme-eyebrow"><?php esc_html_e( '404', 'igp-travel-pro' ); ?></p>
	<h1><?php esc_html_e( 'Page not found', 'igp-travel-pro' ); ?></h1>
	<p><?php esc_html_e( 'The requested page could not be found. Return home or search for a destination.', 'igp-travel-pro' ); ?></p>
	<form class="igp-theme-archive-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="igp-404-search"><?php esc_html_e( 'Search site', 'igp-travel-pro' ); ?></label>
		<input id="igp-404-search" type="search" name="s" placeholder="<?php esc_attr_e( 'Search tours or destinations…', 'igp-travel-pro' ); ?>">
		<button type="submit"><?php esc_html_e( 'Search', 'igp-travel-pro' ); ?></button>
	</form>
	<a class="igp-theme-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Return home', 'igp-travel-pro' ); ?></a>
</section>
<?php get_footer();
