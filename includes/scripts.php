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

    if( ! apply_filters( 'printcenter_load_admin_scripts', printcenter_is_admin_page( $hook ), $hook ) ) {
        return;
    }

    // Use minified libraries if SCRIPT_DEBUG is turned off
    $suffix     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
    $ui_style   = ( get_user_option( 'admin_color' ) == 'classic' ) ? 'classic' : 'fresh';

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_enqueue_media();
    wp_enqueue_style( 'jquery-ui-css', PRINTCENTER_URL . 'assets/css/jquery-ui-' . $ui_style . $suffix . '.css' );
    wp_enqueue_script( 'media-upload' );
    wp_enqueue_style( 'thickbox' );
    wp_enqueue_script( 'thickbox' );

    wp_enqueue_style( 'printcenter-fa', PRINTCENTER_URL . 'assets/css/font-awesome.min.css', array(), '4.3.0' );
    wp_enqueue_style( 'printcenter', PRINTCENTER_URL . 'assets/css/admin' . $suffix . '.css', array(), PRINTCENTER_VER );
    wp_enqueue_script( 'printcenter', PRINTCENTER_URL . 'assets/js/admin' . $suffix . '.js', array( 'jquery' ), PRINTCENTER_VER );
    wp_localize_script( 'printcenter', 'printcenter_vars', array(
        'image_media_button'    => __( 'Insert Image', 'printcenter' ),
        'image_media_title'     => __( 'Select Image', 'printcenter' )
    ) );
}
add_action( 'admin_enqueue_scripts', 'printcenter_admin_scripts', 100 );
