<?php
/**
 * Admin actions
 *
 * @package     PrintCenter\Admin\Actions
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Process all actions sent via POST and GET by looking for the 'printcenter-action'
 * request and running do_action() to call the function
 *
 * @since       1.0.0
 * @return      void
 */
function printcenter_process_actions() {
	if( isset( $_POST['printcenter-action'] ) ) {
		do_action( 'printcenter_' . $_POST['printcenter-action'], $_POST );
	}

	if( isset( $_GET['printcenter-action'] ) ) {
		do_action( 'printcenter_' . $_GET['printcenter-action'], $_GET );
	}
}
add_action( 'init', 'printcenter_process_actions' );
add_action( 'admin_init', 'printcenter_process_actions' );
