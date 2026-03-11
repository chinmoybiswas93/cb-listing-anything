<?php

namespace CrocoDevs;

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

		if ( ! empty( $providers ) ) {
			ServiceProviderManager::register( $providers );
			ServiceProviderManager::bootAll();
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
}
