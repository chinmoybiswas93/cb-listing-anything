<?php

namespace CBListingAnything\Rest;

use WP_Post;
use WP_REST_Response;
use WP_Term;

/**
 * Decodes HTML entities in REST API responses for this plugin’s post type and taxonomies.
 *
 * WordPress may expose strings with entities (e.g. {@code &amp;} for {@code &}) in JSON;
 * clients then show literal entities unless decoded. Centralizing here keeps API output plain UTF-8.
 */
class RestHtmlEntityDecode {

	/**
	 * Register REST filters.
	 *
	 * WordPress uses the dynamic hook {@code rest_prepare_{$taxonomy}} for terms
	 * (see {@see WP_REST_Terms_Controller::prepare_item_for_response}), not {@code rest_prepare_term}.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_term_rest_filters' ), 20 );
		add_filter( 'rest_prepare_post', array( $this, 'prepare_post' ), 10, 3 );
	}

	/**
	 * Attach per-taxonomy term filters after config and taxonomies exist.
	 *
	 * @return void
	 */
	public function register_term_rest_filters() {
		$category_tax = crocodevs_config( 'taxonomies.category' );
		$tag_tax      = crocodevs_config( 'taxonomies.tag' );

		if ( is_string( $category_tax ) && $category_tax !== '' ) {
			add_filter( "rest_prepare_{$category_tax}", array( $this, 'prepare_term' ), 10, 3 );
		}

		if ( is_string( $tag_tax ) && $tag_tax !== '' && $tag_tax !== $category_tax ) {
			add_filter( "rest_prepare_{$tag_tax}", array( $this, 'prepare_term' ), 10, 3 );
		}
	}

	/**
	 * Decode HTML entities in a string (safe for JSON text fields).
	 *
	 * @param mixed $value String or non-string passthrough.
	 * @return mixed
	 */
	public static function decode_string( $value ) {
		if ( ! is_string( $value ) || $value === '' ) {
			return $value;
		}

		$flags = ENT_QUOTES;
		if ( defined( 'ENT_HTML5' ) ) {
			$flags |= ENT_HTML5;
		}

		// Repeat until stable (handles double-encoded entities in stored data).
		$out   = $value;
		$prev  = null;
		$tries = 0;
		while ( $prev !== $out && $tries < 5 ) {
			$prev = $out;
			$out  = html_entity_decode( $out, $flags, 'UTF-8' );
			++$tries;
		}

		return $out;
	}

	/**
	 * @param WP_REST_Response $response Response.
	 * @param WP_Term          $term     Term object.
	 * @param \WP_REST_Request $request  Request.
	 * @return WP_REST_Response
	 */
	public function prepare_term( $response, $term, $request ) {
		if ( ! $term instanceof WP_Term ) {
			return $response;
		}

		$data = $response->get_data();

		if ( isset( $data['name'] ) && is_string( $data['name'] ) ) {
			$data['name'] = self::decode_string( $data['name'] );
		}

		if ( isset( $data['description'] ) && is_string( $data['description'] ) ) {
			$data['description'] = self::decode_string( $data['description'] );
		}

		$response->set_data( $data );

		return $response;
	}

	/**
	 * @param WP_REST_Response $response Response.
	 * @param WP_Post          $post     Post object.
	 * @param \WP_REST_Request $request  Request.
	 * @return WP_REST_Response
	 */
	public function prepare_post( $response, $post, $request ) {
		if ( ! $post instanceof WP_Post ) {
			return $response;
		}

		if ( crocodevs_config( 'post_type.slug' ) !== $post->post_type ) {
			return $response;
		}

		$data = $response->get_data();

		foreach ( array( 'title', 'excerpt', 'content' ) as $field ) {
			if ( ! isset( $data[ $field ] ) || ! is_array( $data[ $field ] ) ) {
				continue;
			}
			foreach ( array( 'raw', 'rendered' ) as $sub ) {
				if ( isset( $data[ $field ][ $sub ] ) && is_string( $data[ $field ][ $sub ] ) ) {
					$data[ $field ][ $sub ] = self::decode_string( $data[ $field ][ $sub ] );
				}
			}
		}

		$response->set_data( $data );

		return $response;
	}
}
