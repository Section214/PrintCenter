<?php
/**
 * PrintCenter Loader
 *
 * The Loader class bootstraps components that are required
 * during both the install process and instantiation of the
 * main PrintCenter class. This allows us to load PrintCenter
 * on plugins_loaded and still access required components from
 * the register_plugin_activation hook.
 *
 * @package     PrintCenter\Loader
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * PrintCenter_Loader class
 *
 * A general use class for bootstrapping components required during load and install.
 *
 * @since       1.0.0
 */
class PrintCenter_Loader {


	/**
	 * @var         string $plugin_file The main plugin file
	 * @since       1.0.0
	 */
	public $plugin_file;


	/**
	 * @access      public
	 * @since       1.0.0
	 * @var         object $ssi The SSI API object
	 */
	public $ssi;


	/**
	 * @var         objct $settings The Domain Power Pack settings object
	 * @since       1.0.0
	 */
	public $settings;


	/**
	 * Setup the loader
	 *
	 * @since       1.0.0
	 * @param       string $plugin_file The main plugin file
	 */
	public function __construct( $plugin_file ) {
		// We need the main plugin file reference
		$this->plugin_file = $plugin_file;

		$this->setup_constants();
		$this->load_textdomain();
		$this->includes();
		$this->hooks();

		if( $this->settings->get_option( 'site_type', 'store' ) == 'store' ) {
			$this->ssi = new SSI_API();
		}
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
		if( ! defined( 'PRINTCENTER_VER' ) ) {
			define( 'PRINTCENTER_VER', '0.0.1' );
		}

		// Plugin path
		if( ! defined( 'PRINTCENTER_DIR' ) ) {
			define( 'PRINTCENTER_DIR', plugin_dir_path( $this->plugin_file ) );
		}

		// Plugin URL
		if( ! defined( 'PRINTCENTER_URL' ) ) {
			define( 'PRINTCENTER_URL', plugin_dir_url( $this->plugin_file ) );
		}

		// Plugin file
		if( ! defined( 'PRINTCENTER_FILE' ) ) {
			define( 'PRINTCENTER_FILE', $this->plugin_file );
		}
	}


