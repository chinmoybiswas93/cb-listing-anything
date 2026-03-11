<?php

namespace CBListingAnything\Rest;

use CBListingAnything\Config\App as AppConfig;
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
		return AppConfig::REST_NAMESPACE;
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	abstract public function register_routes();
}
