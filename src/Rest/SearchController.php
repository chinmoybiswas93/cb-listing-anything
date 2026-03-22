<?php

namespace CBListingAnything\Rest;

use CBListingAnything\Controllers\SettingsController;
use CrocoDevs\Database\QueryBuilder;
use CrocoDevs\Http\Response;
use CrocoDevs\Validation\Validator;
use WP_REST_Request;
use WP_REST_Response;

class SearchController extends AbstractRestController {

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( $this->rest_namespace(), '/search', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'search_listings' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'keyword'  => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'default'           => '',
				),
				'category' => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'default'           => 0,
				),
			),
		) );
	}

	/**
	 * Search listings endpoint callback.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function search_listings( WP_REST_Request $request ) {
		$data = $request->get_params();

		$validation = Validator::make( $data, array(
			'keyword'  => 'nullable|string|max:200',
			'category' => 'nullable|integer',
		) );

		if ( $validation->fails() ) {
			return Response::validationError( $validation->errors() );
		}

		$validated = $validation->validated();
		$keyword   = isset( $validated['keyword'] ) ? $validated['keyword'] : '';
		$category  = isset( $validated['category'] ) ? $validated['category'] : 0;

		if ( empty( $keyword ) && empty( $category ) ) {
			return new WP_REST_Response( array(), 200 );
		}

		$query = QueryBuilder::make()
			->postType( crocodevs_config( 'post_type.slug' ) )
			->status( 'publish' )
			->perPage( 8 )
			->whenKeyword( (string) $keyword )
			->whenTax( crocodevs_config( 'taxonomies.category' ), 'term_id', $category )
			->get();

		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id   = get_the_ID();
				$thumb_url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
				$location  = get_post_meta( $post_id, '_listing_location', true );
				$price     = get_post_meta( $post_id, '_listing_price', true );
				$cats      = get_the_terms( $post_id, crocodevs_config( 'taxonomies.category' ) );
				$cat_name  = '';

				if ( $cats && ! is_wp_error( $cats ) ) {
					$cat_name = $cats[0]->name;
				}

				$results[] = array(
					'id'        => $post_id,
					'title'     => RestHtmlEntityDecode::decode_string( get_the_title() ),
					'url'       => get_permalink(),
					'thumbnail' => $thumb_url ? $thumb_url : '',
					'location'  => $location ? RestHtmlEntityDecode::decode_string( $location ) : '',
					'price'     => $price ? SettingsController::currency_symbol() . $price : '',
					'category'  => $cat_name ? RestHtmlEntityDecode::decode_string( $cat_name ) : '',
				);
			}
			wp_reset_postdata();
		}

		return new WP_REST_Response( $results, 200 );
	}
}
