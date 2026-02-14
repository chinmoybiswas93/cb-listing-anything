<?php

namespace CBListingAnything\Core;

use CBListingAnything\Controllers\BlockController;
use CBListingAnything\Controllers\CategoryImageController;
use CBListingAnything\Controllers\MetaBoxController;
use CBListingAnything\Controllers\PostTypeController;
use CBListingAnything\Controllers\SearchController;
use CBListingAnything\Controllers\SettingsController;
use CBListingAnything\Controllers\TaxonomyController;

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
	 * @var SearchController
	 */
	private $search_controller;

	/**
	 * @var SettingsController
	 */
	private $settings_controller;

	/**
	 * @var CategoryImageController
	 */
	private $category_image_controller;

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
		$this->post_type_controller      = new PostTypeController();
		$this->taxonomy_controller       = new TaxonomyController();
		$this->meta_box_controller       = new MetaBoxController();
		$this->block_controller          = new BlockController();
		$this->search_controller         = new SearchController();
		$this->settings_controller       = new SettingsController();
		$this->category_image_controller = new CategoryImageController();
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
		$this->search_controller->init();
		$this->settings_controller->init();
		$this->category_image_controller->init();
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'cb-listing-anything', false, dirname( plugin_basename( CB_LISTING_ANYTHING_PLUGIN_FILE ) ) . '/languages' );
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
