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
	$menu['type']       = 'submenu';
	$menu['parent']     = 'edit.php?post_type=shop_commission';
	$menu['page_title'] = __( 'PrintCenter Settings', 'printcenter' );
	$menu['menu_title'] = __( 'Settings', 'printcenter' );

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
  $tabs['general'] = __( 'General', 'printcenter' );

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
		'general' => apply_filters( 'printcenter_general_settings', array(
			array(
				'id' => 'general_header',
				'name' => __( 'General Settings', 'printcenter' ),
				'desc' => '',
				'type' => 'header'
			)
		) )
	);

	return array_merge( $settings, $plugin_settings );
}
add_filter( 'printcenter_registered_settings', 'printcenter_settings' );