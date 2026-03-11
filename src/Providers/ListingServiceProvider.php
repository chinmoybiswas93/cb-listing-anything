<?php

namespace CBListingAnything\Providers;

use CBListingAnything\Controllers\BlockController;
use CBListingAnything\Controllers\CategoryImageController;
use CBListingAnything\Controllers\MediaController;
use CBListingAnything\Controllers\MetaBoxController;
use CBListingAnything\Controllers\PostTypeController;
use CBListingAnything\Controllers\SettingsController;
use CBListingAnything\Controllers\TaxonomyController;
use CBListingAnything\Rest\SearchController;
use CBListingAnything\Rest\TermController;
use CrocoDevs\Container\ServiceManager;
use CrocoDevs\Database\QueryBuilder;
use CrocoDevs\Support\ServiceProvider;

/**
 * Wires CB Listing Anything services into the CrocoDevs container.
 */
class ListingServiceProvider extends ServiceProvider {

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		ServiceManager::singleton( 'cb.listing.post_type_controller', function () {
			return new PostTypeController();
		} );

		ServiceManager::singleton( 'cb.listing.taxonomy_controller', function () {
			return new TaxonomyController();
		} );

		ServiceManager::singleton( 'cb.listing.meta_box_controller', function () {
			return new MetaBoxController();
		} );

		ServiceManager::singleton( 'cb.listing.block_controller', function () {
			return new BlockController();
		} );

		ServiceManager::singleton( 'cb.listing.settings_controller', function () {
			return new SettingsController();
		} );

		ServiceManager::singleton( 'cb.listing.category_image_controller', function () {
			return new CategoryImageController();
		} );

		ServiceManager::singleton( 'cb.listing.media_controller', function () {
			return new MediaController();
		} );

		ServiceManager::singleton( 'cb.listing.rest.search_controller', function () {
			return new SearchController();
		} );

		ServiceManager::singleton( 'cb.listing.rest.term_controller', function () {
			return new TermController();
		} );

		ServiceManager::register( 'cb.listing.query', function ( array $base_args = array() ) {
			return QueryBuilder::make( $base_args );
		} );
	}
}
