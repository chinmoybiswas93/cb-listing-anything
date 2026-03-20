<?php
/**
 * Core bootstrap for the CB Listing Anything plugin.
 *
 * Required from the main plugin file after constants and the Composer
 * autoloader are loaded. Wires WordPress hooks to the plugin core
 * and exposes the public helper API.
 */

use CBListingAnything\Core\Plugin;
use CrocoDevs\Framework;

if ( ! function_exists( 'cb_listing_anything_activate' ) ) {
	/**
	 * Activation hook callback.
	 *
	 * @return void
	 */
	function cb_listing_anything_activate() {
		if ( ! class_exists( Plugin::class ) ) {
			return;
		}

		add_role(
			'cb_listing_contributor',
			__( 'List Contributor', 'cb-listing-anything' ),
			array(
				'read'         => true,
				'edit_posts'   => true,
				'upload_files' => true,
			)
		);

		Plugin::instance()->register_content_types();

		flush_rewrite_rules();
	}
}

if ( ! function_exists( 'cb_listing_anything_deactivate' ) ) {
	/**
	 * Deactivation hook callback.
	 *
	 * @return void
	 */
	function cb_listing_anything_deactivate() {
		flush_rewrite_rules();
	}
}

register_activation_hook( CB_LISTING_ANYTHING_PLUGIN_FILE, 'cb_listing_anything_activate' );
register_deactivation_hook( CB_LISTING_ANYTHING_PLUGIN_FILE, 'cb_listing_anything_deactivate' );

if ( ! function_exists( 'cb_listing_anything_init' ) ) {
	/**
	 * Initialize plugin on plugins_loaded.
	 *
	 * @return Plugin|null
	 */
	function cb_listing_anything_init() {
		if ( ! class_exists( Plugin::class ) ) {
			return null;
		}

		Framework::bootstrap( CB_LISTING_ANYTHING_PLUGIN_DIR );

		$plugin = Plugin::instance();
		$plugin->run();

		return $plugin;
	}
}

add_action( 'plugins_loaded', 'cb_listing_anything_init' );

if ( ! function_exists( 'cb_listing_anything' ) ) {
	/**
	 * Get the plugin instance.
	 *
	 * @return Plugin
	 */
	function cb_listing_anything() {
		return Plugin::instance();
	}
}

if ( ! function_exists( 'cb_listing_anything_maybe_hide_admin_bar' ) ) {
	/**
	 * Hide the WordPress admin bar for List Contributor users.
	 *
	 * @param bool $show Whether to show the admin bar.
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
}

add_filter( 'show_admin_bar', 'cb_listing_anything_maybe_hide_admin_bar' );

if ( ! function_exists( 'cb_listing_anything_get_category_image_id' ) ) {
	/**
	 * Get the category image attachment ID for a listing category term.
	 *
	 * @param int $term_id Listing category term ID.
	 * @return int Attachment ID, or 0 if none set.
	 */
	function cb_listing_anything_get_category_image_id( $term_id ) {
		return (int) get_term_meta( $term_id, 'cb_listing_anything_category_image', true );
	}
}
