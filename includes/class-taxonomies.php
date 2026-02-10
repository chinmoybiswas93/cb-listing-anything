<?php
/**
 * Taxonomies Registration
 *
 * @package Listing_Items
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Listing_Items_Taxonomies
 */
class Listing_Items_Taxonomies {

	/**
	 * Register custom taxonomies
	 */
	public function register() {
		$this->register_categories();
		$this->register_tags();
	}

	/**
	 * Register Listing Categories
	 */
	private function register_categories() {
		$labels = array(
			'name'              => _x( 'Listing Categories', 'taxonomy general name', 'listing-items' ),
			'singular_name'     => _x( 'Listing Category', 'taxonomy singular name', 'listing-items' ),
			'search_items'      => __( 'Search Categories', 'listing-items' ),
			'all_items'         => __( 'All Categories', 'listing-items' ),
			'parent_item'       => __( 'Parent Category', 'listing-items' ),
			'parent_item_colon' => __( 'Parent Category:', 'listing-items' ),
			'edit_item'         => __( 'Edit Category', 'listing-items' ),
			'update_item'       => __( 'Update Category', 'listing-items' ),
			'add_new_item'      => __( 'Add New Category', 'listing-items' ),
			'new_item_name'     => __( 'New Category Name', 'listing-items' ),
			'menu_name'         => __( 'Categories', 'listing-items' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'listing-category' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'listing_category', array( 'listing' ), $args );
	}

	/**
	 * Register Listing Tags
	 */
	private function register_tags() {
		$labels = array(
			'name'                       => _x( 'Listing Tags', 'taxonomy general name', 'listing-items' ),
			'singular_name'              => _x( 'Listing Tag', 'taxonomy singular name', 'listing-items' ),
			'search_items'               => __( 'Search Tags', 'listing-items' ),
			'popular_items'              => __( 'Popular Tags', 'listing-items' ),
			'all_items'                  => __( 'All Tags', 'listing-items' ),
			'edit_item'                  => __( 'Edit Tag', 'listing-items' ),
			'update_item'                => __( 'Update Tag', 'listing-items' ),
			'add_new_item'               => __( 'Add New Tag', 'listing-items' ),
			'new_item_name'              => __( 'New Tag Name', 'listing-items' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'listing-items' ),
			'add_or_remove_items'       => __( 'Add or remove tags', 'listing-items' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'listing-items' ),
			'not_found'                  => __( 'No tags found.', 'listing-items' ),
			'menu_name'                  => __( 'Tags', 'listing-items' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'listing-tag' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'listing_tag', array( 'listing' ), $args );
	}
}
