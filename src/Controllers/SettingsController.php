<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Core\AbstractController;
use CBListingAnything\Models\ListingMeta as ListingMetaModel;

class SettingsController extends AbstractController {

	const OPTION_KEY = 'cb_listing_anything_settings';
	const MENU_SLUG  = 'cb-listing-anything';

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'redirect_legacy_list' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'parent_file', array( $this, 'fix_taxonomy_parent_menu' ) );
		add_action( 'update_option_' . self::OPTION_KEY, array( $this, 'maybe_flush_rewrite_rules' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'admin_body_class', array( $this, 'filter_admin_body_class_settings_page' ) );
	}

	/**
	 * Adds a body class on the React settings screen so CSS can remove wp-admin .wrap / content gutters.
	 *
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public function filter_admin_body_class_settings_page( $classes ) {
		if ( ! isset( $_GET['page'] ) ) {
			return $classes;
		}
		$page = sanitize_key( wp_unslash( $_GET['page'] ) );
		if ( self::MENU_SLUG . '-settings' === $page ) {
			$classes .= ' cb-listing-admin-settings-page';
		}
		return $classes;
	}

	public function redirect_legacy_list() {
		if ( ! is_admin() ) {
			return;
		}

		global $pagenow;
		if ( 'edit.php' !== $pagenow ) {
			return;
		}

		if ( empty( $_GET['post_type'] ) ) {
			return;
		}

		$pt = sanitize_key( wp_unslash( $_GET['post_type'] ) );
		if ( crocodevs_config( 'post_type.slug' ) !== $pt ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
		exit;
	}

	public function fix_taxonomy_parent_menu( $parent_file ) {
		$screen = get_current_screen();

		if ( $screen && crocodevs_config( 'post_type.slug' ) === $screen->post_type && in_array( $screen->taxonomy, array( crocodevs_config( 'taxonomies.category' ), crocodevs_config( 'taxonomies.tag' ) ), true ) ) {
			return self::MENU_SLUG;
		}

		return $parent_file;
	}

	public function register_menu() {
		add_menu_page(
			__( 'CB Listings', 'cb-listing-anything' ),
			__( 'CB Listings', 'cb-listing-anything' ),
			'edit_posts',
			self::MENU_SLUG,
			array( $this, 'render_admin_list' ),
			'dashicons-list-view',
			26
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'All Listings', 'cb-listing-anything' ),
			__( 'All Listings', 'cb-listing-anything' ),
			'edit_posts',
			self::MENU_SLUG,
			array( $this, 'render_admin_list' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'cb-listing-anything' ),
			__( 'Settings', 'cb-listing-anything' ),
			'manage_options',
			self::MENU_SLUG . '-settings',
			array( $this, 'render_admin_settings' )
		);
	}

	/**
	 * @return void
	 */
	public function render_admin_list() {
		echo '<div id="cb-listing-admin-root" class="cb-listing-admin-root" data-screen="list"></div>';
	}

	/**
	 * @return void
	 */
	public function render_admin_settings() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		if ( ! in_array( $tab, array( 'general', 'fields', 'display', 'advanced' ), true ) ) {
			$tab = 'general';
		}
		echo '<div id="cb-listing-admin-root" class="cb-listing-admin-root cb-listing-admin-root--settings" data-screen="settings" data-tab="' . esc_attr( $tab ) . '"></div>';
	}

	/**
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Do not rely on $hook_suffix: WordPress builds it as "{sanitize_title(menu_title)}_page_{slug}", not "toplevel_page_{slug}".
		if ( ! isset( $_GET['page'] ) ) {
			return;
		}
		$page = sanitize_key( wp_unslash( $_GET['page'] ) );
		$allowed_pages = array(
			self::MENU_SLUG,
			self::MENU_SLUG . '-settings',
		);
		if ( ! in_array( $page, $allowed_pages, true ) ) {
			return;
		}

		$asset_file = CB_LISTING_ANYTHING_PLUGIN_DIR . 'build/admin/index.asset.php';
		$script_file = CB_LISTING_ANYTHING_PLUGIN_DIR . 'build/admin/index.js';
		if ( ! file_exists( $asset_file ) || ! file_exists( $script_file ) ) {
			add_action(
				'admin_notices',
				static function () {
					if ( ! current_user_can( 'edit_posts' ) ) {
						return;
					}
					echo '<div class="notice notice-warning"><p>';
					echo esc_html__( 'CB Listing admin UI assets are missing. In the plugin folder run: npm install, then npm run build (production) or npm run start (development — builds blocks and admin).', 'cb-listing-anything' );
					echo '</p></div>';
				}
			);
			return;
		}

		$asset = require $asset_file;
		$ver   = isset( $asset['version'] ) ? $asset['version'] : CB_LISTING_ANYTHING_VERSION;
		$deps  = isset( $asset['dependencies'] ) ? $asset['dependencies'] : array();

		$style_path = CB_LISTING_ANYTHING_PLUGIN_DIR . 'build/admin/style-index.css';
		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				'cb-listing-admin',
				CB_LISTING_ANYTHING_PLUGIN_URL . 'build/admin/style-index.css',
				array( 'common' ),
				$ver
			);
			wp_style_add_data( 'cb-listing-admin', 'rtl', 'replace' );
		}

		wp_enqueue_script(
			'cb-listing-admin',
			CB_LISTING_ANYTHING_PLUGIN_URL . 'build/admin/index.js',
			array_merge( array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'wp-url' ), $deps ),
			$ver,
			true
		);

		$post_type = crocodevs_config( 'post_type.slug' );
		$pto       = get_post_type_object( $post_type );
		$rest_base = ( $pto && ! empty( $pto->rest_base ) ) ? $pto->rest_base : $post_type;

		wp_localize_script(
			'cb-listing-admin',
			'cbListingAdmin',
			array(
				'restUrl'         => esc_url_raw( rest_url() ),
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'postType'        => $post_type,
				'restBase'        => $rest_base,
				'namespace'       => crocodevs_config( 'app.api_prefix' ),
				'newPostUrl'      => admin_url( 'post-new.php?post_type=' . rawurlencode( $post_type ) ),
				'adminUrl'        => admin_url(),
				'listPageUrl'     => admin_url( 'admin.php?page=' . self::MENU_SLUG ),
				'settingsPageUrl' => admin_url( 'admin.php?page=' . self::MENU_SLUG . '-settings' ),
				'pluginName'      => __( 'CB Listings', 'cb-listing-anything' ),
			)
		);
	}

	public function register_settings() {
		register_setting(
			'cb_listing_anything_general',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Merge a partial settings patch into stored options (REST and legacy forms).
	 *
	 * @param array $patch Keys: currency, listing_title, listing_slug, enabled_fields (optional).
	 * @return array{settings: array, errors: string[]}
	 */
	public static function merge_settings_patch( array $patch ) {
		$sanitized = get_option( self::OPTION_KEY, self::defaults() );
		if ( ! is_array( $sanitized ) ) {
			$sanitized = self::defaults();
		}

		$errors = array();

		if ( isset( $patch['currency'] ) ) {
			$valid                   = array_keys( self::currencies() );
			$sanitized['currency'] = in_array( $patch['currency'], $valid, true ) ? $patch['currency'] : 'USD';
		}

		if ( isset( $patch['listing_title'] ) ) {
			$sanitized['listing_title'] = sanitize_text_field( $patch['listing_title'] );
			if ( $sanitized['listing_title'] === '' ) {
				$sanitized['listing_title'] = self::defaults()['listing_title'];
			}
		}

		if ( isset( $patch['listing_slug'] ) ) {
			$raw_slug = sanitize_text_field( $patch['listing_slug'] );
			$slug     = sanitize_title( $raw_slug, '', 'save' );
			$slug     = str_replace( ' ', '-', strtolower( $slug ) );
			$slug     = preg_replace( '/[^a-z0-9_-]/', '', $slug );

			if ( $slug !== '' && self::is_slug_unique( $slug ) ) {
				$sanitized['listing_slug'] = $slug;
			} elseif ( $slug !== '' ) {
				$errors[] = __( 'This slug is already in use by another post type or is reserved. Please choose a unique slug.', 'cb-listing-anything' );
			}
		}

		if ( array_key_exists( 'enabled_fields', $patch ) ) {
			$raw_enabled = $patch['enabled_fields'];
			if ( ! is_array( $raw_enabled ) ) {
				$raw_enabled = array();
			}

			$enabled = array();
			foreach ( $raw_enabled as $value ) {
				$value = sanitize_text_field( (string) $value );
				if ( in_array( $value, ListingMetaModel::fields(), true ) ) {
					$enabled[] = $value;
				}
			}

			$enabled = array_values( array_unique( $enabled ) );

			if ( empty( $enabled ) ) {
				$enabled = ListingMetaModel::fields();
			}

			$sanitized['enabled_fields'] = $enabled;
		}

		return array(
			'settings' => $sanitized,
			'errors'   => $errors,
		);
	}

	public function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return get_option( self::OPTION_KEY, self::defaults() );
		}

		$result = self::merge_settings_patch( $input );
		foreach ( $result['errors'] as $message ) {
			add_settings_error(
				'cb_listing_anything_general',
				'cb_listing_settings',
				$message,
				'error'
			);
		}

		return $result['settings'];
	}

	/**
	 * Flush rewrite rules when listing_slug changes so archive and taxonomy URLs update.
	 *
	 * @param mixed  $old_value Old option value.
	 * @param mixed  $value     New option value.
	 * @param string $option    Option name.
	 */
	public function maybe_flush_rewrite_rules( $old_value, $value, $option ) {
		$old_slug = is_array( $old_value ) && isset( $old_value['listing_slug'] ) ? $old_value['listing_slug'] : '';
		$new_slug = is_array( $value ) && isset( $value['listing_slug'] ) ? $value['listing_slug'] : '';
		if ( $old_slug !== $new_slug ) {
			flush_rewrite_rules();
		}
	}

	public static function get( $key, $default = '' ) {
		$options = get_option( self::OPTION_KEY, self::defaults() );
		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
	}

	public static function defaults() {
		return array(
			'currency'       => 'USD',
			'listing_title'  => __( 'Listing', 'cb-listing-anything' ),
			'listing_slug'   => 'cb_listing',
			'enabled_fields' => ListingMetaModel::fields(),
		);
	}

	/**
	 * Reserved slugs that cannot be used as the listing archive slug.
	 *
	 * @return string[]
	 */
	public static function reserved_slugs() {
		return array(
			'post', 'page', 'attachment', 'revision', 'nav_menu_item',
			'custom_css', 'customize_changeset', 'oembed_cache', 'user_request',
			'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation',
		);
	}

	/**
	 * Check if a slug is unique (not used by another post type's rewrite).
	 *
	 * @param string $slug Proposed slug.
	 * @return bool True if unique.
	 */
	public static function is_slug_unique( $slug ) {
		$slug = trim( $slug );
		if ( $slug === '' ) {
			return false;
		}
		$slug_lower = strtolower( $slug );
		if ( in_array( $slug_lower, self::reserved_slugs(), true ) ) {
			return false;
		}
		foreach ( get_post_types( array(), 'objects' ) as $post_type ) {
			if ( $post_type->name === crocodevs_config( 'post_type.slug' ) ) {
				continue;
			}
			$rewrite_slug = isset( $post_type->rewrite['slug'] ) ? $post_type->rewrite['slug'] : $post_type->name;
			if ( $slug_lower === strtolower( $rewrite_slug ) ) {
				return false;
			}
		}
		return true;
	}

	public static function currencies() {
		return array(
			'USD' => '$ — US Dollar',
			'EUR' => '€ — Euro',
			'GBP' => '£ — British Pound',
			'BDT' => '৳ — Bangladeshi Taka',
			'INR' => '₹ — Indian Rupee',
			'CAD' => 'C$ — Canadian Dollar',
			'AUD' => 'A$ — Australian Dollar',
			'JPY' => '¥ — Japanese Yen',
			'CNY' => '¥ — Chinese Yuan',
			'CHF' => 'Fr — Swiss Franc',
			'SGD' => 'S$ — Singapore Dollar',
			'MYR' => 'RM — Malaysian Ringgit',
			'THB' => '฿ — Thai Baht',
			'SAR' => '﷼ — Saudi Riyal',
			'AED' => 'د.إ — UAE Dirham',
			'PKR' => '₨ — Pakistani Rupee',
			'NZD' => 'NZ$ — New Zealand Dollar',
			'ZAR' => 'R — South African Rand',
			'BRL' => 'R$ — Brazilian Real',
			'TRY' => '₺ — Turkish Lira',
		);
	}

	public static function currency_symbol() {
		$currency   = self::get( 'currency', 'USD' );
		$currencies = self::currencies();

		if ( isset( $currencies[ $currency ] ) ) {
			return explode( ' ', $currencies[ $currency ] )[0];
		}

		return '$';
	}
}
