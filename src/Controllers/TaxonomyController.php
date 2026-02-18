<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Config\PostType as PostTypeConfig;
use CBListingAnything\Config\Taxonomies as TaxonomiesConfig;
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
		$args = TaxonomiesConfig::category_args();
		$slug = SettingsController::get( 'listing_slug', 'cb_listing' );
		$args['rewrite'] = array( 'slug' => $slug . '-category' );
		register_taxonomy( TaxonomiesConfig::CATEGORY_TAXONOMY, array( PostTypeConfig::POST_TYPE ), $args );
	}

	/**
	 * Register listing tags taxonomy.
	 *
	 * @return void
	 */
	private function register_tags() {
		$args = TaxonomiesConfig::tag_args();
		$slug = SettingsController::get( 'listing_slug', 'cb_listing' );
		$args['rewrite'] = array( 'slug' => $slug . '-tag' );
		register_taxonomy( TaxonomiesConfig::TAG_TAXONOMY, array( PostTypeConfig::POST_TYPE ), $args );
	}
}
