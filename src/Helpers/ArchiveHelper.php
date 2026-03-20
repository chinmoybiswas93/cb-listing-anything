<?php

namespace CBListingAnything\Helpers;

use CrocoDevs\Database\QueryBuilder;

/**
 * Helpers for the listings archive block: filter parsing and query building.
 */
class ArchiveHelper {

	/**
	 * Parse and sanitize archive filter parameters from the request.
	 *
	 * @param bool        $is_category_archive Whether we're on a category taxonomy archive.
	 * @param bool        $is_tag_archive      Whether we're on a tag taxonomy archive.
	 * @param object|null $current_term         Queried term when on a taxonomy archive.
	 * @param string      $default_orderby      Default orderby from block attributes.
	 *
	 * @return array{
	 *     filter_cat: int[],
	 *     filter_tag: int[],
	 *     price_min: int,
	 *     price_max: int,
	 *     orderby: string,
	 *     paged: int
	 * }
	 */
	public static function parse_filters( $is_category_archive = false, $is_tag_archive = false, $current_term = null, $default_orderby = 'date' ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended

		$filter_cat = isset( $_GET['listing_category'] ) ? $_GET['listing_category'] : array();
		if ( ! is_array( $filter_cat ) ) {
			$filter_cat = $filter_cat ? array( absint( $filter_cat ) ) : array();
		} else {
			$filter_cat = array_map( 'absint', array_filter( $filter_cat ) );
		}

		if ( $is_category_archive && $current_term && empty( $filter_cat ) ) {
			$filter_cat = array( $current_term->term_id );
		}

		$filter_tag = isset( $_GET['listing_tag'] ) ? $_GET['listing_tag'] : array();
		if ( ! is_array( $filter_tag ) ) {
			$filter_tag = $filter_tag ? array( absint( $filter_tag ) ) : array();
		} else {
			$filter_tag = array_map( 'absint', array_filter( $filter_tag ) );
		}

		if ( $is_tag_archive && $current_term && empty( $filter_tag ) ) {
			$filter_tag = array( $current_term->term_id );
		}

		$price_min = isset( $_GET['price_min'] ) ? absint( preg_replace( '/[^0-9]/', '', wp_unslash( $_GET['price_min'] ) ) ) : 0;
		$price_max = isset( $_GET['price_max'] ) ? absint( preg_replace( '/[^0-9]/', '', wp_unslash( $_GET['price_max'] ) ) ) : 0;

		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : $default_orderby;
		$allowed = array( 'date', 'date_asc', 'title', 'price_asc', 'price_desc' );
		if ( ! in_array( $orderby, $allowed, true ) ) {
			$orderby = 'date';
		}

		$paged_get = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;
		if ( ! $paged_get && isset( $_GET['page'] ) ) {
			$paged_get = absint( $_GET['page'] );
		}
		if ( ! $paged_get && get_query_var( 'paged' ) ) {
			$paged_get = absint( get_query_var( 'paged' ) );
		}

		// phpcs:enable

		return array(
			'filter_cat' => $filter_cat,
			'filter_tag' => $filter_tag,
			'price_min'  => $price_min,
			'price_max'  => $price_max,
			'orderby'    => $orderby,
			'paged'      => max( 1, $paged_get ),
		);
	}

	/**
	 * Build a QueryBuilder from parsed filter values.
	 *
	 * @param array $filters  Output of {@see self::parse_filters()}.
	 * @param int   $per_page Posts per page.
	 *
	 * @return \WP_Query
	 */
	public static function build_query( array $filters, $per_page ) {
		$builder = QueryBuilder::make()
			->postType( crocodevs_config( 'post_type.slug' ) )
			->status( 'publish' )
			->perPage( $per_page )
			->page( $filters['paged'] );

		if ( ! empty( $filters['filter_cat'] ) ) {
			$builder->whenTax( crocodevs_config( 'taxonomies.category' ), 'term_id', $filters['filter_cat'] );
		}
		if ( ! empty( $filters['filter_tag'] ) ) {
			$builder->whenTax( crocodevs_config( 'taxonomies.tag' ), 'term_id', $filters['filter_tag'] );
		}
		if ( ! empty( $filters['filter_cat'] ) && ! empty( $filters['filter_tag'] ) ) {
			$current_args = $builder->toArgs();
			$builder->mergeArgs( array(
				'tax_query' => array_merge( array( 'relation' => 'AND' ), $current_args['tax_query'] ?? array() ),
			) );
		}

		$price_min = $filters['price_min'];
		$price_max = $filters['price_max'];
		if ( $price_min > 0 || $price_max > 0 ) {
			if ( $price_min > 0 && $price_max > 0 ) {
				$builder->whereMeta( '_listing_price', array( $price_min, $price_max ), 'BETWEEN', 'NUMERIC' );
			} elseif ( $price_min > 0 ) {
				$builder->whereMeta( '_listing_price', $price_min, '>=', 'NUMERIC' );
			} else {
				$builder->whereMeta( '_listing_price', $price_max, '<=', 'NUMERIC' );
			}
		}

		switch ( $filters['orderby'] ) {
			case 'date_asc':
				$builder->orderBy( 'date', 'ASC' );
				break;
			case 'title':
				$builder->orderBy( 'title', 'ASC' );
				break;
			case 'price_asc':
				$builder->orderBy( 'meta_value_num', 'ASC' )->mergeArgs( array( 'meta_key' => '_listing_price' ) );
				break;
			case 'price_desc':
				$builder->orderBy( 'meta_value_num', 'DESC' )->mergeArgs( array( 'meta_key' => '_listing_price' ) );
				break;
			default:
				$builder->orderBy( 'date', 'DESC' );
				break;
		}

		return $builder->get();
	}
}
