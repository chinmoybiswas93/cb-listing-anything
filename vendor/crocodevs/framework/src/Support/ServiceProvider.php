<?php

namespace CrocoDevs\Support;

/**
 * Base service provider.
 *
 * Subclass this to register services with the container and
 * optionally perform boot-time wiring (hooks, filters, etc.).
 *
 * @package CrocoDevs\Support
 */
abstract class ServiceProvider {

	/**
	 * Register bindings with the service container.
	 *
	 * @return void
	 */
	abstract public function register();

	/**
	 * Boot logic that runs after all providers have been registered.
	 *
	 * @return void
	 */
	public function boot() {
	}

	/**
	 * WordPress hooks that trigger this provider's registration.
	 *
	 * Return an associative array of hook_name => priority.
	 * Return an empty array to register immediately (no deferred hook).
	 *
	 * @return array<string, int>
	 */
	public function hooks() {
		return array();
	}
}
