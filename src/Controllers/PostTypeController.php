<?php

namespace ListingItems\Controllers;

class PostTypeController {

	/**
	 * Register custom post type.
	 *
	 * @return void
	 */
	public function register() {
		$labels = array(
			'name'                  => _x( 'Listings', 'Post Type General Name', 'listing-items' ),
			'singular_name'         => _x( 'Listing', 'Post Type Singular Name', 'listing-items' ),
			'menu_name'             => __( 'Listings', 'listing-items' ),
			'name_admin_bar'        => __( 'Listing', 'listing-items' ),
			'archives'              => __( 'Listing Archives', 'listing-items' ),
			'attributes'            => __( 'Listing Attributes', 'listing-items' ),
			'parent_item_colon'     => __( 'Parent Listing:', 'listing-items' ),
			'all_items'             => __( 'All Listings', 'listing-items' ),
			'add_new_item'          => __( 'Add New Listing', 'listing-items' ),
			'add_new'               => __( 'Add New', 'listing-items' ),
			'new_item'              => __( 'New Listing', 'listing-items' ),
			'edit_item'             => __( 'Edit Listing', 'listing-items' ),
			'update_item'           => __( 'Update Listing', 'listing-items' ),
			'view_item'             => __( 'View Listing', 'listing-items' ),
			'view_items'            => __( 'View Listings', 'listing-items' ),
			'search_items'          => __( 'Search Listing', 'listing-items' ),
			'not_found'             => __( 'Not found', 'listing-items' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'listing-items' ),
			'featured_image'        => __( 'Featured Image', 'listing-items' ),
			'set_featured_image'    => __( 'Set featured image', 'listing-items' ),
			'remove_featured_image' => __( 'Remove featured image', 'listing-items' ),
			'use_featured_image'    => __( 'Use as featured image', 'listing-items' ),
			'insert_into_item'      => __( 'Insert into listing', 'listing-items' ),
			'uploaded_to_this_item' => __( 'Uploaded to this listing', 'listing-items' ),
			'items_list'            => __( 'Listings list', 'listing-items' ),
			'items_list_navigation' => __( 'Listings list navigation', 'listing-items' ),
			'filter_items_list'     => __( 'Filter listings list', 'listing-items' ),
		);

		$args = array(
			'label'               => __( 'Listing', 'listing-items' ),
			'description'         => __( 'Listing items with custom details', 'listing-items' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
			'taxonomies'          => array( 'listing_category', 'listing_tag' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-list-view',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
		);

		register_post_type( 'listing', $args );
	}
}
