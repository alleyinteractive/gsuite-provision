<?php
/**
 * @package GSuite_Provision
 * @version 1.0
 */
/*
Plugin Name: GSuite User Provisioning
Plugin URI: https://alley.co
Description: Lightweight user provisioning via GSuite SSO. Used for Alley's Community of Practice P2 system.
Author: Matt Johnson
Version: 1.0
*/

/*  This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Version number.
 *
 * @var string
 */
define( 'GSUITE_PROVISION_VERSION', '1.0' );
/**
 * Include path.
 *
 * @var string
 */
define( 'GSUITE_PROVISION_PATH', plugin_dir_path( __FILE__ ) );
/**
 * Enqueue path.
 *
 * @var string
 */
define( 'GSUITE_PROVISION_URL', plugin_dir_url( __FILE__ ) );
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	wp_die( esc_html__( 'This file cannot be accessed directly', 'gsuite_provision' ) );
}

require_once GSUITE_PROVISION_PATH . 'php/class-gsuite-provision.php';
require_once GSUITE_PROVISION_PATH . 'php/class-gsuite-provision-settings.php';

/**
 * Instantiates plugin objects.
 */
function gsuite_provision_load() {
	gsuite_provision();
	gsuite_provision_settings();
}

/**
 * Instance generator for main object.
 */
function gsuite_provision() {
	return GSuite_Provision::instance();
}

/**
 * Instance generator for settings object.
 */
function gsuite_provision_settings() {
	return GSuite_Provision_Settings::instance();
}

add_action( 'plugins_loaded', 'gsuite_provision_load' );
