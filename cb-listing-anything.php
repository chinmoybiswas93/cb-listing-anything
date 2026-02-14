<?php
/**
 * Plugin Name: CB Listing Anything
 * Plugin URI: https://example.com/cb-listing-anything
 * Description: A standard plugin for managing listing items with custom post type, categories, tags, custom fields, and Gutenberg blocks.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cb-listing-anything
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CB_LISTING_ANYTHING_VERSION', '1.0.0' );
define( 'CB_LISTING_ANYTHING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CB_LISTING_ANYTHING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CB_LISTING_ANYTHING_PLUGIN_FILE', __FILE__ );

if ( file_exists( CB_LISTING_ANYTHING_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once CB_LISTING_ANYTHING_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Activation hook
 */
function cb_listing_anything_activate() {
	if ( ! class_exists( 'CBListingAnything\\Core\\Plugin' ) ) {
		return;
	}

	$plugin = CBListingAnything\Core\Plugin::instance();
	$plugin->register_content_types();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cb_listing_anything_activate' );

/**
 * Deactivation hook
 */
function cb_listing_anything_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cb_listing_anything_deactivate' );

/**
 * Initialize plugin
 */
function cb_listing_anything_init() {
	if ( ! class_exists( 'CBListingAnything\\Core\\Plugin' ) ) {
		return null;
	}

	$plugin = CBListingAnything\Core\Plugin::instance();
	$plugin->run();

	return $plugin;
}

add_action( 'plugins_loaded', 'cb_listing_anything_init' );

/**
 * Get the category image attachment ID for a listing category term.
 *
 * @param int $term_id Listing category term ID.
 * @return int Attachment ID, or 0 if none set.
 */
function cb_listing_anything_get_category_image_id( $term_id ) {
	return (int) get_term_meta( $term_id, 'cb_listing_anything_category_image', true );
}
