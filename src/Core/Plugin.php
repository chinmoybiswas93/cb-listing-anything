<?php

namespace CBListingAnything\Core;

use CBListingAnything\Controllers\BlockController;
use CBListingAnything\Controllers\CategoryImageController;
use CBListingAnything\Controllers\MetaBoxController;
use CBListingAnything\Controllers\PostTypeController;
use CBListingAnything\Controllers\SettingsController;
use CBListingAnything\Controllers\TaxonomyController;
use CBListingAnything\Rest\SearchController as RestSearchController;
use CBListingAnything\Rest\TermController as RestTermController;

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
	 * @var SettingsController
	 */
	private $settings_controller;

	/**
	 * @var RestSearchController
	 */
	private $rest_search_controller;

	/**
	 * @var RestTermController
	 */
	private $rest_term_controller;

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
		$this->settings_controller       = new SettingsController();
		$this->category_image_controller = new CategoryImageController();
		$this->rest_search_controller    = new RestSearchController();
		$this->rest_term_controller      = new RestTermController();
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
		$this->settings_controller->init();
		$this->category_image_controller->init();
		add_action( 'rest_api_init', array( $this->rest_search_controller, 'register_routes' ) );
		add_action( 'rest_api_init', array( $this->rest_term_controller, 'register_routes' ) );
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

	/**
	 * Get the post type controller.
	 *
	 * @return PostTypeController
	 */
	public function get_post_type_controller() {
		return $this->post_type_controller;
	}

	/**
	 * Get the taxonomy controller.
	 *
	 * @return TaxonomyController
	 */
	public function get_taxonomy_controller() {
		return $this->taxonomy_controller;
	}

	/**
	 * Get the meta box controller.
	 *
	 * @return MetaBoxController
	 */
	public function get_meta_box_controller() {
		return $this->meta_box_controller;
	}

	/**
	 * Get the block controller.
	 *
	 * @return BlockController
	 */
	public function get_block_controller() {
		return $this->block_controller;
	}

	/**
	 * Get the settings controller.
	 *
	 * @return SettingsController
	 */
	public function get_settings_controller() {
		return $this->settings_controller;
	}

	/**
	 * Get the category image controller.
	 *
	 * @return CategoryImageController
	 */
	public function get_category_image_controller() {
		return $this->category_image_controller;
	}
}
