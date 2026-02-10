<?php

namespace ListingItems\Core;

use ListingItems\Controllers\BlockController;
use ListingItems\Controllers\MetaBoxController;
use ListingItems\Controllers\PostTypeController;
use ListingItems\Controllers\TaxonomyController;

class Plugin {

	/**
	 * Plugin singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Post type controller.
	 *
	 * @var PostTypeController
	 */
	private $post_type_controller;

	/**
	 * Taxonomy controller.
	 *
	 * @var TaxonomyController
	 */
	private $taxonomy_controller;

	/**
	 * Meta box controller.
	 *
	 * @var MetaBoxController
	 */
	private $meta_box_controller;

	/**
	 * Block controller.
	 *
	 * @var BlockController
	 */
	private $block_controller;

	/**
	 * Get plugin singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 */
	private function __construct() {
		$this->post_type_controller = new PostTypeController();
		$this->taxonomy_controller  = new TaxonomyController();
		$this->meta_box_controller  = new MetaBoxController();
		$this->block_controller     = new BlockController();
	}

	/**
	 * Register runtime hooks.
	 *
	 * @return void
	 */
	public function run() {
		$this->load_textdomain();
		add_action( 'init', array( $this->post_type_controller, 'register' ) );
		add_action( 'init', array( $this->taxonomy_controller, 'register' ) );

		$this->meta_box_controller->init();
		$this->block_controller->init();
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'listing-items', false, dirname( plugin_basename( LISTING_ITEMS_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Register required post type and taxonomies for activation.
	 *
	 * @return void
	 */
	public function register_content_types() {
		$this->post_type_controller->register();
		$this->taxonomy_controller->register();
	}
}
