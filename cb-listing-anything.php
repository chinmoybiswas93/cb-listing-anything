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

	// Ensure custom role for front-end listing submissions exists.
	add_role(
		'cb_listing_contributor',
		__( 'List Contributor', 'cb-listing-anything' ),
		array(
			'read'         => true,
			'edit_posts'   => true,
			'upload_files' => true,
		)
	);

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
 * Get the plugin instance.
 * Call after plugins_loaded (e.g. in templates or other plugins).
 *
 * @return \CBListingAnything\Core\Plugin
 */
function cb_listing_anything() {
	return \CBListingAnything\Core\Plugin::instance();
}

/**
 * Hide the WordPress admin bar for List Contributor users.
 *
 * @param bool $show Whether to show the admin bar for the current user.
 * @return bool
 */
function cb_listing_anything_maybe_hide_admin_bar( $show ) {
	if ( ! is_user_logged_in() ) {
		return $show;
	}

	$user = wp_get_current_user();

	if ( in_array( 'cb_listing_contributor', (array) $user->roles, true ) ) {
		return false;
	}

	return $show;
}

add_filter( 'show_admin_bar', 'cb_listing_anything_maybe_hide_admin_bar' );

/**
 * Get the category image attachment ID for a listing category term.
 *
 * @param int $term_id Listing category term ID.
 * @return int Attachment ID, or 0 if none set.
 */
function cb_listing_anything_get_category_image_id( $term_id ) {
	return (int) get_term_meta( $term_id, 'cb_listing_anything_category_image', true );
}
