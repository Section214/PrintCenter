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


/**
 * Register this site for plugin updates
 *
 * @since       1.0.0
 * @return      void
 */
function printcenter_register_site() {
	if( ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	$license = get_option( 'printcenter_license', false );

	if( $license != 'valid' ) {
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => '98731c11ef37695fa07a7b0151e0a00e',
			'item_name'  => 'PrintCenter',
			'url'        => home_url()
		);

		// Call the API
		$response = wp_remote_post( 'https://section214.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		if( is_wp_error( $response ) ) {
			return false;
		}

		// Decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		update_option( 'printcenter_license', $license_data->license );
		delete_transient( 'printcenter_license' );
	}
}