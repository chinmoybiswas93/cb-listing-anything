<?php

namespace CrocoDevs\Http;

/**
 * Thin wrapper around WP_REST_Response for a consistent return API.
 */
class Response {

	/**
	 * Return a JSON success response.
	 *
	 * @param mixed $data
	 * @param int   $status
	 * @param array $headers
	 *
	 * @return \WP_REST_Response
	 */
	public static function json( $data = null, $status = 200, array $headers = array() ) {
		$response = new \WP_REST_Response( $data, $status );

		foreach ( $headers as $key => $value ) {
			$response->header( $key, $value );
		}

		return $response;
	}

	/**
	 * Return a success response.
	 *
	 * @param mixed $data
	 *
	 * @return \WP_REST_Response
	 */
	public static function success( $data = null ) {
		return self::json( $data, 200 );
	}

	/**
	 * Return a 201 created response.
	 *
	 * @param mixed $data
	 *
	 * @return \WP_REST_Response
	 */
	public static function created( $data = null ) {
		return self::json( $data, 201 );
	}

	/**
	 * Return a validation error response.
	 *
	 * @param array $errors
	 *
	 * @return \WP_REST_Response
	 */
	public static function validationError( array $errors ) {
		return self::json( array( 'errors' => $errors ), 422 );
	}

	/**
	 * Return a generic error response.
	 *
	 * @param string $message
	 * @param int    $status
	 *
	 * @return \WP_REST_Response
	 */
	public static function error( $message, $status = 400 ) {
		return self::json( array( 'error' => $message ), $status );
	}

	/**
	 * Return a 404 not found response.
	 *
	 * @param string $message
	 *
	 * @return \WP_REST_Response
	 */
	public static function notFound( $message = 'Not found' ) {
		return self::error( $message, 404 );
	}
}
