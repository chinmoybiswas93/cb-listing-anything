<?php

namespace CrocoDevs\Container;

/**
 * Lightweight service container / registry.
 *
 * Provides singleton and factory registration without requiring
 * a heavy DI framework. All methods are static so they can be
 * called from anywhere (service providers, controllers, etc.).
 *
 * @package CrocoDevs\Container
 */
final class ServiceManager {

	/**
	 * @var array<string, callable>
	 */
	protected static $singletons = array();

	/**
	 * @var array<string, mixed>
	 */
	protected static $instances = array();

	/**
	 * @var array<string, callable>
	 */
	protected static $factories = array();

	/**
	 * Store a concrete instance directly.
	 *
	 * @param string $id       Identifier.
	 * @param mixed  $instance The instance to store.
	 *
	 * @return void
	 */
	public static function instance( $id, $instance ) {
		self::$instances[ $id ] = $instance;
	}

	/**
	 * Register a singleton factory (resolved once, then cached).
	 *
	 * @param string   $id      Identifier.
	 * @param callable $factory Factory returning the instance.
	 *
	 * @return void
	 */
	public static function singleton( $id, callable $factory ) {
		self::$singletons[ $id ] = $factory;
	}

	/**
	 * Register a factory (new instance every time).
	 *
	 * @param string   $id      Identifier.
	 * @param callable $factory Factory returning a new instance.
	 *
	 * @return void
	 */
	public static function register( $id, callable $factory ) {
		self::$factories[ $id ] = $factory;
	}

	/**
	 * Resolve a service by identifier.
	 *
	 * @param string $id      Identifier.
	 * @param mixed  ...$args Optional arguments forwarded to factory.
	 *
	 * @return mixed|null
	 */
	public static function get( $id, ...$args ) {
		if ( isset( self::$instances[ $id ] ) ) {
			return self::$instances[ $id ];
		}

		if ( isset( self::$singletons[ $id ] ) ) {
			self::$instances[ $id ] = ( self::$singletons[ $id ] )( ...$args );

			return self::$instances[ $id ];
		}

		if ( isset( self::$factories[ $id ] ) ) {
			return ( self::$factories[ $id ] )( ...$args );
		}

		return null;
	}

	/**
	 * Check whether a service has been registered.
	 *
	 * @param string $id Identifier.
	 *
	 * @return bool
	 */
	public static function has( $id ) {
		return isset( self::$instances[ $id ] )
			|| isset( self::$singletons[ $id ] )
			|| isset( self::$factories[ $id ] );
	}
}
