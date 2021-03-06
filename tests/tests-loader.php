<?php


class Tests_Loader extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @covers PrintCenter_Loader::setup_constants
	 */
	public function test_constants() {
		// Plugin Folder URL
		$path = str_replace( 'tests/', '', plugin_dir_url( __FILE__ ) );
		$this->assertSame( PRINTCENTER_URL, $path );

		// Plugin Folder Path
		$path = str_replace( 'tests/', '', plugin_dir_path( __FILE__ ) );
		$this->assertSame( PRINTCENTER_DIR, $path );

		// Plugin Root File
		$path = str_replace( 'tests/', '', plugin_dir_path( __FILE__ ) );
		$this->assertSame( PRINTCENTER_FILE, $path . 'printcenter.php' );
	}

	/**
	 * @covers PrintCenter_Loader::includes
	 */
	public function test_includes() {
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/actions.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/scripts.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/functions.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/post-types.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/admin/settings.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/admin/contextual-help.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/ssitest.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/admin/product-settings.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/admin/ssi-products/meta-boxes.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/class.ssi-api.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/class.shipping-api.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/vendors/class.product-vendors.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/vendors/class.product-vendors-commissions.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/vendors/class.product-vendors-widget.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/vendors/class.product-vendors-export-handler.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/vendors/actions.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/vendors/functions.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/vendors/reports.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/class.loader.php' );

		/** Check Libraries Exist */
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/S214-Settings/source/class.s214-settings.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/tgm-plugin-activation/class-tgm-plugin-activation.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/Array2XML.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/xmlstr_to_array.php' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/S214_Plugin_Updater.php' );

        /** Check Assets Exist */
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/S214-Settings/source/assets/css/admin.css' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/S214-Settings/source/assets/css/admin.min.css' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/S214-Settings/source/assets/css/jquery-ui-classic.css' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/S214-Settings/source/assets/css/jquery-ui-classic.min.css' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/S214-Settings/source/assets/css/jquery-ui-fresh.css' );
		$this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/S214-Settings/source/assets/css/jquery-ui-fresh.min.css' );
        $this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/S214-Settings/source/assets/js/admin.js' );
        $this->assertFileExists( PRINTCENTER_DIR . 'includes/libraries/S214-Settings/source/assets/js/admin.min.js' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/css/admin.css' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/css/admin.min.css' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/css/font.css' );
		$this->assertFileExists( PRINTCENTER_DIR . 'assets/css/font.min.css' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/img/help/product-help-1.png' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/img/help/product-help-2.png' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/img/help/product-help-3.png' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/img/help/product-help-4.png' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/img/help/product-help-5.png' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/img/help/commission-help-1.png' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/font/printcenter.eot' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/font/printcenter.svg' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/font/printcenter.ttf' );
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/font/printcenter.woff' );

        /** Check Installables Exist */
        $this->assertFileExists( PRINTCENTER_DIR . 'assets/plugins/advanced-product-attributes.zip' );
	}
}
