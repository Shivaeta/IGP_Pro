<?php
/**
 * Theme header.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="igp-theme-skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'igp-travel-pro' ); ?></a>
<header class="igp-theme-header" data-igp-theme-header>
	<div class="igp-theme-topbar">
		<div class="igp-theme-topbar__inner">
			<span><?php echo esc_html( igp_travel_pro_trust_line() ); ?></span>
			<?php if ( igp_travel_pro_phone_url() ) : ?>
				<a href="<?php echo esc_url( igp_travel_pro_phone_url() ); ?>"><?php echo esc_html( igp_travel_pro_phone_label() ); ?></a>
			<?php endif; ?>
		</div>
	</div>
	<div class="igp-theme-header__inner">
		<a class="igp-theme-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<span class="igp-theme-logo__mark" aria-hidden="true">IGP</span><span class="igp-theme-logo__text"><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>
		</a>
		<nav class="igp-theme-nav" id="igp-theme-primary-nav" aria-label="<?php esc_attr_e( 'Primary menu', 'igp-travel-pro' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => 'wp_page_menu',
					'menu_class'     => 'igp-theme-menu',
					'depth'          => 2,
				)
			);
			?>
		</nav>
		<div class="igp-theme-header__actions">
			<a class="igp-theme-button igp-theme-button--header" href="<?php echo esc_url( igp_travel_pro_cta_url() ); ?>"><?php echo esc_html( igp_travel_pro_cta_label() ); ?></a>
			<button class="igp-theme-menu-toggle" type="button" aria-expanded="false" aria-controls="igp-theme-primary-nav" data-igp-menu-toggle>
				<span class="screen-reader-text"><?php esc_html_e( 'Toggle menu', 'igp-travel-pro' ); ?></span>
				<span aria-hidden="true"></span><span aria-hidden="true"></span>
			</button>
		</div>
	</div>
</header>
<main id="content" class="igp-theme-main">
