<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="igp-site-header">
	<div class="igp-site-header__inner">
		<a class="igp-site-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php if ( has_custom_logo() ) : the_custom_logo(); else : ?><span><?php bloginfo( 'name' ); ?></span><?php endif; ?>
		</a>
		<nav class="igp-site-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'igp-travel-pro' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'fallback_cb' => false, 'depth' => 2 ) );
			} else {
				?>
				<ul class="menu">
					<li><a href="#"><?php esc_html_e( 'Tours', 'igp-travel-pro' ); ?></a></li>
					<li><a href="#"><?php esc_html_e( 'Destinations', 'igp-travel-pro' ); ?></a></li>
					<li><a href="#"><?php esc_html_e( 'About', 'igp-travel-pro' ); ?></a></li>
					<li><a href="#"><?php esc_html_e( 'Plan a trip', 'igp-travel-pro' ); ?></a></li>
				</ul>
				<?php
			}
			?>
		</nav>
	</div>
</header>
<main id="primary" class="igp-site-main">
