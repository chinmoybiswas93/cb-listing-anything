<?php

namespace CBListingAnything\Core;

/**
 * Base controller for plugin controllers.
 * Optional: use plugin() for access to Plugin singleton.
 */
abstract class AbstractController {

	/**
	 * Get the plugin instance.
	 *
	 * @return Plugin
	 */
	protected function plugin() {
		return Plugin::instance();
	}
}
