<?php
/**
 * CrocoDevs global helper functions.
 *
 * Loaded automatically via Composer "files" autoload.
 *
 * @package CrocoDevs\Support
 */

use CrocoDevs\Config\Config;
use CrocoDevs\Framework;
use CrocoDevs\Container\ServiceManager;
use CrocoDevs\Validation\Validator;

if ( ! function_exists( 'crocodevs_app_path' ) ) {
	/**
	 * Get an absolute path inside the plugin.
	 *
	 * @param string $path Relative path from the plugin root.
	 *
	 * @return string
	 */
	function crocodevs_app_path( $path = '' ) {
		return Framework::appPath( $path );
	}
}

if ( ! function_exists( 'crocodevs_view_path' ) ) {
	/**
	 * Get the absolute path to a view template.
	 *
	 * @param string $view View name (dot or slash separated, without .php).
	 *
	 * @return string
	 */
	function crocodevs_view_path( $view ) {
		return Framework::viewPath( $view );
	}
}

if ( ! function_exists( 'crocodevs_asset_url' ) ) {
	/**
	 * Get the URL to a plugin asset.
	 *
	 * @param string $path Relative asset path.
	 *
	 * @return string
	 */
	function crocodevs_asset_url( $path ) {
		return Framework::assetUrl( $path );
	}
}

if ( ! function_exists( 'crocodevs_resolve' ) ) {
	/**
	 * Resolve a service from the container.
	 *
	 * @param string $id   Service identifier.
	 * @param mixed  ...$args Optional arguments.
	 *
	 * @return mixed|null
	 */
	function crocodevs_resolve( $id, ...$args ) {
		return ServiceManager::get( $id, ...$args );
	}
}

if ( ! function_exists( 'crocodevs_config' ) ) {
	/**
	 * Get a configuration value from the CrocoDevs config repository.
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	function crocodevs_config( $key, $default = null ) {
		return Config::get( $key, $default );
	}
}

if ( ! function_exists( 'crocodevs_validate' ) ) {
	/**
	 * Validate data against a set of rules.
	 *
	 * @param array $data  Input data.
	 * @param array $rules Validation rules.
	 *
	 * @return \CrocoDevs\Validation\ValidationResult
	 */
	function crocodevs_validate( array $data, array $rules ) {
		return Validator::make( $data, $rules );
	}
}
