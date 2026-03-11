<?php

namespace CrocoDevs\Config;

/**
 * Simple configuration repository with dot-notation access.
 */
final class Config {

	/**
	 * @var array<string,mixed>
	 */
	private static $items = array();

	/**
	 * Replace the entire configuration array.
	 *
	 * @param array<string,mixed> $config
	 *
	 * @return void
	 */
	public static function set( array $config ) {
		self::$items = $config;
	}

	/**
	 * Get a configuration value using dot-notation.
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		if ( '' === $key ) {
			return self::$items;
		}

		$segments = explode( '.', $key );
		$value    = self::$items;

		foreach ( $segments as $segment ) {
			if ( is_array( $value ) && array_key_exists( $segment, $value ) ) {
				$value = $value[ $segment ];
			} else {
				return $default;
			}
		}

		return $value;
	}
}

