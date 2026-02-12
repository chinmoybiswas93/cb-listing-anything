<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Models\ListingMeta;
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	public function enqueue_admin_styles( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( 'listing' !== get_post_type() ) {
			return;
		}

		$css = '
			.cb-listing-meta-box { padding: 12px 0; }
			.cb-listing-meta-box__row {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 16px;
				margin-bottom: 16px;
			}
			.cb-listing-meta-box__row:last-child { margin-bottom: 0; }
			.cb-listing-meta-box__field label {
				display: block;
				font-weight: 600;
				margin-bottom: 6px;
				color: #1e1e1e;
			}
			.cb-listing-meta-box__field input {
				width: 100%;
				padding: 8px 12px;
				border: 1px solid #8c8f94;
				border-radius: 4px;
				box-sizing: border-box;
				font-size: 14px;
				line-height: 1.4;
				transition: border-color 0.15s ease;
			}
			.cb-listing-meta-box__field input:focus {
				border-color: #2271b1;
				box-shadow: 0 0 0 1px #2271b1;
				outline: none;
			}
			@media (max-width: 782px) {
				.cb-listing-meta-box__row {
					grid-template-columns: 1fr;
				}
			}
		';

		$css .= '
			#listing_details .postbox-header .handlediv { display: none; }
			#listing_details .postbox-header { cursor: default; }
			#listing_details .inside { display: block !important; }
		';

		wp_register_style( 'cb-listing-meta-box', false );
		wp_enqueue_style( 'cb-listing-meta-box' );
		wp_add_inline_style( 'cb-listing-meta-box', $css );

		$js = "
			wp.domReady( function() {
				var panel = wp.data.select( 'core/edit-post' );
				if ( panel && panel.isEditorPanelOpened ) {
					var panelName = 'meta-box-listing_details';
					if ( ! panel.isEditorPanelOpened( panelName ) ) {
						wp.data.dispatch( 'core/edit-post' ).toggleEditorPanelOpened( panelName );
					}
				}
			} );
		";

		wp_register_script( 'cb-listing-meta-box-panel', '', array( 'wp-dom-ready', 'wp-data', 'wp-edit-post' ), CB_LISTING_ANYTHING_VERSION, true );
		wp_enqueue_script( 'cb-listing-meta-box-panel' );
		wp_add_inline_script( 'cb-listing-meta-box-panel', $js );
	}

	/**
	 * Register listing meta box.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'listing_details',
			__( 'Listing Details', 'cb-listing-anything' ),
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

		include CB_LISTING_ANYTHING_PLUGIN_DIR . 'src/Views/admin/meta-box-listing-details.php';
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
