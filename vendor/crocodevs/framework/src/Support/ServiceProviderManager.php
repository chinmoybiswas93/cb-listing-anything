<?php

namespace CrocoDevs\Support;

/**
 * Manages the lifecycle of service providers.
 *
 * @package CrocoDevs\Support
 */
final class ServiceProviderManager {

	/**
	 * @var ServiceProvider[]
	 */
	protected static $providers = array();

	/**
	 * Instantiate and register a list of service providers.
	 *
	 * @param string[] $providerClasses Fully-qualified class names.
	 *
	 * @return void
	 */
	public static function register( array $providerClasses ) {
		foreach ( $providerClasses as $class ) {
			if ( ! class_exists( $class ) ) {
				continue;
			}

			$provider = new $class();

			if ( ! $provider instanceof ServiceProvider ) {
				continue;
			}

			$provider->register();
			self::$providers[] = $provider;
		}
	}

	/**
	 * Call boot() on every registered provider.
	 *
	 * @return void
	 */
	public static function bootAll() {
		foreach ( self::$providers as $provider ) {
			$provider->boot();
		}
	}
}
