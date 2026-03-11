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
	 * Create a new builder instance.
	 *
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
	 * Set ordering.
	 *
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
	 * Exclude specific post IDs.
	 *
	 * @param int|int[] $ids
	 *
	 * @return $this
	 */
	public function exclude( $ids ) {
		$this->args['post__not_in'] = (array) $ids;

		return $this;
	}

	/**
	 * Include only specific post IDs.
	 *
	 * @param int|int[] $ids
	 *
	 * @return $this
	 */
	public function include( $ids ) {
		$this->args['post__in'] = (array) $ids;

		return $this;
	}

	/**
	 * Filter by author ID.
	 *
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
	 * Merge additional WP_Query args into the builder.
	 *
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
	 * Get the raw WP_Query args.
	 *
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
