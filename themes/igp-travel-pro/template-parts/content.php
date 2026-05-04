<?php
/** Standard page/post content. */
$graph_html = igp_travel_pro_maybe_render_graph( get_the_ID() );
?>
<article <?php post_class( '' !== $graph_html ? 'igp-entry igp-entry--graph' : 'igp-entry' ); ?>>
	<header class="igp-entry__header<?php echo '' !== $graph_html ? ' igp-entry__header--graph' : ''; ?>">
		<?php if ( is_singular() ) : ?><h1 class="igp-entry__title"><?php the_title(); ?></h1><?php else : ?><h2 class="igp-entry__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php endif; ?>
	</header>
	<div class="igp-entry__content">
		<?php
		if ( '' !== $graph_html ) {
			echo $graph_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered by IGP Pro renderer.
		} else {
			the_content();
			wp_link_pages();
		}
		?>
	</div>
</article>
