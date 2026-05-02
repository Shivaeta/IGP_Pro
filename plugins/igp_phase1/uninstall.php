<?php
/**
 * Plugin uninstall routine.
 *
 * @package IGP_Pro
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$meta_key = '_igp_pro_content_graph';

$wpdb->delete(
	$wpdb->postmeta,
	array( 'meta_key' => $meta_key ),
	array( '%s' )
);
