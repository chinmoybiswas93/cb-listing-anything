<?php

namespace CBListingAnything\Config;

/**
 * Listing category and tag taxonomy labels and registration args.
 */
class Taxonomies {

	/** Category taxonomy slug. */
	public const CATEGORY_TAXONOMY = 'cb_listing_category';

	/** Tag taxonomy slug. */
	public const TAG_TAXONOMY = 'cb_listing_tag';

	/**
	 * Get labels for the cb_listing_category taxonomy.
	 *
	 * @return array
	 */
	public static function category_labels() {
		return array(
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
	}

	/**
	 * Get registration args for the cb_listing_category taxonomy.
	 *
	 * @return array
	 */
	public static function category_args() {
		return array(
			'hierarchical'      => true,
			'labels'            => self::category_labels(),
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'cb-listing-category' ),
			'show_in_rest'      => true,
		);
	}

	/**
	 * Get labels for the cb_listing_tag taxonomy.
	 *
	 * @return array
	 */
	public static function tag_labels() {
		return array(
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
	}

	/**
	 * Get registration args for the cb_listing_tag taxonomy.
	 *
	 * @return array
	 */
	public static function tag_args() {
		return array(
			'hierarchical'      => false,
			'labels'            => self::tag_labels(),
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'cb-listing-tag' ),
			'show_in_rest'      => true,
		);
	}
}
