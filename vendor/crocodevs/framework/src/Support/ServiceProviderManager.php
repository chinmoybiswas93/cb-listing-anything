<?php

namespace CrocoDevs\Support;

/**
 * Manages the lifecycle of service providers with WordPress hook awareness.
 *
 * Providers may declare WordPress hooks that control when they are registered.
 * Providers without hooks are registered immediately.
 *
 * @package CrocoDevs\Support
 */
final class ServiceProviderManager {

	/**
	 * Class names of all queued providers.
	 *
	 * @var string[]
	 */
	protected static $providers = array();

	/**
	 * Provider instances that have been registered (keyed by class name).
	 *
	 * @var array<string, ServiceProvider>
	 */
	protected static $registered = array();

	/**
	 * Whether providers have been booted.
	 *
	 * @var bool
	 */
	protected static $booted = false;

	/**
	 * Accept provider class names and schedule their registration.
	 *
	 * Providers that return hooks() are deferred to those WordPress hooks.
	 * Providers with an empty hooks() array are registered immediately.
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

			self::$providers[] = $class;

			$tmp = new $class();

			if ( ! $tmp instanceof ServiceProvider ) {
				continue;
			}

			$hooks = $tmp->hooks();

			if ( empty( $hooks ) ) {
				self::initializeProvider( $class );
				continue;
			}

			foreach ( $hooks as $hook => $priority ) {
				add_action( $hook, function () use ( $class ) {
					self::initializeProvider( $class );
				}, $priority );
			}
		}

		add_action( 'wp_loaded', array( self::class, 'bootProviders' ), 999 );
	}

	/**
	 * Instantiate and register a single provider (idempotent).
	 *
	 * @param string $class Fully-qualified class name.
	 *
	 * @return void
	 */
	public static function initializeProvider( $class ) {
		if ( isset( self::$registered[ $class ] ) ) {
			return;
		}

		$provider = new $class();

		if ( ! $provider instanceof ServiceProvider ) {
			return;
		}

		$provider->register();
		self::$registered[ $class ] = $provider;

		if ( self::$booted ) {
			$provider->boot();
		}
	}

	/**
	 * Boot all registered providers.
	 *
	 * Hooked into wp_loaded so all deferred providers have been registered.
	 *
	 * @return void
	 */
	public static function bootProviders() {
		if ( self::$booted ) {
			return;
		}

		foreach ( self::$registered as $provider ) {
			$provider->boot();
		}

		self::$booted = true;
	}

	/**
	 * Alias for bootProviders() — used by Framework::bootstrap().
	 *
	 * @return void
	 */
	public static function bootAll() {
		self::bootProviders();
	}
}