	/**
	 * Include required files
	 *
	 * @access      private
	 * @since       1.0.0
	 * @global      array $printcenter_options The options array
	 * @return      void
	 */
	private function includes() {
		global $printcenter_options, $woo_vendors;

		require_once PRINTCENTER_DIR . 'includes/actions.php';
		require_once PRINTCENTER_DIR . 'includes/scripts.php';
		require_once PRINTCENTER_DIR . 'includes/functions.php';
		require_once PRINTCENTER_DIR . 'includes/post-types.php';

		// Libraries
		require_once PRINTCENTER_DIR . 'includes/libraries/Array2XML.php';

		if( is_admin() ) {
			require_once PRINTCENTER_DIR . 'includes/admin/settings.php';
			require_once PRINTCENTER_DIR . 'includes/ssitest.php';
		}

		// Settings
		if( ! class_exists( 'S214_Settings' ) ) {
			require_once PRINTCENTER_DIR . 'includes/libraries/S214-Settings/source/class.s214-settings.php';
		}

		$this->settings = new S214_Settings( 'printcenter', 'general' );
		$printcenter_options = $this->settings->get_settings();

		if( $this->settings->get_option( 'site_type', 'store' ) == 'store' ) {
			// TGM
			if( ! class_exists( 'TGM_Plugin_Activation' ) ) {
				require_once PRINTCENTER_DIR . 'includes/libraries/tgm-plugin-activation/class-tgm-plugin-activation.php';
			}

			if( is_admin() ) {
				require_once PRINTCENTER_DIR . 'includes/admin/product-settings.php';
				require_once PRINTCENTER_DIR . 'includes/admin/ssi-products/meta-boxes.php';
			}

			// SSI files
			require_once PRINTCENTER_DIR . 'includes/class.ssi-api.php';

			// Vendor files
			require_once PRINTCENTER_DIR . 'includes/vendors/class.product-vendors.php';
			require_once PRINTCENTER_DIR . 'includes/vendors/class.product-vendors-commissions.php';
			require_once PRINTCENTER_DIR . 'includes/vendors/class.product-vendors-widget.php';
			require_once PRINTCENTER_DIR . 'includes/vendors/class.product-vendors-export-handler.php';
			require_once PRINTCENTER_DIR . 'includes/vendors/actions.php';
			require_once PRINTCENTER_DIR . 'includes/vendors/functions.php';
			require_once PRINTCENTER_DIR . 'includes/vendors/reports.php';

			$woo_vendors                 = new WooCommerce_Product_Vendors( __FILE__ );
			$woo_vendors->commissions    = new WooCommerce_Product_Vendors_Commissions( __FILE__ );
			$woo_vendors->export_handler = new WooCommerce_Product_Vendors_Export_Handler();
		}

		if( ! class_exists( 'S214_Plugin_Updater' ) ) {
			require_once PRINTCENTER_DIR . 'includes/libraries/S214_Plugin_Updater.php';
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
		add_action( 'tgmpa_register', array( $this, 'plugin_activation' ) );

		// Licensing
		if( is_admin() && current_user_can( 'update_plugins' ) ) {
			$license = get_option( 'printcenter_license', false );

			if( $license == 'valid' ) {
				$update = new S214_Plugin_Updater( 'https://section214.com', $this->plugin_file, array(
					'version' => PRINTCENTER_VER,
					'license' => '98731c11ef37695fa07a7b0151e0a00e',
					'item_id' => 39378,
					'author'  => 'Daniel J Griffiths'
				) );
			} else {
				add_action( 'admin_init', 'printcenter_register_site' );
			}
		}
	}


	/**
	 * Loads the plugin language files
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function load_textdomain() {
		// Set filter for plugin languages directory
		$lang_dir = dirname( plugin_basename( PRINTCENTER_FILE ) ) . '/languages/';
		$lang_dir = apply_filters( 'printcenter_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale',  get_locale(), 'printcenter' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'printcenter', $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/printcenter/' . $mofile;
		$mofile_core   = WP_LANG_DIR . '/plugins/printcenter/' . $mofile;

		if( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/printcenter/ folder
			load_textdomain( 'printcenter', $mofile_global );
		} elseif( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/printcenter/languages/ folder
			load_textdomain( 'printcenter', $mofile_local );
		} elseif( file_exists( $mofile_core ) ) {
			// Look in core /wp-content/languages/plugins/printcenter/ folder
			load_textdomain( 'printcenter', $mofile_core );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'printcenter', false, $lang_dir );
		}
	}


	/**
	 * Plugin activation
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function plugin_activation() {
		if( $this->settings->get_option( 'site_type', 'store' ) == 'store' ) {
			$plugins = array(
				array(
					'name'     => __( 'JC WooCommerce Advanced Attributes', 'printcenter' ),
					'slug'     => 'jc-woocommerce-advanced-attributes',
					'source'   => PRINTCENTER_URL . 'assets/plugins/advanced-product-attributes.zip',
					'required' => true
				),
				array(
					'name'     => __( 'WooCommerce', 'printcenter' ),
					'slug'     => 'woocommerce',
					'required' => true
				)
			);
		} else {
			$plugins = array(
				array(
					'name'     => 'WordPress REST API (Version 2)',
					'slug'     => 'rest-api',
					'required' => true
				)
			);
		}

		$config = array(
			'id'           => 'printcenter',
			'default_path' => PRINTCENTER_URL . 'assets/plugins',
			'menu'         => 'printcenter-deps',
			'parent_slug'  => 'edit.php?post_type=shop_commission',
			'capability'   => 'install_plugins',
			'has_notices'  => true,
			'dismissable'  => false,
			'is_automatic' => false,
			'strings'      => array(
				'page_title'                     => __( 'Install PrintCenter Dependencies', 'printcenter' ),
				'menu_title'                     => __( 'Install Dependencies', 'printcenter' ),
				'notice_can_install_required'    => _n_noop( 'PrintCenter requires the following plugin: %1$s.', 'PrintCenter requires the following plugins: %1$s.', 'printcenter' ),
				'notice_can_install_recommended' => _n_noop( 'PrintCenter recommends the following plugin: %1$s.', 'PrintCenter recommends the following plugins: %1$s.', 'printcenter' ),
				'notice_ask_to_update'           => _n_noop( 'The following plugin needs to be updated to ensure compatibility with PrintCenter: %1$s', 'The following plugins need to be updated to ensure compatibility with PrintCenter', 'printcenter' ),
				'return'                         => __( 'Return to PrintCenter Dependency Installer', 'printcenter' ),
				'plugin_needs_higher_version'    => __( 'Plugin not activated. A more recent version of %s is required for PrintCenter.', 'printcenter' )
			)
		);

		tgmpa( $plugins, $config );
	}
}