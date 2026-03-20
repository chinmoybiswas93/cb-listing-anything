<?php

namespace CBListingAnything\Rest;

use CBListingAnything\Core\AbstractController;

/**
 * Base class for REST API controllers.
 *
 * Provides the shared REST namespace and requires subclasses to register routes.
 */
abstract class AbstractRestController extends AbstractController {

	/**
	 * Get the REST namespace for all plugin endpoints.
	 *
	 * @return string
	 */
	protected function rest_namespace() {
		return crocodevs_config( 'app.api_prefix' );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	abstract public function register_routes();
}
