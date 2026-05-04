<?php
/**
 * Token import/export and generated CSS variable layer.
 *
 * @package IGP_Travel_Pro
 */

defined( 'ABSPATH' ) || exit;

function igp_travel_pro_default_tokens(): array {
	return array(
		'colors' => array(
			'accent'      => '#ff5a2a',
			'accent_2'    => '#f97316',
			'ink'         => '#0f172a',
			'ink_2'       => '#1e293b',
			'muted'       => '#64748b',
			'soft'        => '#f6f8fc',
			'soft_2'      => '#edf2f7',
			'panel'       => '#ffffff',
			'page_start'  => '#eef3f9',
			'page_mid'    => '#f7f9fc',
			'page_end'    => '#edf2f7',
			'on_dark'     => '#ffffff',
		),
		'rgb' => array(
			'accent' => '255, 90, 42',
			'ink'    => '15, 23, 42',
			'on_dark'=> '255, 255, 255',
		),
		'typography' => array(
			'font_family' => 'Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
			'line_height' => '1.55',
		),
		'spacing' => array(
			'gap'       => '22px',
			'shell'     => '34px',
			'section_y' => 'clamp(44px, 7vw, 96px)',
		),
		'radius' => array(
			'xl' => '36px',
			'lg' => '28px',
			'md' => '20px',
			'sm' => '14px',
			'pill' => '999px',
		),
		'shadow' => array(
			'elevated' => '0 28px 80px rgba(15, 23, 42, .18)',
			'card'     => '0 14px 40px rgba(15, 23, 42, .11)',
			'soft'     => '0 14px 44px rgba(15, 23, 42, .07)',
		),
		'container' => array(
			'narrow' => '820px',
			'default' => '1120px',
			'wide' => '1280px',
			'full' => '100%',
		),
		'motion' => array(
			'transition' => '160ms ease',
		),
	);
}

function igp_travel_pro_flatten_tokens( array $tokens, string $prefix = '' ): array {
	$out = array();
	foreach ( $tokens as $key => $value ) {
		$key = sanitize_key( str_replace( '_', '-', (string) $key ) );
		$name = '' === $prefix ? $key : $prefix . '-' . $key;
		if ( is_array( $value ) ) {
			$out = array_merge( $out, igp_travel_pro_flatten_tokens( $value, $name ) );
		} else {
			$out[ $name ] = (string) $value;
		}
	}
	return $out;
}

function igp_travel_pro_get_tokens(): array {
	$stored = get_option( 'igp_travel_pro_tokens', array() );
	return igp_travel_pro_merge_tokens( igp_travel_pro_default_tokens(), is_array( $stored ) ? $stored : array() );
}

function igp_travel_pro_merge_tokens( array $defaults, array $incoming ): array {
	foreach ( $defaults as $key => $value ) {
		if ( is_array( $value ) ) {
			$defaults[ $key ] = igp_travel_pro_merge_tokens( $value, isset( $incoming[ $key ] ) && is_array( $incoming[ $key ] ) ? $incoming[ $key ] : array() );
		} elseif ( array_key_exists( $key, $incoming ) && is_scalar( $incoming[ $key ] ) ) {
			$defaults[ $key ] = sanitize_text_field( (string) $incoming[ $key ] );
		}
	}
	return $defaults;
}

function igp_travel_pro_is_safe_color_token( string $value ): bool {
	return (bool) preg_match( '/^(#[0-9a-fA-F]{3,8}|rgb\([0-9%,.\s]+\)|rgba\([0-9%,.\s]+\)|hsl\([0-9%,.\s]+\)|hsla\([0-9%,.\s]+\)|var\(--[a-z0-9-]+\))$/', trim( $value ) );
}

function igp_travel_pro_is_safe_css_token( string $value ): bool {
	$value = trim( $value );
	if ( '' === $value || preg_match( '/[;{}<>]/', $value ) ) {
		return false;
	}
	return (bool) preg_match( '/^[a-zA-Z0-9\s,\.\-_%()#"\/:]+$/', $value );
}

function igp_travel_pro_sanitize_tokens( $tokens ): array {
	$defaults = igp_travel_pro_default_tokens();
	if ( ! is_array( $tokens ) ) {
		return $defaults;
	}
	$merged = igp_travel_pro_merge_tokens( $defaults, $tokens );
	foreach ( $merged as $group => $values ) {
		if ( ! is_array( $values ) || ! isset( $defaults[ $group ] ) || ! is_array( $defaults[ $group ] ) ) {
			continue;
		}
		foreach ( $values as $key => $value ) {
			$value = trim( (string) $value );
			if ( 'colors' === $group ) {
				if ( ! igp_travel_pro_is_safe_color_token( $value ) ) {
					$merged[ $group ][ $key ] = $defaults[ $group ][ $key ];
				}
			} elseif ( ! igp_travel_pro_is_safe_css_token( $value ) ) {
				$merged[ $group ][ $key ] = $defaults[ $group ][ $key ];
			}
		}
	}
	return $merged;
}

