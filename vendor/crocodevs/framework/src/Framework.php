<?php

namespace CrocoDevs;

use CrocoDevs\Config\Config;
use CrocoDevs\Http\Router\Router;
use CrocoDevs\Support\ServiceProviderManager;

/**
 * Main CrocoDevs Framework Class.
 *
 * @package CrocoDevs
 */
class Framework {

	const VERSION = '1.0.0';

	/**
	 * @var string
	 */
	protected static $appPath = '';

	/**
	 * @var string
	 */
	protected static $pluginFile = '';

	/**
	 * @var bool
	 */
	protected static $bootstrapped = false;

	/**
	 * Bootstrap the framework.
	 *
	 * @param string $pluginPath Absolute path to the plugin root directory.
	 * @param array  $providers  Service provider class names.
	 *
	 * @return void
	 */
	public static function bootstrap( $pluginPath, array $providers = array() ) {
		if ( self::$bootstrapped ) {
			return;
		}

		self::$appPath = rtrim( $pluginPath, '/\\' );

		// Load configuration from framework and app config directories.
		self::loadConfiguration();

		// Allow providers to be passed explicitly or configured.
		if ( empty( $providers ) ) {
			$providers = (array) Config::get( 'app.providers', array() );
		}

		if ( ! empty( $providers ) ) {
			ServiceProviderManager::register( $providers );
			ServiceProviderManager::bootAll();
		}

		if ( Config::get( 'app.use_router', false ) ) {
			add_action( 'rest_api_init', array( self::class, 'registerRoutes' ) );
		}

		self::$bootstrapped = true;
	}

	/**
	 * Get the application base path.
	 *
	 * @param string $path Optional relative path.
	 *
	 * @return string
	 */
	public static function appPath( $path = '' ) {
		if ( '' === $path ) {
			return self::$appPath;
		}

		return self::$appPath . '/' . ltrim( $path, '/\\' );
	}

	/**
	 * Get a configuration value.
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public static function config( $key, $default = null ) {
		return Config::get( $key, $default );
	}

	/**
	 * Get the path to a view file.
	 *
	 * @param string $view Dot-notation or slash-separated view name.
	 *
	 * @return string
	 */
	public static function viewPath( $view ) {
		$view = str_replace( '.', '/', $view );

		return self::appPath( 'src/Views/' . $view . '.php' );
	}

	/**
	 * Get the URL to a plugin asset.
	 *
	 * @param string $path Relative asset path.
	 *
	 * @return string
	 */
	public static function assetUrl( $path ) {
		return plugins_url( $path, self::$appPath . '/plugin.php' );
	}

	/**
	 * @return string
	 */
	public static function version() {
		return self::VERSION;
	}

	/**
	 * @return bool
	 */
	public static function isBootstrapped() {
		return self::$bootstrapped;
	}

	/**
	 * Load the plugin's routes file and register all router-defined routes.
	 *
	 * Hooked into rest_api_init when app.use_router is enabled.
	 *
	 * @return void
	 */
	public static function registerRoutes() {
		$routesFile = self::appPath( 'routes/api.php' );

		if ( file_exists( $routesFile ) ) {
			Router::init();
			require_once $routesFile;
		}

		Router::registerRoutes();
	}

	/**
	 * Load configuration files from the framework and application.
	 *
	 * @return void
	 */
	protected static function loadConfiguration() {
		$config = array();

		$frameworkConfigPath = dirname( __DIR__ ) . '/config';
		if ( is_dir( $frameworkConfigPath ) ) {
			foreach ( glob( $frameworkConfigPath . '/*.php' ) as $file ) {
				$name            = basename( $file, '.php' );
				$config[ $name ] = require $file;
			}
		}

		$appConfigPath = self::$appPath . '/config';
		if ( is_dir( $appConfigPath ) ) {
			foreach ( glob( $appConfigPath . '/*.php' ) as $file ) {
				$name = basename( $file, '.php' );

				$appConfig = require $file;

				if ( isset( $config[ $name ] ) && is_array( $config[ $name ] ) && is_array( $appConfig ) ) {
					$config[ $name ] = array_replace_recursive( $config[ $name ], $appConfig );
				} else {
					$config[ $name ] = $appConfig;
				}
			}
		}

		Config::set( $config );
	}
}
