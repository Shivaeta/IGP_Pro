<?php
/**
 * Visa / Travel Requirements block render callback.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'igp_pro_render_visa_requirements' ) ) {
	/**
	 * Render visa/travel requirements.
	 *
	 * @param array $data Resolved block data.
	 * @return string
	 */
	function igp_pro_render_visa_requirements( array $data ): string {
		$title        = isset( $data['title'] ) ? trim( igp_pro_to_string( $data['title'] ) ) : '';
		$intro        = isset( $data['intro'] ) ? trim( igp_pro_to_string( $data['intro'] ) ) : '';
		$requirements = igp_pro_normalize_list( $data['requirements'] ?? array() );
		$documents    = igp_pro_normalize_list( $data['documents'] ?? array() );
		$disclaimer   = isset( $data['disclaimer'] ) ? trim( igp_pro_to_string( $data['disclaimer'] ) ) : '';
		$updated      = isset( $data['last_updated'] ) ? trim( igp_pro_to_string( $data['last_updated'] ) ) : '';

		ob_start();
		?>
		<div class="igp-pro-visa-requirements">
			<?php if ( '' !== $title ) : ?>
				<h2 class="igp-pro-block-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== $intro ) : ?>
				<p class="igp-pro-visa-requirements__intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $requirements ) ) : ?>
				<div class="igp-pro-visa-requirements__list" role="list">
					<?php foreach ( $requirements as $item ) : ?>
						<?php
						if ( ! is_array( $item ) ) {
							continue;
						}
						$item_title = trim( igp_pro_to_string( $item['title'] ?? '' ) );
						$desc       = trim( igp_pro_to_string( $item['description'] ?? '' ) );
						$applies    = trim( igp_pro_to_string( $item['applies_to'] ?? '' ) );
						$link_label = trim( igp_pro_to_string( $item['link_label'] ?? '' ) );
						$link_url   = esc_url( igp_pro_to_string( $item['link_url'] ?? '' ) );
						$is_required = ! empty( $item['required'] );
						if ( '' === $item_title && '' === $desc ) {
							continue;
						}
						?>
						<article class="igp-pro-visa-requirements__item" role="listitem">
							<header>
								<?php if ( '' !== $item_title ) : ?>
									<h3><?php echo esc_html( $item_title ); ?></h3>
								<?php endif; ?>
								<span class="igp-pro-visa-requirements__status"><?php echo esc_html( $is_required ? __( 'Required', 'igp-pro' ) : __( 'Recommended', 'igp-pro' ) ); ?></span>
							</header>
							<?php if ( '' !== $applies ) : ?>
								<p class="igp-pro-visa-requirements__applies"><?php echo esc_html( $applies ); ?></p>
							<?php endif; ?>
							<?php if ( '' !== $desc ) : ?>
								<p><?php echo esc_html( $desc ); ?></p>
							<?php endif; ?>
							<?php if ( '' !== $link_url && '' !== $link_label ) : ?>
								<a class="igp-pro-text-link" href="<?php echo esc_url( $link_url ); ?>" rel="nofollow noopener"><?php echo esc_html( $link_label ); ?></a>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $documents ) ) : ?>
				<div class="igp-pro-visa-requirements__documents">
					<h3><?php esc_html_e( 'Common documents', 'igp-pro' ); ?></h3>
					<ul>
						<?php foreach ( $documents as $document ) : ?>
							<?php
							if ( ! is_array( $document ) ) {
								continue;
							}
							$item = trim( igp_pro_to_string( $document['item'] ?? '' ) );
							$note = trim( igp_pro_to_string( $document['note'] ?? '' ) );
							if ( '' === $item ) {
								continue;
							}
							?>
							<li><strong><?php echo esc_html( $item ); ?></strong><?php echo '' !== $note ? ' — ' . esc_html( $note ) : ''; ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $disclaimer || '' !== $updated ) : ?>
				<footer class="igp-pro-visa-requirements__note">
					<?php if ( '' !== $disclaimer ) : ?><p><?php echo esc_html( $disclaimer ); ?></p><?php endif; ?>
					<?php if ( '' !== $updated ) : ?><p><?php echo esc_html( sprintf( __( 'Last updated: %s', 'igp-pro' ), $updated ) ); ?></p><?php endif; ?>
				</footer>
			<?php endif; ?>
		</div>
		<?php
		return trim( ob_get_clean() );
	}
}

return igp_pro_render_visa_requirements( $resolved_data ?? array() );
