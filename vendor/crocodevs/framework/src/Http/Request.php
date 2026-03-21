<?php

namespace CrocoDevs\Http;

/**
 * Thin wrapper around WP_REST_Request for a cleaner controller API.
 */
class Request {

	/**
	 * @var \WP_REST_Request
	 */
	protected $wp;

	public function __construct( \WP_REST_Request $wp_request ) {
		$this->wp = $wp_request;
	}

	/**
	 * Get a single parameter (merged from URL, query, and body).
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$value = $this->wp->get_param( $key );

		return null !== $value ? $value : $default;
	}

	/**
	 * @return array
	 */
	public function all() {
		return $this->wp->get_params();
	}

	/**
	 * @param string[] $keys
	 *
	 * @return array
	 */
	public function only( array $keys ) {
		return array_intersect_key( $this->all(), array_flip( $keys ) );
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has( $key ) {
		return null !== $this->wp->get_param( $key );
	}

	/**
	 * @return string
	 */
	public function method() {
		return $this->wp->get_method();
	}

	/**
	 * Get the underlying WP_REST_Request.
	 *
	 * @return \WP_REST_Request
	 */
	public function wpRequest() {
		return $this->wp;
	}
}
