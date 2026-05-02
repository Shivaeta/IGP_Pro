<?php
/**
 * Theme header.
 *
 * @package IGP_Travel_Pro
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="igp-theme-header" data-igp-theme-header>
	<div class="igp-theme-header__inner">
		<a class="igp-theme-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<span><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>
		</a>
		<nav class="igp-theme-nav" aria-label="<?php esc_attr_e( 'Primary menu', 'igp-travel-pro' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => false,
					'menu_class'     => 'igp-theme-menu',
					'depth'          => 2,
				)
			);
			?>
		</nav>
		<div class="igp-theme-header__actions">
			<a class="igp-theme-header__phone" href="tel:+911234567890"><?php esc_html_e( '+91 12345 67890', 'igp-travel-pro' ); ?></a>
			<a class="igp-theme-button" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Plan a trip', 'igp-travel-pro' ); ?></a>
			<button class="igp-theme-menu-toggle" type="button" aria-expanded="false" data-igp-menu-toggle><span></span><span></span></button>
		</div>
	</div>
</header>
<main id="content" class="igp-theme-main">
