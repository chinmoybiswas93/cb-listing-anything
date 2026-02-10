<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Listing_Items_Blocks {

	public function init() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'localize_block_data' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'unregister_taxonomy_blocks' ) );
	}

	public function register_blocks() {
		$block_dir = LISTING_ITEMS_PLUGIN_DIR . 'build/listings-card';

		if ( ! file_exists( $block_dir . '/block.json' ) ) {
			return;
		}

		register_block_type( $block_dir );
	}

	public function unregister_taxonomy_blocks() {
		$script = <<<JS
wp.domReady( function() {
	wp.blocks.unregisterBlockVariation( 'core/post-terms', 'listing_category' );
	wp.blocks.unregisterBlockVariation( 'core/post-terms', 'listing_tag' );
} );
JS;

		wp_register_script(
			'listing-items-unregister-variations',
			'',
			array( 'wp-blocks', 'wp-dom-ready' ),
			LISTING_ITEMS_VERSION,
			true
		);
		wp_enqueue_script( 'listing-items-unregister-variations' );
		wp_add_inline_script( 'listing-items-unregister-variations', $script );
	}

	public function localize_block_data() {
		$terms = get_terms( array(
			'taxonomy'   => 'listing_category',
			'hide_empty' => false,
		) );

		$options = array(
			array( 'label' => __( 'All Categories', 'listing-items' ), 'value' => 0 ),
		);

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[] = array( 'label' => $term->name, 'value' => $term->term_id );
			}
		}

		wp_add_inline_script(
			'listing-items-listings-card-editor-script',
			'window.listingItemsData = ' . wp_json_encode( array( 'categories' => $options ) ) . ';',
			'before'
		);
	}
}
