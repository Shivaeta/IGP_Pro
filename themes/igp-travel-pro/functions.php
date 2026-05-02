<?php
/**
 * IGP Travel Pro functions.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;

function igp_travel_pro_setup(): void {
	load_theme_textdomain( 'igp-travel-pro', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/theme.css' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'igp-travel-pro' ),
			'footer'  => __( 'Footer Menu', 'igp-travel-pro' ),
		)
	);

	add_image_size( 'igp-travel-card', 720, 520, true );
	add_image_size( 'igp-travel-hero', 1600, 800, true );
}
add_action( 'after_setup_theme', 'igp_travel_pro_setup' );

function igp_travel_pro_enqueue_assets(): void {
	$css = get_template_directory() . '/assets/css/theme.css';
	$js  = get_template_directory() . '/assets/js/theme.js';

	wp_enqueue_style( 'igp-travel-pro', get_template_directory_uri() . '/assets/css/theme.css', array(), file_exists( $css ) ? (string) filemtime( $css ) : wp_get_theme()->get( 'Version' ) );

	if ( file_exists( $js ) ) {
		wp_enqueue_script( 'igp-travel-pro', get_template_directory_uri() . '/assets/js/theme.js', array(), (string) filemtime( $js ), true );
		if ( function_exists( 'wp_script_add_data' ) ) {
			wp_script_add_data( 'igp-travel-pro', 'strategy', 'defer' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'igp_travel_pro_enqueue_assets' );

function igp_travel_pro_excerpt_length(): int {
	return 22;
}
add_filter( 'excerpt_length', 'igp_travel_pro_excerpt_length' );

function igp_travel_pro_meta_first( int $post_id, array $keys, string $default = '' ): string {
	foreach ( $keys as $key ) {
		$value = get_post_meta( $post_id, $key, true );
		if ( '' !== (string) $value ) {
			return (string) $value;
		}
	}

	return $default;
}

function igp_travel_pro_post_card( WP_Post $post ): void {
	$price    = igp_travel_pro_meta_first( $post->ID, array( '_igp_price', 'igp_price', '_price', 'price' ) );
	$duration = igp_travel_pro_meta_first( $post->ID, array( '_igp_duration', 'igp_duration', '_duration', 'duration' ) );
	$rating   = igp_travel_pro_meta_first( $post->ID, array( '_igp_rating', 'igp_rating', '_rating', 'rating' ) );
	?>
	<article <?php post_class( 'igp-theme-card' ); ?>>
		<a class="igp-theme-card__media" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
			<?php if ( has_post_thumbnail( $post ) ) : ?>
				<?php echo get_the_post_thumbnail( $post, 'igp-travel-card', array( 'loading' => 'lazy' ) ); ?>
			<?php else : ?>
				<span class="igp-theme-card__placeholder" aria-hidden="true"></span>
			<?php endif; ?>
			<?php if ( '' !== $rating ) : ?><span class="igp-theme-card__rating">★ <?php echo esc_html( $rating ); ?></span><?php endif; ?>
		</a>
		<div class="igp-theme-card__body">
			<h2 class="igp-theme-card__title"><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h2>
			<?php if ( has_excerpt( $post ) ) : ?><p><?php echo esc_html( get_the_excerpt( $post ) ); ?></p><?php endif; ?>
			<div class="igp-theme-card__footer">
				<?php if ( '' !== $duration ) : ?><span><?php echo esc_html( $duration ); ?></span><?php endif; ?>
				<?php if ( '' !== $price ) : ?><strong><?php esc_html_e( 'From', 'igp-travel-pro' ); ?> <?php echo esc_html( $price ); ?></strong><?php endif; ?>
			</div>
		</div>
	</article>
	<?php
}
