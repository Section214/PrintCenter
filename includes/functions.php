<?php
/**
 * Helper functions
 *
 * @package     PrintCenter\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Fetch an array of available shirt types
 *
 * @since       1.0.0
 * @return      array $shirts The available shirt types
 */
function printcenter_get_shirts() {
	$shirts = array(
		'CAN3001'       => __( 'Canvas 3001 Unisex Tee', 'printcenter' ),
		'CAN3005'       => __( 'Canvas 3005 Mens V-Neck Tee', 'printcenter' ),
		'CAN3200'       => __( 'Canvas 3200 Raglan 3/4 Length Baseball Tee', 'printcenter' ),
		'CAN3480'       => __( 'Canvas 3480 Unisex Tank Top', 'printcenter' ),
		'CAN3501'       => __( 'Canvas 3501 Mens 4.2oz L/S Tee', 'printcenter' ),
		'BEL6004'       => __( 'Bella 6004 Ladies Tee', 'printcenter' ),
		'BEL6035'       => __( 'Bella 6035 Womans Deep V-Neck', 'printcenter' ),
		'DEL99300'      => __( 'Delta 99300 Zipped Hoodie', 'printcenter' ),
		'DEL99200'      => __( 'Delta 99200 Hoodie', 'printcenter' ),
		'DEL99100'      => __( 'Delta 99100 Crew Sweatshirt', 'printcenter' ),
		'AA6322'        => __( 'American Apparel Women\'s Sheer Top', 'printcenter' ),
		'AARSA2329'     => __( 'American Apparel Racerback Tank Top', 'printcenter' ),
	);

	return $shirts;
}