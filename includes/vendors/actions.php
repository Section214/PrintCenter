<?php
/**
 * Vendor actions
 *
 * @package     PrintCenter\Vendor\Actions
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Search vendors
 *
 * @since       1.0.0
 * @global      object $woo_vendors The vendors object
 * @return      void
 */
function printcenter_ajax_search_vendors() {
	global $woo_vendors;

	check_ajax_referer( 'search-vendors', 'security' );

	header( 'Content-Type: application/json; charset=utf-8' );

	$term = urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );

	if( empty( $term ) ) {
		die();
	}

	$found_vendors = array();

	$args = array(
		'hide_empty' => false,
		'search'     => $term
	);

	$vendors = get_terms( $woo_vendors->token, $args );

	if( $vendors ) {
		foreach( $vendors as $vendor ) {
			$found_vendors[$vendor->term_id] = $vendor->name;
		}
	}

	echo json_encode( $found_vendors );
	die();
}
add_action( 'wp_ajax_printcenter_ajax_search_vendors', 'printcenter_ajax_search_vendors' );