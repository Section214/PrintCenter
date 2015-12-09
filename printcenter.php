<?php
/**
 * Plugin Name:     PrintCenter
 * Plugin URI:      http://customerprintcenter.com
 * Description:     Provides the server components for the Customer Print Center aspect of Grow Thrive Print
 * Version:         0.0.1
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
		 * @access      public
		 * @since       1.0.0
		 * @var         object $loader The PrintCenter loader object
		 */
		public $loader;


		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      self::$instance The one true PrintCenter
		 */
		public static function instance() {
			if( ! isset( self::$instance ) && ! ( self::$instance instanceof PrintCenter ) ) {
				self::$instance = new PrintCenter();

				// Bootstrap the plugin
				if( ! class_exists( 'PrintCenter_Loader' ) ) {
					require_once 'includes/class.loader.php';
				}
				self::$instance->loader = new PrintCenter_Loader( __FILE__ );
            }

			return self::$instance;
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
add_action( 'plugins_loaded', 'printcenter', 9 );