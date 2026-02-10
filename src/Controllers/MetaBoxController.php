<?php

namespace ListingItems\Controllers;

use ListingItems\Models\ListingMeta;
use WP_Post;

class MetaBoxController {

	/**
	 * Initialize meta box hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_data' ) );
	}

	/**
	 * Register listing meta box.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'listing_details',
			__( 'Listing Details', 'listing-items' ),
			array( $this, 'render_listing_details_meta_box' ),
			'listing',
			'normal',
			'high'
		);
	}

	/**
	 * Render the listing details meta box.
	 *
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_listing_details_meta_box( $post ) {
		wp_nonce_field( 'listing_details_meta_box', 'listing_details_meta_box_nonce' );

		$values = array();
		foreach ( ListingMeta::fields() as $field ) {
			$values[ $field ] = get_post_meta( $post->ID, ListingMeta::key( $field ), true );
		}

		include LISTING_ITEMS_PLUGIN_DIR . 'src/Views/admin/meta-box-listing-details.php';
	}

	/**
	 * Persist listing meta fields.
	 *
	 * @param int $post_id Current post ID.
	 * @return void
	 */
	public function save_meta_data( $post_id ) {
		if ( ! isset( $_POST['listing_details_meta_box_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['listing_details_meta_box_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'listing_details_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( 'listing' !== get_post_type( $post_id ) ) {
			return;
		}

		foreach ( ListingMeta::fields() as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = wp_unslash( $_POST[ $field ] );
				update_post_meta( $post_id, ListingMeta::key( $field ), ListingMeta::sanitize( $field, $value ) );
			} else {
				delete_post_meta( $post_id, ListingMeta::key( $field ) );
			}
		}
	}
}
