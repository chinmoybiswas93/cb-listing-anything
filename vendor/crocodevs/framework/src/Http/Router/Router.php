<?php

namespace CrocoDevs\Http\Router;

use CrocoDevs\Framework;
use CrocoDevs\Http\Request;

/**
 * Minimal REST API router for WordPress.
 *
 * Collects route definitions and registers them via register_rest_route().
 */
class Router {

	/**
	 * @var array[] Collected route definitions.
	 */
	protected static $routes = array();

	/**
	 * Initialize the router (placeholder for future middleware/prefix logic).
	 *
	 * @return void
	 */
	public static function init() {
	}

	/**
	 * Register a GET route.
	 *
	 * @param string          $path
	 * @param callable|array  $handler  Callable or [Controller::class, 'method'].
	 * @param array           $args     WP REST arg definitions.
	 *
	 * @return void
	 */
	public static function get( $path, $handler, array $args = array() ) {
		self::addRoute( 'GET', $path, $handler, $args );
	}

	/**
	 * Register a POST route.
	 *
	 * @param string          $path
	 * @param callable|array  $handler
	 * @param array           $args
	 *
	 * @return void
	 */
	public static function post( $path, $handler, array $args = array() ) {
		self::addRoute( 'POST', $path, $handler, $args );
	}

	/**
	 * Register a PUT route.
	 *
	 * @param string          $path
	 * @param callable|array  $handler
	 * @param array           $args
	 *
	 * @return void
	 */
	public static function put( $path, $handler, array $args = array() ) {
		self::addRoute( 'PUT', $path, $handler, $args );
	}

	/**
	 * Register a DELETE route.
	 *
	 * @param string          $path
	 * @param callable|array  $handler
	 * @param array           $args
	 *
	 * @return void
	 */
	public static function delete( $path, $handler, array $args = array() ) {
		self::addRoute( 'DELETE', $path, $handler, $args );
	}

	/**
	 * Store a route definition.
	 *
	 * @param string          $method
	 * @param string          $path
	 * @param callable|array  $handler
	 * @param array           $args
	 *
	 * @return void
	 */
	protected static function addRoute( $method, $path, $handler, array $args ) {
		self::$routes[] = array(
			'method'  => $method,
			'path'    => '/' . ltrim( $path, '/' ),
			'handler' => $handler,
			'args'    => $args,
		);
	}

	/**
	 * Register all collected routes with WordPress.
	 *
	 * Called inside rest_api_init.
	 *
	 * @return void
	 */
	public static function registerRoutes() {
		$namespace = Framework::config( 'app.api_prefix', 'crocodevs/v1' );

		foreach ( self::$routes as $route ) {
			$handler = $route['handler'];

			$callback = self::wrapHandler( $handler );

			register_rest_route( $namespace, $route['path'], array(
				'methods'             => $route['method'],
				'callback'            => $callback,
				'permission_callback' => '__return_true',
				'args'                => $route['args'],
			) );
		}
	}

	/**
	 * Wrap a handler so it receives a CrocoDevs Request instead of WP_REST_Request.
	 *
	 * @param callable|array $handler
	 *
	 * @return callable
	 */
	protected static function wrapHandler( $handler ) {
		return function ( \WP_REST_Request $wp_request ) use ( $handler ) {
			$request = new Request( $wp_request );

			if ( is_array( $handler ) && is_string( $handler[0] ) && class_exists( $handler[0] ) ) {
				$instance = new $handler[0]();

				return call_user_func( array( $instance, $handler[1] ), $request );
			}

			return call_user_func( $handler, $request );
		};
	}

	/**
	 * Get all registered routes (for inspection/testing).
	 *
	 * @return array[]
	 */
	public static function getRoutes() {
		return self::$routes;
	}
}
