<?php

/*
 * Plugin Name: Gastro-Rechner
 * Description: Ein professionelles Plugin für Gastronomen zur Berechnung von Umsätzen und Trinkgeldern.
 * Version: 1.1
 * Author: Your Company
 * Author URI: https://yourcompany.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gastro-rechner
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.2
 * Last updated: May 5, 2025
*/

// Verhindern, dass das Plugin direkt aufgerufen wird
if ( ! defined( 'ABSPATH' ) ) {
	exit("Not with me..."); // Exit if accessed directly
}

// Define plugin constants
define('GASTRO_RECHNER_VERSION', '1.1');
define('GASTRO_RECHNER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GASTRO_RECHNER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GASTRO_RECHNER_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoload classes
spl_autoload_register(function($class) {
    // Check if the class should be loaded by this autoloader
    if (strpos($class, 'Gastro_Rechner_') !== 0) {
        return;
    }
    
    // Convert class name to file path
    $class_path = str_replace('Gastro_Rechner_', '', $class);
    $class_path = strtolower(str_replace('_', '-', $class_path));
    $file = GASTRO_RECHNER_PLUGIN_DIR . 'includes/class-' . $class_path . '.php';
    
    // Load the file if it exists
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize the plugin
function gastro_rechner_init() {
    // Initialize main plugin class
    $gastro_rechner = new Gastro_Rechner_Main();
    $gastro_rechner->init();
    
    // Register activation and deactivation hooks
    register_activation_hook(__FILE__, array('Gastro_Rechner_Activator', 'activate'));
    register_deactivation_hook(__FILE__, array('Gastro_Rechner_Activator', 'deactivate'));
}

// Start the plugin
gastro_rechner_init();
?>