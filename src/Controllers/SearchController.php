<?php

namespace CBListingAnything\Controllers;

use WP_REST_Request;
use WP_REST_Response;

class SearchController {

	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route( 'cb-listing-anything/v1', '/search', array(
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

		register_rest_route( 'cb-listing-anything/v1', '/categories', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_categories' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function search_listings( WP_REST_Request $request ) {
		$keyword  = $request->get_param( 'keyword' );
		$category = $request->get_param( 'category' );

		if ( empty( $keyword ) && empty( $category ) ) {
			return new WP_REST_Response( array(), 200 );
		}

		$args = array(
			'post_type'      => 'listing',
			'post_status'    => 'publish',
			'posts_per_page' => 8,
		);

		if ( ! empty( $keyword ) ) {
			$args['s'] = $keyword;
		}

		if ( ! empty( $category ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'listing_category',
					'field'    => 'term_id',
					'terms'    => $category,
				),
			);
		}

		$query   = new \WP_Query( $args );
		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id   = get_the_ID();
				$thumb_url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
				$location  = get_post_meta( $post_id, '_listing_location', true );
				$price     = get_post_meta( $post_id, '_listing_price', true );
				$cats      = get_the_terms( $post_id, 'listing_category' );
				$cat_name  = '';

				if ( $cats && ! is_wp_error( $cats ) ) {
					$cat_name = $cats[0]->name;
				}

				$results[] = array(
					'id'        => $post_id,
					'title'     => html_entity_decode( get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'url'       => get_permalink(),
					'thumbnail' => $thumb_url ? $thumb_url : '',
					'location'  => $location ? html_entity_decode( $location, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '',
					'price'     => $price ? SettingsController::currency_symbol() . $price : '',
					'category'  => $cat_name ? html_entity_decode( $cat_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '',
				);
			}
			wp_reset_postdata();
		}

		return new WP_REST_Response( $results, 200 );
	}

	public function get_categories( WP_REST_Request $request ) {
		$terms = get_terms( array(
			'taxonomy'   => 'listing_category',
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
