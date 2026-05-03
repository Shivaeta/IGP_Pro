<?php
/**
 * IGP Travel Pro theme functions.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_TRAVEL_PRO_VERSION' ) ) {
	define( 'IGP_TRAVEL_PRO_VERSION', '1.0.0' );
}

/**
 * Theme setup.
 */
function igp_travel_pro_setup(): void {
	load_theme_textdomain( 'igp-travel-pro', get_template_directory() . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 260, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );

	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'igp-travel-pro' ),
			'footer'  => __( 'Footer Menu', 'igp-travel-pro' ),
		)
	);

	add_image_size( 'igp-travel-card', 720, 540, true );
	add_image_size( 'igp-travel-card-wide', 960, 600, true );
	add_image_size( 'igp-travel-hero', 1800, 980, true );
}
add_action( 'after_setup_theme', 'igp_travel_pro_setup' );

/**
 * Enqueue theme assets. No external network assets are used.
 */
function igp_travel_pro_enqueue_assets(): void {
	$css_path = get_template_directory() . '/assets/css/theme.css';
	$js_path  = get_template_directory() . '/assets/js/theme.js';

	wp_enqueue_style(
		'igp-travel-pro',
		get_template_directory_uri() . '/assets/css/theme.css',
		array(),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : IGP_TRAVEL_PRO_VERSION
	);

	if ( file_exists( $js_path ) ) {
		wp_enqueue_script(
			'igp-travel-pro',
			get_template_directory_uri() . '/assets/js/theme.js',
			array(),
			(string) filemtime( $js_path ),
			true
		);

		if ( function_exists( 'wp_script_add_data' ) ) {
			wp_script_add_data( 'igp-travel-pro', 'strategy', 'defer' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'igp_travel_pro_enqueue_assets' );

/**
 * Add useful body classes without hard dependencies on IGP Pro.
 *
 * @param array $classes Body classes.
 * @return array
 */
function igp_travel_pro_body_classes( array $classes ): array {
	$classes[] = 'igp-travel-pro-theme';

	if ( function_exists( 'igp_pro_get_block_registry' ) ) {
		$classes[] = 'igp-pro-active';
	}

	if ( is_singular() ) {
		$post = get_post();
		if ( $post instanceof WP_Post && igp_travel_pro_has_igp_hero( $post ) ) {
			$classes[] = 'igp-has-plugin-hero';
		}
	}

	return $classes;
}
add_filter( 'body_class', 'igp_travel_pro_body_classes' );

/**
 * Customizer options for header contact details.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function igp_travel_pro_customize_register( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'igp_travel_pro_header',
		array(
			'title'       => __( 'IGP Travel Header', 'igp-travel-pro' ),
			'description' => __( 'Lightweight header content for the OTA shell.', 'igp-travel-pro' ),
			'priority'    => 35,
		)
	);

	$settings = array(
		'igp_travel_pro_trust_line' => array(
			'label'   => __( 'Trust line', 'igp-travel-pro' ),
			'default' => __( 'Curated private tours · Verified stays · 24/7 concierge', 'igp-travel-pro' ),
		),
		'igp_travel_pro_phone'      => array(
			'label'   => __( 'Phone label', 'igp-travel-pro' ),
			'default' => '+91 12345 67890',
		),
		'igp_travel_pro_cta_label'  => array(
			'label'   => __( 'Header CTA label', 'igp-travel-pro' ),
			'default' => __( 'Plan a trip', 'igp-travel-pro' ),
		),
		'igp_travel_pro_cta_url'    => array(
			'label'   => __( 'Header CTA URL', 'igp-travel-pro' ),
			'default' => home_url( '/contact/' ),
		),
	);

	foreach ( $settings as $setting => $args ) {
		$wp_customize->add_setting(
			$setting,
			array(
				'default'           => $args['default'],
				'sanitize_callback' => 'igp_travel_pro_sanitize_customizer_value',
			)
		);
		$wp_customize->add_control(
			$setting,
			array(
				'label'   => $args['label'],
				'section' => 'igp_travel_pro_header',
				'type'    => 'text',
			)
		);
	}
}
add_action( 'customize_register', 'igp_travel_pro_customize_register' );

/**
 * Sanitize a customizer string or URL depending on setting name.
 *
 * @param string $value Value.
 * @param WP_Customize_Setting|null $setting Setting.
 * @return string
 */
function igp_travel_pro_sanitize_customizer_value( string $value, $setting = null ): string {
	if ( $setting instanceof WP_Customize_Setting && 'igp_travel_pro_cta_url' === $setting->id ) {
		return esc_url_raw( $value );
	}

	return sanitize_text_field( $value );
}

/**
 * Short excerpts for premium cards.
 *
 * @return int
 */
function igp_travel_pro_excerpt_length(): int {
	return 22;
}
add_filter( 'excerpt_length', 'igp_travel_pro_excerpt_length' );

/**
 * Preserve excerpt ellipsis.
 *
 * @return string
 */
function igp_travel_pro_excerpt_more(): string {
	return '…';
}
add_filter( 'excerpt_more', 'igp_travel_pro_excerpt_more' );

/**
 * Get first non-empty post meta value.
 *
 * @param int    $post_id Post ID.
 * @param array  $keys Meta keys.
 * @param string $default Default.
 * @return string
 */
function igp_travel_pro_meta_first( int $post_id, array $keys, string $default = '' ): string {
	foreach ( $keys as $key ) {
		$value = get_post_meta( $post_id, $key, true );
		if ( '' !== (string) $value ) {
			return is_scalar( $value ) ? (string) $value : $default;
		}
	}

	return $default;
}

/**
 * Check whether the post content includes a given IGP block slug.
 *
 * @param WP_Post $post Post.
 * @param string  $slug WP block slug.
 * @return bool
 */
function igp_travel_pro_has_igp_block( WP_Post $post, string $slug ): bool {
	return has_block( 'igp-pro/' . $slug, $post ) || false !== strpos( (string) $post->post_content, '<!-- wp:igp-pro/' . $slug );
}

/**
 * Detect IGP hero block.
 *
 * @param WP_Post $post Post.
 * @return bool
 */
function igp_travel_pro_has_igp_hero( WP_Post $post ): bool {
	return igp_travel_pro_has_igp_block( $post, 'hero' );
}

/**
 * Detect IGP sticky booking CTA block.
 *
 * @param WP_Post $post Post.
 * @return bool
 */
function igp_travel_pro_has_booking_cta( WP_Post $post ): bool {
	return igp_travel_pro_has_igp_block( $post, 'sticky-booking-cta' );
}

/**
 * Get configured phone label.
 *
 * @return string
 */
function igp_travel_pro_phone_label(): string {
	return (string) get_theme_mod( 'igp_travel_pro_phone', '+91 12345 67890' );
}

/**
 * Build tel URL from the configured label.
 *
 * @return string
 */
function igp_travel_pro_phone_url(): string {
	$phone = preg_replace( '/[^0-9+]/', '', igp_travel_pro_phone_label() );
	return $phone ? 'tel:' . $phone : '';
}

/**
 * Header CTA URL.
 *
 * @return string
 */
function igp_travel_pro_cta_url(): string {
	$url = (string) get_theme_mod( 'igp_travel_pro_cta_url', home_url( '/contact/' ) );
	return '' !== $url ? $url : home_url( '/contact/' );
}

/**
 * Header CTA label.
 *
 * @return string
 */
function igp_travel_pro_cta_label(): string {
	$label = (string) get_theme_mod( 'igp_travel_pro_cta_label', __( 'Plan a trip', 'igp-travel-pro' ) );
	return '' !== $label ? $label : __( 'Plan a trip', 'igp-travel-pro' );
}

/**
 * Trust line.
 *
 * @return string
 */
function igp_travel_pro_trust_line(): string {
	$line = (string) get_theme_mod( 'igp_travel_pro_trust_line', __( 'Curated private tours · Verified stays · 24/7 concierge', 'igp-travel-pro' ) );
	return '' !== $line ? $line : __( 'Curated private tours · Verified stays · 24/7 concierge', 'igp-travel-pro' );
}

/**
 * Resolve primary destination label from IGP relationship service when available.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function igp_travel_pro_primary_destination_label( int $post_id ): string {
	if ( function_exists( 'igp_pro_get_primary_destination' ) ) {
		$destination_id = igp_pro_get_primary_destination( $post_id );
		if ( $destination_id ) {
			$title = get_the_title( $destination_id );
			if ( $title ) {
				return $title;
			}
		}
	}

	return igp_travel_pro_meta_first( $post_id, array( '_igp_destination', 'igp_destination', 'destination' ) );
}

/**
 * Render archive/listing card.
 *
 * @param WP_Post $post Post.
 * @return void
 */
function igp_travel_pro_post_card( WP_Post $post ): void {
	$price       = igp_travel_pro_meta_first( $post->ID, array( '_igp_price', 'igp_price', '_price', 'price' ) );
	$duration    = igp_travel_pro_meta_first( $post->ID, array( '_igp_duration', 'igp_duration', '_duration', 'duration' ) );
	$rating      = igp_travel_pro_meta_first( $post->ID, array( '_igp_rating', 'igp_rating', '_rating', 'rating' ) );
	$destination = igp_travel_pro_primary_destination_label( $post->ID );
	$post_type   = get_post_type( $post );
	?>
	<article <?php post_class( 'igp-theme-card igp-theme-card--' . sanitize_html_class( (string) $post_type ), $post ); ?>>
		<a class="igp-theme-card__media" href="<?php echo esc_url( get_permalink( $post ) ); ?>" aria-label="<?php echo esc_attr( get_the_title( $post ) ); ?>">
			<?php if ( has_post_thumbnail( $post ) ) : ?>
				<?php echo get_the_post_thumbnail( $post, 'igp-travel-card', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
			<?php else : ?>
				<span class="igp-theme-card__placeholder" aria-hidden="true"></span>
			<?php endif; ?>
			<?php if ( '' !== $rating ) : ?><span class="igp-theme-card__rating">★ <?php echo esc_html( $rating ); ?></span><?php endif; ?>
			<?php if ( 'destination' === $post_type ) : ?><span class="igp-theme-card__badge"><?php esc_html_e( 'Destination', 'igp-travel-pro' ); ?></span><?php endif; ?>
		</a>
		<div class="igp-theme-card__body">
			<?php if ( '' !== $destination ) : ?><p class="igp-theme-card__kicker"><?php echo esc_html( $destination ); ?></p><?php endif; ?>
			<h2 class="igp-theme-card__title"><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h2>
			<?php if ( has_excerpt( $post ) ) : ?><p class="igp-theme-card__excerpt"><?php echo esc_html( get_the_excerpt( $post ) ); ?></p><?php endif; ?>
			<div class="igp-theme-card__footer">
				<?php if ( '' !== $duration ) : ?><span><?php echo esc_html( $duration ); ?></span><?php endif; ?>
				<?php if ( '' !== $price ) : ?><strong><?php esc_html_e( 'From', 'igp-travel-pro' ); ?> <?php echo esc_html( $price ); ?></strong><?php endif; ?>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Render a compact booking/contact rail for tour detail pages.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function igp_travel_pro_booking_rail( int $post_id ): void {
	$price    = igp_travel_pro_meta_first( $post_id, array( '_igp_price', 'igp_price', '_price', 'price' ) );
	$duration = igp_travel_pro_meta_first( $post_id, array( '_igp_duration', 'igp_duration', '_duration', 'duration' ) );
	?>
	<aside class="igp-theme-booking-card" aria-label="<?php esc_attr_e( 'Booking summary', 'igp-travel-pro' ); ?>">
		<p class="igp-theme-booking-card__eyebrow"><?php esc_html_e( 'Trip planning desk', 'igp-travel-pro' ); ?></p>
		<?php if ( '' !== $price ) : ?>
			<p class="igp-theme-booking-card__price"><span><?php esc_html_e( 'from', 'igp-travel-pro' ); ?></span> <strong><?php echo esc_html( $price ); ?></strong></p>
		<?php endif; ?>
		<?php if ( '' !== $duration ) : ?><p class="igp-theme-booking-card__meta"><?php echo esc_html( $duration ); ?></p><?php endif; ?>
		<a class="igp-theme-button igp-theme-button--full" href="<?php echo esc_url( igp_travel_pro_cta_url() ); ?>"><?php esc_html_e( 'Request proposal', 'igp-travel-pro' ); ?></a>
		<a class="igp-theme-button igp-theme-button--ghost igp-theme-button--full" href="<?php echo esc_url( get_post_type_archive_link( 'tour' ) ?: home_url( '/' ) ); ?>"><?php esc_html_e( 'View all tours', 'igp-travel-pro' ); ?></a>
		<ul class="igp-theme-booking-card__trust">
			<li><?php esc_html_e( 'No-obligation enquiry', 'igp-travel-pro' ); ?></li>
			<li><?php esc_html_e( 'Verified travel specialists', 'igp-travel-pro' ); ?></li>
			<li><?php esc_html_e( 'Private itinerary support', 'igp-travel-pro' ); ?></li>
		</ul>
	</aside>
	<?php
}
