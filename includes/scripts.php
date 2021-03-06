<?php
/**
 * Scripts
 *
 * @package     PrintCenter\Scripts
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Load admin scripts
 *
 * @since       1.0.0
 * @param       string $hook The page hook
 * @return      void
 */
function printcenter_admin_scripts( $hook ) {
	wp_enqueue_style( 'printcenter-font', PRINTCENTER_URL . 'assets/css/font.css', array(), PRINTCENTER_VER );
	wp_enqueue_style( 'printcenter', PRINTCENTER_URL . 'assets/css/admin.css', array(), PRINTCENTER_VER );
}
add_action( 'admin_enqueue_scripts', 'printcenter_admin_scripts', 100 );
