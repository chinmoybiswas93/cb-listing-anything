<?php

namespace CBListingAnything\Rest;

use CBListingAnything\Config\Taxonomies as TaxonomiesConfig;
use WP_REST_Request;
use WP_REST_Response;

class TermController {

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( 'cb-listing-anything/v1', '/categories', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_categories' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Get listing categories endpoint callback.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_categories( WP_REST_Request $request ) {
		$terms = get_terms( array(
			'taxonomy'   => TaxonomiesConfig::CATEGORY_TAXONOMY,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		if ( is_wp_error( $terms ) ) {
			return new WP_REST_Response( array(), 200 );
		}

		$flat = array();

		foreach ( $terms as $term ) {
			$flat[] = array(
				'id'     => $term->term_id,
				'name'   => $term->name,
				'parent' => $term->parent,
				'count'  => $term->count,
			);
		}

		return new WP_REST_Response( $flat, 200 );
	}
}
