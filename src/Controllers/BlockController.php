<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Core\AbstractController;
use CBListingAnything\Models\ListingMeta as ListingMetaModel;
use CBListingAnything\Controllers\SettingsController;

class BlockController extends AbstractController {

	/**
	 * Initialize block hooks.
	 *
	 * @return void
	 */
	public function init()
	{
		add_action('init', array($this, 'register_blocks'));
		add_action('enqueue_block_editor_assets', array($this, 'localize_block_data'));
		add_action('enqueue_block_editor_assets', array($this, 'unregister_taxonomy_blocks'));
		add_filter('allowed_block_types_all', array($this, 'restrict_listing_details_block'), 10, 2);
	}

	/**
	 * Register plugin blocks.
	 *
	 * @return void
	 */
	public function register_blocks()
	{
		$blocks = array( 'listings-card', 'listing-details', 'related-listings', 'listing-search', 'categories-slider', 'listings-archive', 'listing-breadcrumb', 'listing-cards-slider', 'listing-user-dashboard' );

		foreach ( $blocks as $block ) {
			$block_dir = crocodevs_app_path( 'build/' . $block );

			if ( file_exists( $block_dir . '/block.json' ) ) {
				register_block_type( $block_dir );
			}
		}
	}

	/**
	 * Remove category and tag variations from core terms block.
	 *
	 * @return void
	 */
	public function unregister_taxonomy_blocks()
	{
		$script = <<<JS
		wp.domReady( function() {
			wp.blocks.unregisterBlockVariation( 'core/post-terms', 'cb_listing_category' );
			wp.blocks.unregisterBlockVariation( 'core/post-terms', 'cb_listing_tag' );
		} );
		JS;

		wp_register_script(
			'cb-listing-anything-unregister-variations',
			'',
			array('wp-blocks', 'wp-dom-ready'),
			CB_LISTING_ANYTHING_VERSION,
			true
		);
		wp_enqueue_script('cb-listing-anything-unregister-variations');
		wp_add_inline_script('cb-listing-anything-unregister-variations', $script);
	}

	/**
	 * Only allow Listing Details block in listing contexts.
	 *
	 * @param bool|string[] $allowed_blocks
	 * @param \WP_Block_Editor_Context $editor_context
	 * @return bool|string[]
	 */
	public function restrict_listing_details_block( $allowed_blocks, $editor_context ) {
		if ( 'core/edit-site' === $editor_context->name ) {
			return $allowed_blocks;
		}

		if ( isset( $editor_context->post ) && crocodevs_config( 'post_type.slug' ) === $editor_context->post->post_type ) {
			return $allowed_blocks;
		}

		$restricted = array(
			'cb-listing-anything/listing-details',
			'cb-listing-anything/related-listings',
		);

		if ( true === $allowed_blocks ) {
			$all = array_keys( \WP_Block_Type_Registry::get_instance()->get_all_registered() );
			return array_values( array_diff( $all, $restricted ) );
		}

		if ( is_array( $allowed_blocks ) ) {
			return array_values( array_diff( $allowed_blocks, $restricted ) );
		}

		return $allowed_blocks;
	}

	/**
	 * Provide taxonomy and enabled-fields data to block editor (for listings-card and listings-archive).
	 *
	 * @return void
	 */
	public function localize_block_data() {
		$terms = get_terms(
			array(
				'taxonomy'   => crocodevs_config( 'taxonomies.category' ),
				'hide_empty' => false,
			)
		);

		$options = array(
			array(
				'label' => __( 'All Categories', 'cb-listing-anything' ),
				'value' => 0,
			),
		);

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[] = array(
					'label' => $term->name,
					'value' => $term->term_id,
				);
			}
		}

		$enabled_fields = ListingMetaModel::normalize_enabled_fields(
			SettingsController::get( 'enabled_fields', null )
		);

		$data = array(
			'categories'     => $options,
			'enabledFields'   => array_values( $enabled_fields ),
		);

		wp_register_script( 'cb-listing-anything-editor-data', false, array(), CB_LISTING_ANYTHING_VERSION, false );
		wp_enqueue_script( 'cb-listing-anything-editor-data' );
		wp_add_inline_script(
			'cb-listing-anything-editor-data',
			'window.cbListingAnythingData = ' . wp_json_encode( $data ) . ';',
			'before'
		);
	}
}
