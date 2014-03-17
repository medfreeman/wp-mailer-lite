<?php
/**
 * Mailer Lite
 *
 * Mailer Lite ajax forms
 *
 * @package   Mailer_Lite
 * @author    Mehdi Lahlou <mehdi.lahlou@free.fr>
 * @license   GPL-2.0+
 * @link      http://www.mappingfestival.com
 * @copyright 2014 Mehdi Lahlou
 *
 * @wordpress-plugin
 * Plugin Name:       Mailer Lite
 * Plugin URI:        http://www.mappingfestival.com
 * Description:       Mailer Lite ajax forms
 * Version:           1
 * Author:            Mehdi Lahlou
 * Author URI:        
 * Text Domain:       mailer-lite
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: 
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-mailer-lite.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'Mailer_Lite', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Mailer_Lite', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Mailer_Lite', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * @TODO:
 *
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-mailer-lite-admin.php' );
	add_action( 'plugins_loaded', array( 'Mailer_Lite_Admin', 'get_instance' ) );

}
