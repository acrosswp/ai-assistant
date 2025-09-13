<?php
/**
 * Instantiates the Ai Assistant plugin
 *
 * @package Ai_Assistant
 */

namespace Ai_Assistant;

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/WPBoilerplate/ai-assistant
 * @since             0.0.1
 * @package           Ai_Assistant
 *
 * @wordpress-plugin
 * Plugin Name:       Ai Assistant
 * Plugin URI:        https://github.com/WPBoilerplate/ai-assistant
 * Description:       Ai Assistant by WPBoilerplate
 * Version:           0.0.8
 * Author:            WPBoilerplate
 * Author URI:        https://github.com/WPBoilerplate/ai-assistant
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ai-assistant
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load Composer autoloader
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

/**
 * Currently plugin version.
 * Start at version 0.0.1 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'AI_ASSISTANT_PLUGIN_FILE', __FILE__ );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/activator.php
 */
function ai_assistant_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/activator.php';
	Includes\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/deactivator.php
 */
function ai_assistant_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/deactivator.php';
	Includes\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'Ai_Assistant\ai_assistant_activate' );
register_deactivation_hook( __FILE__, 'Ai_Assistant\ai_assistant_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/main_class.php';

use Ai_Assistant\Includes\Main;

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.0.1
 */
function ai_assistant_run() {

	$plugin = Main::instance();

	/**
	 * Run this plugin on the plugins_loaded functions
	 */
	add_action( 'plugins_loaded', array( $plugin, 'run' ), 0 );
}
ai_assistant_run();
