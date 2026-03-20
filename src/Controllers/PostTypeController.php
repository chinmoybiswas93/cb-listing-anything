<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Core\AbstractController;

class PostTypeController extends AbstractController {

	/**
	 * Register custom post type.
	 *
	 * @return void
	 */
	public function register() {
		$labels = $this->labels();

		$args = array(
			'label'               => __( 'Listing', 'cb-listing-anything' ),
			'description'         => __( 'Listing items with custom details', 'cb-listing-anything' ),
			'labels'              => $labels,
			'supports'            => crocodevs_config( 'post_type.supports' ),
			'taxonomies'          => array( crocodevs_config( 'taxonomies.category' ), crocodevs_config( 'taxonomies.tag' ) ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'cb-listing-anything',
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

		$slug = SettingsController::get( 'listing_slug', 'cb_listing' );
		$args['rewrite'] = array( 'slug' => $slug );

		$title = SettingsController::get( 'listing_title', __( 'Listing', 'cb-listing-anything' ) );
		if ( $title !== '' ) {
			$args['label'] = $title;
			$args['labels'] = array_merge( $args['labels'], array(
				'name'          => $title . 's',
				'singular_name' => $title,
				'menu_name'     => $title . 's',
				'archives'      => $title . ' ' . __( 'Archives', 'cb-listing-anything' ),
				'all_items'     => __( 'All', 'cb-listing-anything' ) . ' ' . $title . 's',
				'add_new_item'  => __( 'Add New', 'cb-listing-anything' ) . ' ' . $title,
				'edit_item'     => __( 'Edit', 'cb-listing-anything' ) . ' ' . $title,
				'view_item'     => __( 'View', 'cb-listing-anything' ) . ' ' . $title,
			) );
		}

		register_post_type( crocodevs_config( 'post_type.slug' ), $args );
	}

	/**
	 * @return array
	 */
	private function labels() {
		return array(
			'name'                  => _x( 'Listings', 'Post Type General Name', 'cb-listing-anything' ),
			'singular_name'         => _x( 'Listing', 'Post Type Singular Name', 'cb-listing-anything' ),
			'menu_name'             => __( 'Listings', 'cb-listing-anything' ),
			'name_admin_bar'        => __( 'Listing', 'cb-listing-anything' ),
			'archives'              => __( 'Listing Archives', 'cb-listing-anything' ),
			'attributes'            => __( 'Listing Attributes', 'cb-listing-anything' ),
			'parent_item_colon'     => __( 'Parent Listing:', 'cb-listing-anything' ),
			'all_items'             => __( 'All Listings', 'cb-listing-anything' ),
			'add_new_item'          => __( 'Add New Listing', 'cb-listing-anything' ),
			'add_new'               => __( 'Add New', 'cb-listing-anything' ),
			'new_item'              => __( 'New Listing', 'cb-listing-anything' ),
			'edit_item'             => __( 'Edit Listing', 'cb-listing-anything' ),
			'update_item'           => __( 'Update Listing', 'cb-listing-anything' ),
			'view_item'             => __( 'View Listing', 'cb-listing-anything' ),
			'view_items'            => __( 'View Listings', 'cb-listing-anything' ),
			'search_items'          => __( 'Search Listing', 'cb-listing-anything' ),
			'not_found'             => __( 'Not found', 'cb-listing-anything' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'cb-listing-anything' ),
			'featured_image'        => __( 'Featured Image', 'cb-listing-anything' ),
			'set_featured_image'    => __( 'Set featured image', 'cb-listing-anything' ),
			'remove_featured_image' => __( 'Remove featured image', 'cb-listing-anything' ),
			'use_featured_image'    => __( 'Use as featured image', 'cb-listing-anything' ),
			'insert_into_item'      => __( 'Insert into listing', 'cb-listing-anything' ),
			'uploaded_to_this_item' => __( 'Uploaded to this listing', 'cb-listing-anything' ),
			'items_list'            => __( 'Listings list', 'cb-listing-anything' ),
			'items_list_navigation' => __( 'Listings list navigation', 'cb-listing-anything' ),
			'filter_items_list'     => __( 'Filter listings list', 'cb-listing-anything' ),
		);
	}
}
