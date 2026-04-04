<?php
/**
 * Plugin Name:       WP Headless Content Extractor
 * Description:       Extracts all website content and exports it into a highly structured .ZIP file containing JSON and Markdown files for headless migrations.
 * Version:           1.0.0
 * Author:            Jules
 * Text Domain:       wp-headless-extractor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants
define( 'WPHE_VERSION', '1.0.0' );
define( 'WPHE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPHE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader for plugin classes.
 */
spl_autoload_register( function ( $class ) {
	// Only handle WP_Headless_Extractor namespace.
	if ( strpos( $class, 'WP_Headless_Extractor\\' ) !== 0 ) {
		return;
	}

	$class_name = str_replace( 'WP_Headless_Extractor\\', '', $class );

	// Convert namespace to path
	$class_path = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );

	// Handle special cases based on our folder structure
	$parts = explode( DIRECTORY_SEPARATOR, $class_path );
	$file_name = 'class-wphe-' . strtolower( str_replace( '_', '-', array_pop( $parts ) ) ) . '.php';

	// Check includes directory
	$includes_file = WPHE_PLUGIN_DIR . 'includes/' . $file_name;
	if ( file_exists( $includes_file ) ) {
		require_once $includes_file;
		return;
	}

	// Check admin directory
	$admin_file = WPHE_PLUGIN_DIR . 'admin/' . $file_name;
	if ( file_exists( $admin_file ) ) {
		require_once $admin_file;
		return;
	}
} );

/**
 * Begins execution of the plugin.
 */
function run_wp_headless_extractor() {
	$plugin = new WP_Headless_Extractor\Init();
	$plugin->run();
}

// Initialize the plugin
add_action( 'plugins_loaded', 'run_wp_headless_extractor' );
