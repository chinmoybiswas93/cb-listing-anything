<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Core\AbstractController;

class TaxonomyController extends AbstractController {

	/**
	 * Register custom taxonomies.
	 *
	 * @return void
	 */
	public function register() {
		$this->register_categories();
		$this->register_tags();
	}

	/**
	 * Register listing categories taxonomy.
	 *
	 * @return void
	 */
	private function register_categories() {
		$labels = array(
			'name'              => _x( 'Listing Categories', 'taxonomy general name', 'cb-listing-anything' ),
			'singular_name'     => _x( 'Listing Category', 'taxonomy singular name', 'cb-listing-anything' ),
			'search_items'      => __( 'Search Categories', 'cb-listing-anything' ),
			'all_items'         => __( 'All Categories', 'cb-listing-anything' ),
			'parent_item'       => __( 'Parent Category', 'cb-listing-anything' ),
			'parent_item_colon' => __( 'Parent Category:', 'cb-listing-anything' ),
			'edit_item'         => __( 'Edit Category', 'cb-listing-anything' ),
			'update_item'       => __( 'Update Category', 'cb-listing-anything' ),
			'add_new_item'      => __( 'Add New Category', 'cb-listing-anything' ),
			'new_item_name'     => __( 'New Category Name', 'cb-listing-anything' ),
			'menu_name'         => __( 'Categories', 'cb-listing-anything' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'cb-listing-category' ),
			'show_in_rest'      => true,
		);

		$slug = SettingsController::get( 'listing_slug', 'cb_listing' );
		$args['rewrite'] = array( 'slug' => $slug . '-category' );
		register_taxonomy( crocodevs_config( 'taxonomies.category' ), array( crocodevs_config( 'post_type.slug' ) ), $args );
	}

	/**
	 * Register listing tags taxonomy.
	 *
	 * @return void
	 */
	private function register_tags() {
		$labels = array(
			'name'                       => _x( 'Listing Tags', 'taxonomy general name', 'cb-listing-anything' ),
			'singular_name'              => _x( 'Listing Tag', 'taxonomy singular name', 'cb-listing-anything' ),
			'search_items'               => __( 'Search Tags', 'cb-listing-anything' ),
			'popular_items'              => __( 'Popular Tags', 'cb-listing-anything' ),
			'all_items'                  => __( 'All Tags', 'cb-listing-anything' ),
			'edit_item'                  => __( 'Edit Tag', 'cb-listing-anything' ),
			'update_item'                => __( 'Update Tag', 'cb-listing-anything' ),
			'add_new_item'               => __( 'Add New Tag', 'cb-listing-anything' ),
			'new_item_name'              => __( 'New Tag Name', 'cb-listing-anything' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'cb-listing-anything' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'cb-listing-anything' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'cb-listing-anything' ),
			'not_found'                  => __( 'No tags found.', 'cb-listing-anything' ),
			'menu_name'                  => __( 'Tags', 'cb-listing-anything' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'cb-listing-tag' ),
			'show_in_rest'      => true,
		);

		$slug = SettingsController::get( 'listing_slug', 'cb_listing' );
		$args['rewrite'] = array( 'slug' => $slug . '-tag' );
		register_taxonomy( crocodevs_config( 'taxonomies.tag' ), array( crocodevs_config( 'post_type.slug' ) ), $args );
	}
}
