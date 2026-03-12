<?php
/**
 * Plugin Name: CB Listing Anything
 * Plugin URI: https://chinmoybiswas.com/cb-listing-anything
 * Description: A standard plugin for managing listing items with custom post type, categories, tags, custom fields, and Gutenberg blocks.
 * Version: 1.0.1
 * Author: Chinmoy Biswas
 * Author URI: https://chinmoybiswas.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cb-listing-anything
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * Tested up to: 6.9.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CB_LISTING_ANYTHING_VERSION', '1.0.1' );
define( 'CB_LISTING_ANYTHING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CB_LISTING_ANYTHING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CB_LISTING_ANYTHING_PLUGIN_FILE', __FILE__ );

// Load Composer autoloader (PSR-4 for both CBListingAnything and CrocoDevs).
if ( file_exists( CB_LISTING_ANYTHING_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once CB_LISTING_ANYTHING_PLUGIN_DIR . 'vendor/autoload.php';
}
// Wire plugin-specific hooks and helpers via the core bootstrap file.
require_once CB_LISTING_ANYTHING_PLUGIN_DIR . 'src/Core/Bootstrap.php';
