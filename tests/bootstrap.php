<?php
/**
 * Bootstrap the testing environment
 *
 * Uses wordpress tests (http://github.com/nb/wordpress-tests/) which uses
 * PHPUnit.
 *
 * Note: Do note change the name of this file. PHPUnit will automatically fire
 * this file when run.
 *
 */

$_tests_dir = getenv('WP_TESTS_DIR');
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../gsuite-provision.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
