<?php
/**
 * Breadcrumb block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_breadcrumb' ) ) {
	/**
	 * Build automatic breadcrumb items.
	 *
	 * @param bool $show_current Include current item.
	 * @return array
	 */
	function igp_pro_get_auto_breadcrumb_items( bool $show_current = true ): array {
		$items = array(
			array(
				'label' => __( 'Home', 'igp-pro' ),
				'url'   => home_url( '/' ),
			),
		);

		if ( is_singular() ) {
			$post_type = get_post_type();
			if ( 'tour' === $post_type ) {
				$items[] = array( 'label' => __( 'Tours', 'igp-pro' ), 'url' => get_post_type_archive_link( 'tour' ) );
			} elseif ( 'destination' === $post_type ) {
				$items[] = array( 'label' => __( 'Destinations', 'igp-pro' ), 'url' => get_post_type_archive_link( 'destination' ) );
			}

			if ( $show_current ) {
				$items[] = array( 'label' => get_the_title(), 'url' => '' );
			}
		}

		return $items;
	}

	/**
	 * Render breadcrumb block.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_breadcrumb( array $data ): string {
		$source       = igp_pro_enum( $data['source'] ?? 'auto', array( 'auto', 'manual' ), 'auto' );
		$show_current = ! empty( $data['show_current'] );
		$items        = 'manual' === $source ? igp_pro_normalize_list( $data['items'] ?? array() ) : igp_pro_get_auto_breadcrumb_items( $show_current );

		ob_start();
		?>
		<nav class="igp-pro-breadcrumb" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'igp-pro' ); ?>">
			<ol class="igp-pro-breadcrumb__list">
				<?php foreach ( $items as $index => $item ) : ?>
					<?php $label = isset( $item['label'] ) ? trim( igp_pro_to_string( $item['label'] ) ) : ''; ?>
					<?php if ( '' === $label ) { continue; } ?>
					<li class="igp-pro-breadcrumb__item">
						<?php if ( ! empty( $item['url'] ) && $index < count( $items ) - 1 ) : ?>
							<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $label ); ?></a>
						<?php else : ?>
							<span aria-current="page"><?php echo esc_html( $label ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_breadcrumb( $resolved_data ?? array() );