function igp_travel_pro_get_token_css(): string {
	$tokens = igp_travel_pro_get_tokens();
	$flat   = igp_travel_pro_flatten_tokens( $tokens );
	$css    = ":root, .igp-travel-pro{\n";
	foreach ( $flat as $name => $value ) {
		$css .= sprintf( "  --igp-%s: %s;\n", esc_html( $name ), esc_html( $value ) );
	}
	$css .= "  --brand: var(--igp-colors-accent);\n";
	$css .= "  --brand2: var(--igp-colors-accent-2);\n";
	$css .= "  --brand-rgb: var(--igp-rgb-accent);\n";
	$css .= "  --ink: var(--igp-colors-ink);\n";
	$css .= "  --ink-rgb: var(--igp-rgb-ink);\n";
	$css .= "  --on-dark-rgb: var(--igp-rgb-on-dark);\n";
	$css .= "  --ink2: var(--igp-colors-ink-2);\n";
	$css .= "  --muted: var(--igp-colors-muted);\n";
	$css .= "  --soft: var(--igp-colors-soft);\n";
	$css .= "  --soft2: var(--igp-colors-soft-2);\n";
	$css .= "  --panel: var(--igp-colors-panel);\n";
	$css .= "  --on-dark: var(--igp-colors-on-dark);\n";
	$css .= "  --line: color-mix(in srgb, var(--igp-colors-ink) 12%, transparent);\n";
	$css .= "  --line2: color-mix(in srgb, var(--igp-colors-ink) 20%, transparent);\n";
	$css .= "  --shadow: var(--igp-shadow-elevated);\n";
	$css .= "  --shadow2: var(--igp-shadow-card);\n";
	$css .= "  --r-xl: var(--igp-radius-xl);\n";
	$css .= "  --r-lg: var(--igp-radius-lg);\n";
	$css .= "  --r-md: var(--igp-radius-md);\n";
	$css .= "  --r-sm: var(--igp-radius-sm);\n";
	$css .= "  --gap: var(--igp-spacing-gap);\n";
	$css .= "  --wide: var(--igp-container-wide);\n";
	$css .= "  --text: var(--igp-typography-font-family);\n";
	$css .= "}\n";
	return $css;
}

add_action( 'admin_menu', 'igp_travel_pro_tokens_menu' );
function igp_travel_pro_tokens_menu(): void {
	add_theme_page(
		__( 'IGP Travel Pro Tokens', 'igp-travel-pro' ),
		__( 'IGP Travel Pro Tokens', 'igp-travel-pro' ),
		'manage_options',
		'igp-travel-pro-tokens',
		'igp_travel_pro_render_token_panel'
	);
}

add_action( 'admin_post_igp_travel_pro_save_tokens', 'igp_travel_pro_handle_save_tokens' );
function igp_travel_pro_handle_save_tokens(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized.', 'igp-travel-pro' ) );
	}
	check_admin_referer( 'igp_travel_pro_save_tokens' );
	$json = isset( $_POST['igp_tokens_json'] ) ? wp_unslash( (string) $_POST['igp_tokens_json'] ) : '';
	$decoded = json_decode( $json, true );
	if ( is_array( $decoded ) ) {
		update_option( 'igp_travel_pro_tokens', igp_travel_pro_sanitize_tokens( $decoded ), false );
		wp_safe_redirect( add_query_arg( array( 'page' => 'igp-travel-pro-tokens', 'updated' => '1' ), admin_url( 'themes.php' ) ) );
		exit;
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'igp-travel-pro-tokens', 'error' => 'invalid_json' ), admin_url( 'themes.php' ) ) );
	exit;
}

add_action( 'admin_post_igp_travel_pro_export_tokens', 'igp_travel_pro_handle_export_tokens' );
function igp_travel_pro_handle_export_tokens(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized.', 'igp-travel-pro' ) );
	}
	check_admin_referer( 'igp_travel_pro_export_tokens' );
	nocache_headers();
	header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
	header( 'Content-Disposition: attachment; filename="igp-travel-pro-tokens.json"' );
	echo wp_json_encode( igp_travel_pro_get_tokens(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	exit;
}

function igp_travel_pro_render_token_panel(): void {
	$tokens = igp_travel_pro_get_tokens();
	?>
	<div class="wrap igp-token-panel">
		<h1><?php esc_html_e( 'IGP Travel Pro Token Panel', 'igp-travel-pro' ); ?></h1>
		<p><?php esc_html_e( 'Import or export the CSS token profile used by the rendering layer. The default profile is electric orange and midnight navy.', 'igp-travel-pro' ); ?></p>
		<?php if ( isset( $_GET['updated'] ) ) : ?><div class="notice notice-success"><p><?php esc_html_e( 'Tokens saved.', 'igp-travel-pro' ); ?></p></div><?php endif; ?>
		<?php if ( isset( $_GET['error'] ) ) : ?><div class="notice notice-error"><p><?php esc_html_e( 'Token JSON was invalid.', 'igp-travel-pro' ); ?></p></div><?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'igp_travel_pro_save_tokens' ); ?>
			<input type="hidden" name="action" value="igp_travel_pro_save_tokens" />
			<p><textarea id="igp-travel-pro-token-json" name="igp_tokens_json" rows="28" style="width:100%;font-family:ui-monospace,Menlo,Consolas,monospace;"><?php echo esc_textarea( wp_json_encode( $tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></textarea></p>
			<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Import / Save Tokens', 'igp-travel-pro' ); ?></button></p>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'igp_travel_pro_export_tokens' ); ?>
			<input type="hidden" name="action" value="igp_travel_pro_export_tokens" />
			<p><button class="button" type="submit"><?php esc_html_e( 'Export Tokens JSON', 'igp-travel-pro' ); ?></button></p>
		</form>
	</div>
	<?php
}
