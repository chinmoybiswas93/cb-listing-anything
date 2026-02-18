<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Config\PostType as PostTypeConfig;
use CBListingAnything\Core\AbstractController;
use CBListingAnything\Models\ListingMeta;
use WP_Post;

class MetaBoxController extends AbstractController {

	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_data' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( PostTypeConfig::POST_TYPE !== get_post_type() ) {
			return;
		}

		wp_enqueue_media();

		$css = '
			.cb-listing-meta-box { padding: 12px 0; }
			.cb-listing-meta-box__section {
				margin: 24px 0 12px;
				padding-bottom: 8px;
				border-bottom: 1px solid #e0e0e0;
				font-size: 14px;
				font-weight: 600;
				color: #1e1e1e;
			}
			.cb-listing-meta-box__section:first-child { margin-top: 0; }
			.cb-listing-meta-box__row {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 16px;
				margin-bottom: 16px;
			}
			.cb-listing-meta-box__row--full {
				grid-template-columns: 1fr;
			}
			.cb-listing-meta-box__row:last-child { margin-bottom: 0; }
			.cb-listing-meta-box__field label {
				display: block;
				font-weight: 600;
				margin-bottom: 6px;
				color: #1e1e1e;
				font-size: 13px;
			}
			.cb-listing-meta-box__field input[type="text"],
			.cb-listing-meta-box__field input[type="url"],
			.cb-listing-meta-box__field input[type="email"],
			.cb-listing-meta-box__field input[type="tel"],
			.cb-listing-meta-box__field input[type="time"] {
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
			.cb-listing-meta-box__checkboxes {
				display: flex;
				flex-wrap: wrap;
				gap: 4px 16px;
			}
			.cb-listing-meta-box__checkbox {
				display: flex;
				align-items: center;
				gap: 6px;
				font-weight: 400 !important;
				font-size: 13px;
				cursor: pointer;
			}
			.cb-listing-meta-box__checkbox input { margin: 0; }
			.cb-listing-gallery__preview {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
				gap: 8px;
				margin-bottom: 12px;
			}
			.cb-listing-gallery__item {
				position: relative;
				border-radius: 4px;
				overflow: hidden;
				border: 1px solid #ddd;
			}
			.cb-listing-gallery__item img {
				display: block;
				width: 100%;
				height: 100px;
				object-fit: cover;
			}
			.cb-listing-gallery__remove {
				position: absolute;
				top: 4px;
				right: 4px;
				width: 22px;
				height: 22px;
				border: none;
				background: rgba(0,0,0,0.6);
				color: #fff;
				border-radius: 50%;
				cursor: pointer;
				font-size: 14px;
				line-height: 1;
				padding: 0;
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.cb-listing-gallery__remove:hover { background: #d63638; }
			#listing_details .postbox-header .handlediv { display: none; }
			#listing_details .postbox-header { cursor: default; }
			#listing_details .inside { display: block !important; }
			@media (max-width: 782px) {
				.cb-listing-meta-box__row {
					grid-template-columns: 1fr;
				}
			}
		';

		wp_register_style( 'cb-listing-meta-box', false );
		wp_enqueue_style( 'cb-listing-meta-box' );
		wp_add_inline_style( 'cb-listing-meta-box', $css );

		$panel_js = "
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
		wp_add_inline_script( 'cb-listing-meta-box-panel', $panel_js );

		$gallery_js = "
			(function($) {
				var frame;
				var preview = $('#cb-listing-gallery-preview');
				var input = $('#listing_gallery');

				function updateInput() {
					var ids = [];
					preview.find('.cb-listing-gallery__item').each(function() {
						ids.push( $(this).data('id') );
					});
					input.val( ids.join(',') );
				}

				$('#cb-listing-gallery-add').on('click', function(e) {
					e.preventDefault();
					if ( frame ) { frame.open(); return; }
					frame = wp.media({
						title: '" . esc_js( __( 'Select Gallery Images', 'cb-listing-anything' ) ) . "',
						button: { text: '" . esc_js( __( 'Add to Gallery', 'cb-listing-anything' ) ) . "' },
						multiple: true,
						library: { type: 'image' }
					});
					frame.on('select', function() {
						var selection = frame.state().get('selection');
						selection.each(function(attachment) {
							var data = attachment.toJSON();
							var thumb = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
							preview.append(
								'<div class=\"cb-listing-gallery__item\" data-id=\"' + data.id + '\">' +
								'<img src=\"' + thumb + '\" alt=\"\" />' +
								'<button type=\"button\" class=\"cb-listing-gallery__remove\" aria-label=\"Remove\">&times;</button>' +
								'</div>'
							);
						});
						updateInput();
					});
					frame.open();
				});

				preview.on('click', '.cb-listing-gallery__remove', function(e) {
					e.preventDefault();
					$(this).closest('.cb-listing-gallery__item').remove();
					updateInput();
				});
			})(jQuery);
		";

		wp_register_script( 'cb-listing-gallery', '', array( 'jquery', 'media-upload' ), CB_LISTING_ANYTHING_VERSION, true );
		wp_enqueue_script( 'cb-listing-gallery' );
		wp_add_inline_script( 'cb-listing-gallery', $gallery_js );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'listing_details',
			__( 'Listing Details', 'cb-listing-anything' ),
			array( $this, 'render_listing_details_meta_box' ),
			PostTypeConfig::POST_TYPE,
			'normal',
			'high'
		);
	}

	public function render_listing_details_meta_box( $post ) {
		wp_nonce_field( 'listing_details_meta_box', 'listing_details_meta_box_nonce' );

		$values = array();
		foreach ( ListingMeta::fields() as $field ) {
			$values[ $field ] = get_post_meta( $post->ID, ListingMeta::key( $field ), true );

			if ( ListingMeta::is_array_field( $field ) && ! is_array( $values[ $field ] ) ) {
				$values[ $field ] = array();
			}

			if ( ! ListingMeta::is_array_field( $field ) && ! is_string( $values[ $field ] ) ) {
				$values[ $field ] = '';
			}
		}

		include CB_LISTING_ANYTHING_PLUGIN_DIR . 'src/Views/admin/meta-box-listing-details.php';
	}

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

		if ( PostTypeConfig::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		foreach ( ListingMeta::fields() as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = wp_unslash( $_POST[ $field ] );
				update_post_meta( $post_id, ListingMeta::key( $field ), ListingMeta::sanitize( $field, $value ) );
			} else {
				if ( ListingMeta::is_array_field( $field ) ) {
					update_post_meta( $post_id, ListingMeta::key( $field ), array() );
				} else {
					delete_post_meta( $post_id, ListingMeta::key( $field ) );
				}
			}
		}
	}
}
