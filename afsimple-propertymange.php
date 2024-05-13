<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.linkedin.com/in/itshahmir/
 * @since             1.0.0
 * @package           Afsimple_Propertymange
 *
 * @wordpress-plugin
 * Plugin Name:       AFsimple - Manage Properties
 * Plugin URI:        https://auctionflippers.com/plugins
 * Description:       AFsimple Property Management tool. Supports CSV, XML Feeds & more coming soon
 * Version:           1.0.0
 * Author:            Ali Shahmir Khan,  Wojciech Puzniak
 * Author URI:        https://www.linkedin.com/in/itshahmir/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       afsimple-propertymange
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'AFSIMPLE_PROPERTYMANGE_VERSION', '1.0.0' );

require plugin_dir_path( __FILE__ ) . 'includes/AFPM_main.php';
require plugin_dir_path( __FILE__ ) . 'includes/AFPM_upload.php';
define('PATHAFPM',plugin_dir_path( __FILE__ ));
define('URLAFPM',plugin_dir_url( __FILE__ ));
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_afsimple_propertymange() {
	global $upload_csv;
	$plugin = new AFPM_Main();
	$plugin->run();
	add_action( 'wp_ajax_uploadCSVHandler', 'AFPM_Main::uploadCSVProcess' );
	add_action( 'wp_ajax_uploadHandler', 'AFPM_Main::upload' );
	

}



run_afsimple_propertymange();
