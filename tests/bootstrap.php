<?php
/**
 * Bootstraps the PHPUnit test suite
 *
 * @package     PrintCenter\Tests\Bootstrap
 * @since       1.0.0
 */

$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_NAME'] = '';
$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin we are testing
 *
 * @since       1.0.0
 * @return      void
 */
function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../printcenter.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

activate_plugin( 'printcenter/printcenter.php' );

echo "Installing PrintCenter...\n";

global $current_user, $printcenter_options;

$printcenter_options = get_option( 'printcenter_settings' );

$current_user = new WP_User(1);
$current_user->set_role('administrator');
