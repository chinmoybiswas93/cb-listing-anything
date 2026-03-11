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
}
