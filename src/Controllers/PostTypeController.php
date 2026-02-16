<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Config\PostType as PostTypeConfig;
use CBListingAnything\Core\AbstractController;

class PostTypeController extends AbstractController {

	/**
	 * Register custom post type.
	 *
	 * @return void
	 */
	public function register() {
		$args = PostTypeConfig::args();
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

		register_post_type( PostTypeConfig::POST_TYPE, $args );
	}
}
