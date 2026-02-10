<?php
/**
 * Plugin Name: Listing Items
 * Plugin URI: https://example.com/listing-items
 * Description: A standard plugin for managing listing items with custom post type, categories, tags, custom fields, and Gutenberg blocks.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: listing-items
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LISTING_ITEMS_VERSION', '1.0.0' );
define( 'LISTING_ITEMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LISTING_ITEMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LISTING_ITEMS_PLUGIN_FILE', __FILE__ );

if ( file_exists( LISTING_ITEMS_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once LISTING_ITEMS_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Activation hook
 */
function listing_items_activate() {
	if ( ! class_exists( 'ListingItems\\Core\\Plugin' ) ) {
		return;
	}

	$plugin = ListingItems\Core\Plugin::instance();
	$plugin->register_content_types();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'listing_items_activate' );

/**
 * Deactivation hook
 */
function listing_items_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'listing_items_deactivate' );

/**
 * Initialize plugin
 */
function listing_items_init() {
	if ( ! class_exists( 'ListingItems\\Core\\Plugin' ) ) {
		return null;
	}

	$plugin = ListingItems\Core\Plugin::instance();
	$plugin->run();

	return $plugin;
}

add_action( 'plugins_loaded', 'listing_items_init' );
