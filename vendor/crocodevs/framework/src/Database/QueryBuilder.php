<?php

namespace CrocoDevs\Database;

/**
 * Minimal fluent query builder wrapping WP_Query.
 *
 * @package CrocoDevs\Database
 */
class QueryBuilder {

	/**
	 * @var array
	 */
	protected $args = array();

	/**
	 * @param array $base Optional base WP_Query args.
	 *
	 * @return static
	 */
	public static function make( array $base = array() ) {
		$builder       = new static();
		$builder->args = $base;

		return $builder;
	}

	/**
	 * @param string|string[] $post_type
	 *
	 * @return $this
	 */
	public function postType( $post_type ) {
		$this->args['post_type'] = $post_type;

		return $this;
	}

	/**
	 * @param string|string[] $status
	 *
	 * @return $this
	 */
	public function status( $status ) {
		$this->args['post_status'] = $status;

		return $this;
	}

	/**
	 * @param int $per_page
	 *
	 * @return $this
	 */
	public function perPage( $per_page ) {
		$this->args['posts_per_page'] = (int) $per_page;

		return $this;
	}

	/**
	 * @param int $page
	 *
	 * @return $this
	 */
	public function page( $page ) {
		$this->args['paged'] = max( 1, (int) $page );

		return $this;
	}

	/**
	 * Add a search keyword (only when non-empty).
	 *
	 * @param string $keyword
	 *
	 * @return $this
	 */
	public function whenKeyword( $keyword ) {
		if ( '' !== (string) $keyword ) {
			$this->args['s'] = $keyword;
		}

		return $this;
	}

	/**
	 * Add a taxonomy filter (only when terms are present).
	 *
	 * @param string     $taxonomy
	 * @param string     $field    Field type (term_id, slug, name).
	 * @param int|int[]  $terms
	 *
	 * @return $this
	 */
	public function whenTax( $taxonomy, $field, $terms ) {
		if ( empty( $terms ) ) {
			return $this;
		}

		if ( ! isset( $this->args['tax_query'] ) || ! is_array( $this->args['tax_query'] ) ) {
			$this->args['tax_query'] = array();
		}

		$this->args['tax_query'][] = array(
			'taxonomy' => $taxonomy,
			'field'    => $field,
			'terms'    => $terms,
		);

		return $this;
	}

	/**
	 * Add a meta query clause.
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param string $compare
	 * @param string $type
	 *
	 * @return $this
	 */
	public function whereMeta( $key, $value, $compare = '=', $type = 'CHAR' ) {
		if ( ! isset( $this->args['meta_query'] ) || ! is_array( $this->args['meta_query'] ) ) {
			$this->args['meta_query'] = array();
		}

		$this->args['meta_query'][] = array(
			'key'     => $key,
			'value'   => $value,
			'compare' => $compare,
			'type'    => $type,
		);

		return $this;
	}

	/**
	 * Add a BETWEEN meta query clause — useful for price/date ranges.
	 *
	 * @param string $key  Meta key.
	 * @param mixed  $min  Minimum value.
	 * @param mixed  $max  Maximum value.
	 * @param string $type Compare type (NUMERIC, DECIMAL, DATE, etc.).
	 *
	 * @return $this
	 */
	public function whereMetaBetween( $key, $min, $max, $type = 'NUMERIC' ) {
		if ( ! isset( $this->args['meta_query'] ) || ! is_array( $this->args['meta_query'] ) ) {
			$this->args['meta_query'] = array();
		}

		$this->args['meta_query'][] = array(
			'key'     => $key,
			'value'   => array( $min, $max ),
			'compare' => 'BETWEEN',
			'type'    => $type,
		);

		return $this;
	}

	/**
	 * @param int[] $ids
	 *
	 * @return $this
	 */
	public function whereAuthorIn( array $ids ) {
		$this->args['author__in'] = array_map( 'intval', $ids );

		return $this;
	}

	/**
	 * @param int[] $ids
	 *
	 * @return $this
	 */
	public function whereAuthorNotIn( array $ids ) {
		$this->args['author__not_in'] = array_map( 'intval', $ids );

		return $this;
	}

	/**
	 * @param array $date_query A WP_Date_Query-compatible array.
	 *
	 * @return $this
	 */
	public function dateQuery( array $date_query ) {
		$this->args['date_query'] = $date_query;

		return $this;
	}

	/**
	 * @param string $meta_key
	 * @param string $order
	 * @param string $type     NUMERIC, DECIMAL, DATE, etc.
	 *
	 * @return $this
	 */
	public function orderByMeta( $meta_key, $order = 'DESC', $type = 'NUMERIC' ) {
		$this->args['meta_key']  = $meta_key;
		$this->args['orderby']   = 'meta_value_num' === $type ? 'meta_value_num' : 'meta_value';
		$this->args['order']     = $order;

		return $this;
	}

	/**
	 * @param string $orderby
	 * @param string $order
	 *
	 * @return $this
	 */
	public function orderBy( $orderby, $order = 'DESC' ) {
		$this->args['orderby'] = $orderby;
		$this->args['order']   = $order;

		return $this;
	}

	/**
	 * @param int|int[] $ids
	 *
	 * @return $this
	 */
	public function exclude( $ids ) {
		$this->args['post__not_in'] = (array) $ids;

		return $this;
	}

	/**
	 * @param int|int[] $ids
	 *
	 * @return $this
	 */
	public function include( $ids ) {
		$this->args['post__in'] = (array) $ids;

		return $this;
	}

	/**
	 * @param int $id
	 *
	 * @return $this
	 */
	public function author( $id ) {
		$this->args['author'] = (int) $id;

		return $this;
	}

	/**
	 * Set the fields to return (e.g. 'ids', 'id=>parent').
	 *
	 * @param string $fields
	 *
	 * @return $this
	 */
	public function fields( $fields ) {
		$this->args['fields'] = $fields;

		return $this;
	}

	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	public function mergeArgs( array $args ) {
		$this->args = array_merge( $this->args, $args );

		return $this;
	}

	/**
	 * Set no_found_rows optimization (skip pagination counting).
	 *
	 * @param bool $no_found_rows
	 *
	 * @return $this
	 */
	public function noFoundRows( $no_found_rows = true ) {
		$this->args['no_found_rows'] = $no_found_rows;

		return $this;
	}

	/**
	 * @return array
	 */
	public function toArgs() {
		return $this->args;
	}

	/**
	 * Execute the query and return a WP_Query.
	 *
	 * @return \WP_Query
	 */
	public function get() {
		return new \WP_Query( $this->args );
	}
}
