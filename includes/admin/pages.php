<?php
/**
 * Admin pages
 *
 * @package     PrintCenter\Admin\Pages
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Create the settings menu pages
 *
 * @since       1.0.0
 * @global      string $printcenter_settings_page The PrintCenter settings page hook
 * @return      void
 */
function printcenter_add_settings_pages() {
    global $printcenter_settings_page;

    $printcenter_settings_page = add_menu_page( __( 'SSI Test', 'printcenter' ), __( 'SSI Test', 'printcenter' ), 'manage_options', 'printcenter-settings', 'printcenter_render_settings_page' );
}
add_action( 'admin_menu', 'printcenter_add_settings_pages', 10 );


/**
 * Determines whether or not the current admin page is a PrintCenter page
 *
 * @since       1.0.0
 * @param       string $hook The hook for this page
 * @global      string $typenow The post type we are viewing
 * @global      string $pagenow The page we are viewing
 * @global      string $printcenter_settings_page The PrintCenter settings page hook
 * @return      bool $ret True if PrintCenter page, false otherwise
 */
function printcenter_is_admin_page( $hook ) {
    global $typenow, $pagenow, $printcenter_settings_page;

    $ret    = false;
    $pages  = apply_filters( 'printcenter_admin_pages', array( $printcenter_settings_page ) );

    if( in_array( $hook, $pages ) ) {
        $ret = true;
    }

    return (bool) apply_filters( 'printcenter_is_admin_page', $ret );
}
