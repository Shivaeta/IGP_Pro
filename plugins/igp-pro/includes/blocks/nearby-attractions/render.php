<?php
/**
 * Nearby Attractions block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_nearby_attractions' ) ) {
	/**
	 * Render nearby attractions.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_nearby_attractions( array $data ): string {
		$title  = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$intro  = isset( $data['intro'] ) ? trim( igp_pro_to_string( $data['intro'] ) ) : '';
		$source = isset( $data['source'] ) ? sanitize_key( (string) $data['source'] ) : 'manual';
		$items  = igp_pro_normalize_list( $data['items'] ?? array() );
		$limit  = isset( $data['limit'] ) ? max( 1, min( 12, absint( $data['limit'] ) ) ) : 6;

		if ( 'related_destinations' === $source && empty( $items ) && class_exists( 'IGP_Relationships' ) ) {
			$post_id = get_the_ID();
			foreach ( IGP_Relationships::get_related_destinations( $post_id, 'nearby_attractions' ) as $destination_id ) {
				$items[] = array(
					'name'        => get_the_title( $destination_id ),
					'description' => wp_trim_words( wp_strip_all_tags( get_post_field( 'post_excerpt', $destination_id ) ?: get_post_field( 'post_content', $destination_id ) ), 22, '' ),
					'distance'    => '',
					'travel_time' => '',
					'image'       => array( 'url' => get_the_post_thumbnail_url( $destination_id, 'medium' ), 'alt' => get_the_title( $destination_id ) ),
					'link_url'    => get_permalink( $destination_id ),
				);
				if ( count( $items ) >= $limit ) {
					break;
				}
			}
		}

		ob_start();
		?>
		<div class="igp-pro-nearby-attractions">
			<?php if ( '' !== $title ) : ?><h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
			<?php if ( '' !== $intro ) : ?><p class="igp-pro-nearby-attractions__intro"><?php echo esc_html( $intro ); ?></p><?php endif; ?>

			<?php if ( ! empty( $items ) ) : ?>
				<div class="igp-pro-nearby-attractions__grid">
					<?php foreach ( array_slice( $items, 0, $limit ) as $item ) : ?>
						<?php
						if ( ! is_array( $item ) ) {
							continue;
						}
						$name = trim( igp_pro_to_string( $item['name'] ?? '' ) );
						$desc = trim( igp_pro_to_string( $item['description'] ?? '' ) );
						$distance = trim( igp_pro_to_string( $item['distance'] ?? '' ) );
						$time = trim( igp_pro_to_string( $item['travel_time'] ?? '' ) );
						$url = esc_url( igp_pro_to_string( $item['link_url'] ?? '' ) );
						$image_url = function_exists( 'igp_pro_get_image_url' ) ? igp_pro_get_image_url( $item['image'] ?? array() ) : '';
						$image_alt = function_exists( 'igp_pro_get_image_alt' ) ? igp_pro_get_image_alt( $item['image'] ?? array(), $name ) : $name;
						if ( '' === $name && '' === $desc ) {
							continue;
						}
						?>
						<article class="igp-pro-nearby-attraction-card">
							<?php if ( '' !== $image_url ) : ?><img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="lazy" /><?php endif; ?>
							<div>
								<?php if ( '' !== $name ) : ?><h3><?php echo esc_html( $name ); ?></h3><?php endif; ?>
								<?php if ( '' !== $distance || '' !== $time ) : ?><p class="igp-pro-nearby-attraction-card__meta"><?php echo esc_html( trim( $distance . ( '' !== $time ? ' · ' . $time : '' ) ) ); ?></p><?php endif; ?>
								<?php if ( '' !== $desc ) : ?><p><?php echo esc_html( $desc ); ?></p><?php endif; ?>
								<?php if ( '' !== $url ) : ?><a class="igp-pro-text-link" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'View details', 'igp-pro' ); ?></a><?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_nearby_attractions( $resolved_data ?? array() );
