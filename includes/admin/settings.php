<?php
/**
 * Settings
 *
 * @package     PrintCenter\Admin\Settings
 * @since       1.0.0
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add the settings menu item
 *
 * @since       1.0.0
 * @param       array $menu The existing menu settings
 * @return      array $menu The updated menu settings
 */
function printcenter_menu( $menu ) {
	$menu['page_title'] = __( 'PrintCenter Settings', 'printcenter' );
	$menu['menu_title'] = __( 'PrintCenter', 'printcenter' );

	return $menu;
}
add_filter( 'printcenter_menu', 'printcenter_menu' );


/**
 * Add the settings menu item
 *
 * @since       1.0.0
 * @param       array $tabs The existing settings tabs
 * @return      array $tabs The updated settings tabs
 */
function printcenter_settings_tabs( $tabs ) {
	$tabs['ssi']   = __( 'SSI', 'printcenter' );
	$tabs['email'] = __( 'Email', 'printcenter' );

	return $tabs;
}
add_filter( 'printcenter_settings_tabs', 'printcenter_settings_tabs' );


/**
 * Add the settings
 *
 * @since       1.0.0
 * @param       array $settings The existing settings
 * @return      array $settings The updated settings
 */
function printcenter_settings( $settings ) {
	$plugin_settings = array(
		'ssi' => apply_filters( 'printcenter_ssi_settings', array(
			array(
				'id'   => 'ssi_header',
				'name' => __( 'SSI Settings', 'printcenter' ),
				'desc' => '',
				'type' => 'header'
			),
			array(
				'id'      => 'ssi_mode',
				'name'    => __( 'Processing Mode', 'printcenter' ),
				'desc'    => __( 'Choose the SSI data processing mode', 'printcenter' ),
				'type'    => 'select',
				'options' => array(
					'live'    => __( 'Live', 'printcenter' ),
					'test'    => __( 'Test', 'printcenter' ),
					'capture' => __( 'Capture', 'printcenter' )
				)
			),
			array(
				'id'   => 'ssi_custid',
				'name' => __( 'Live Customer ID', 'printcenter' ),
				'desc' => __( 'Your SSI customer ID', 'printcenter' ),
				'type' => 'text',
				'std'  => '1024'
			),
			array(
				'id'   => 'ssi_custzip',
				'name' => __( 'Live Customer Zip Code', 'printcenter' ),
				'desc' => __( 'Your SSI billing zip code', 'printcenter' ),
				'type' => 'text',
				'std'  => '80304'
			),
			array(
				'id'   => 'ssi_test_custid',
				'name' => __( 'Test Customer ID', 'printcenter' ),
				'desc' => __( 'Test ID provided by SSI', 'printcenter' ),
				'type' => 'text',
				'std'  => '1013'
			),
			array(
				'id'   => 'ssi_test_custzip',
				'name' => __( 'Test Zip Code', 'printcenter' ),
				'desc' => __( 'Test zip code provided by SSI', 'printcenter' ),
				'type' => 'text',
				'std'  => '99999'
			)
		) ),
		'email' => apply_filters( 'printcenter_email_settings', array(
			array(
				'id'   => 'email_header',
				'name' => __( 'Email Settings', 'printcenter' ),
				'desc' => '',
				'type' => 'header'
			),
		) )
	);

	return array_merge( $settings, $plugin_settings );
}
add_filter( 'printcenter_registered_settings', 'printcenter_settings' );