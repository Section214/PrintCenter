<?php
/**
 * Plugin Name:     PrintCenter
 * Plugin URI:      #
 * Description:     Provides the server components for the Customer Print Center aspect of Grow Thrive Print
 * Version:         1.0.0
 * Author:          Daniel J Griffiths
 * Author URI:      http://section214.com
 * Text Domain:     printcenter
 *
 * @package         PrintCenter
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


if( ! class_exists( 'PrintCenter' ) ) {


    /**
     * Main PrintCenter class
     *
     * @since       1.0.0
     */
    class PrintCenter {


        /**
         * @access      private
         * @since       1.0.0
         * @var         PrintCenter $instance The one true PrintCenter
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      self::$instance The one true PrintCenter
         */
        public static function instance() {
            if( ! self::$instance ) {
                self::$instance = new PrintCenter();
                self::$instance->setup_constants();

                add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

                self::$instance->includes();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'PRINTCENTER_VER', '1.0.0' );

            // Plugin path
            define( 'PRINTCENTER_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'PRINTCENTER_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Include required files
         *
         * @access      private
         * @since       1.0.0
         * @global      array $printcenter_options The PrintCenter options array
         * @return      void
         */
        private function includes() {
            global $printcenter_options;

            require_once PRINTCENTER_DIR . 'includes/admin/settings/register.php';
            $printcenter_options = printcenter_get_settings();

            require_once PRINTCENTER_DIR . 'includes/scripts.php';
            require_once PRINTCENTER_DIR . 'includes/functions.php';

            if( is_admin() ) {
                require_once PRINTCENTER_DIR . 'includes/admin/actions.php';
                require_once PRINTCENTER_DIR . 'includes/admin/pages.php';
                require_once PRINTCENTER_DIR . 'includes/admin/settings/display.php';
            }

            if( ! class_exists( 'WP_GitHub_Updater' ) ) {
                require_once PRINTCENTER_DIR . 'includes/libraries/updater/updater.php';
            }
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Process updates
            if( is_admin() ) {
                $update = new WP_GitHub_Updater( array(
                    'slug'                  => plugin_basename( __FILE__ ),
                    'proper_folder_name'    => 'printcenter',
                    'api_url'               => 'https://api.github.com/repos/Section214/PrintCenter',
                    'raw_url'               => 'https://raw.github.com/Section214/PrintCenter/master',
                    'github_url'            => 'https://github.com/Section214/PrintCenter',
                    'zip_url'               => 'https://github.com/Section214/PrintCenter/zipball/master',
                    'sslverify'             => true,
                    'requires'              => '3.0',
                    'tested'                => '4.2.2',
                    'readme'                => 'README.md'
                ) );
            }
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
            $lang_dir = apply_filters( 'printcenter_language_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), '' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'printcenter', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/printcenter/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/printcenter/ folder
                load_textdomain( 'printcenter', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/printcenter/languages/ folder
                load_textdomain( 'printcenter', $mofile_local );
            } else {
                load_plugin_textdomain( 'printcenter', false, $lang_dir );
            }
        }
    }
}


/**
 * The main function responsible for returning the one true PrintCenter
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      PrintCenter The one true PrintCenter
 */
function PrintCenter() {
    return PrintCenter::instance();
}
PrintCenter();
