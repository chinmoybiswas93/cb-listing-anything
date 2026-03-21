<?php

namespace CrocoDevs;

use CrocoDevs\Http\Router\Router;
use CrocoDevs\Support\ServiceProviderManager;

/**
 * Main CrocoDevs Framework Class.
 *
 * Handles bootstrapping, configuration, path resolution, and route loading.
 * Configuration is stored directly on this class — no separate Config class needed.
 *
 * @package CrocoDevs
 */
class Framework
{

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
	 * @var array
	 */
	protected static $config = array();

	/**
	 * Bootstrap the framework.
	 *
	 * @param string $pluginPath Absolute path to the plugin root directory.
	 * @param array  $providers  Service provider class names.
	 *
	 * @return void
	 */
	public static function bootstrap($pluginPath, array $providers = array())
	{
		if (self::$bootstrapped) {
			return;
		}

		self::$appPath = rtrim($pluginPath, '/\\');

		self::loadConfiguration();

		if (empty($providers)) {
			$providers = (array) self::config('app.providers', array());
		}

		if (! empty($providers)) {
			ServiceProviderManager::register($providers);
			ServiceProviderManager::bootAll();
		}

		if (self::config('app.use_router', false)) {
			add_action('rest_api_init', array(self::class, 'registerRoutes'));
		}

		self::$bootstrapped = true;
	}

	/**
	 * Get a configuration value using dot-notation.
	 *
	 * @param string $key     Dot-separated key (e.g. 'app.api_prefix').
	 * @param mixed  $default Fallback value.
	 *
	 * @return mixed
	 */
	public static function config($key, $default = null)
	{
		if ('' === $key) {
			return self::$config;
		}

		$segments = explode('.', $key);
		$value    = self::$config;

		foreach ($segments as $segment) {
			if (is_array($value) && array_key_exists($segment, $value)) {
				$value = $value[$segment];
			} else {
				return $default;
			}
		}

		return $value;
	}

	/**
	 * Get the application base path.
	 *
	 * @param string $path Optional relative path.
	 *
	 * @return string
	 */
	public static function appPath($path = '')
	{
		if ('' === $path) {
			return self::$appPath;
		}

		return self::$appPath . '/' . ltrim($path, '/\\');
	}

	/**
	 * Get the path to a view file.
	 *
	 * @param string $view Dot-notation or slash-separated view name.
	 *
	 * @return string
	 */
	public static function viewPath($view)
	{
		$view = str_replace('.', '/', $view);

		return self::appPath('src/Views/' . $view . '.php');
	}

	/**
	 * Get the URL to a plugin asset.
	 *
	 * @param string $path Relative asset path.
	 *
	 * @return string
	 */
	public static function assetUrl($path)
	{
		return plugins_url($path, self::$appPath . '/plugin.php');
	}

	/**
	 * @return string
	 */
	public static function version()
	{
		return self::VERSION;
	}

	/**
	 * @return bool
	 */
	public static function isBootstrapped()
	{
		return self::$bootstrapped;
	}

	/**
	 * Load the plugin's routes file and register all router-defined routes.
	 *
	 * @return void
	 */
	public static function registerRoutes()
	{
		$routesFile = self::appPath('routes/api.php');

		if (file_exists($routesFile)) {
			Router::init();
			require_once $routesFile;
		}

		Router::registerRoutes();
	}

	/**
	 * Framework default configuration.
	 *
	 * @return array
	 */
	protected static function getDefaultConfig()
	{
		return array(
			'app' => array(
				'name'       => 'CrocoDevs App',
				'api_prefix' => 'crocodevs/v1',
				'use_router' => false,
				'providers'  => array(),
			),
		);
	}

	/**
	 * Load configuration from the plugin's config/ directory,
	 * merged over framework defaults.
	 *
	 * @return void
	 */
	protected static function loadConfiguration()
	{
		$config = self::getDefaultConfig();

		$appConfigPath = self::$appPath . '/config';
		if (is_dir($appConfigPath)) {
			foreach (glob($appConfigPath . '/*.php') as $file) {
				$name      = basename($file, '.php');
				$appConfig = require $file;

				if (isset($config[$name]) && is_array($config[$name]) && is_array($appConfig)) {
					$config[$name] = array_replace_recursive($config[$name], $appConfig);
				} else {
					$config[$name] = $appConfig;
				}
			}
		}

		self::$config = $config;
	}
}
