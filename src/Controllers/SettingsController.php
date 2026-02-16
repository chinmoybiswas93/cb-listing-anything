<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Config\PostType as PostTypeConfig;
use CBListingAnything\Config\Taxonomies as TaxonomiesConfig;
use CBListingAnything\Core\AbstractController;

class SettingsController extends AbstractController {

	const OPTION_KEY = 'cb_listing_anything_settings';
	const MENU_SLUG  = 'cb-listing-anything';

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'parent_file', array( $this, 'fix_taxonomy_parent_menu' ) );
		add_action( 'update_option_' . self::OPTION_KEY, array( $this, 'maybe_flush_rewrite_rules' ), 10, 3 );
	}

	public function fix_taxonomy_parent_menu( $parent_file ) {
		$screen = get_current_screen();

		if ( $screen && PostTypeConfig::POST_TYPE === $screen->post_type && in_array( $screen->taxonomy, array( TaxonomiesConfig::CATEGORY_TAXONOMY, TaxonomiesConfig::TAG_TAXONOMY ), true ) ) {
			return self::MENU_SLUG;
		}

		return $parent_file;
	}

	public function register_menu() {
		add_menu_page(
			__( 'CB Listings', 'cb-listing-anything' ),
			__( 'CB Listings', 'cb-listing-anything' ),
			'manage_options',
			self::MENU_SLUG,
			'__return_empty_string',
			'dashicons-list-view',
			26
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Categories', 'cb-listing-anything' ),
			__( 'Categories', 'cb-listing-anything' ),
			'manage_categories',
			'edit-tags.php?taxonomy=' . TaxonomiesConfig::CATEGORY_TAXONOMY . '&post_type=' . PostTypeConfig::POST_TYPE
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Tags', 'cb-listing-anything' ),
			__( 'Tags', 'cb-listing-anything' ),
			'manage_categories',
			'edit-tags.php?taxonomy=' . TaxonomiesConfig::TAG_TAXONOMY . '&post_type=' . PostTypeConfig::POST_TYPE
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'cb-listing-anything' ),
			__( 'Settings', 'cb-listing-anything' ),
			'manage_options',
			self::MENU_SLUG . '-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'cb_listing_anything_general', self::OPTION_KEY, array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
			'default'           => self::defaults(),
		) );

		add_settings_section(
			'cb_listing_general_section',
			__( 'General Settings', 'cb-listing-anything' ),
			'__return_empty_string',
			'cb_listing_anything_general'
		);

		add_settings_field(
			'currency',
			__( 'Currency', 'cb-listing-anything' ),
			array( $this, 'render_currency_field' ),
			'cb_listing_anything_general',
			'cb_listing_general_section'
		);

		add_settings_field(
			'listing_title',
			__( 'Listing title', 'cb-listing-anything' ),
			array( $this, 'render_listing_title_field' ),
			'cb_listing_anything_general',
			'cb_listing_general_section'
		);

		add_settings_field(
			'listing_slug',
			__( 'Listing slug', 'cb-listing-anything' ),
			array( $this, 'render_listing_slug_field' ),
			'cb_listing_anything_general',
			'cb_listing_general_section'
		);
	}

	public function sanitize_settings( $input ) {
		$sanitized = get_option( self::OPTION_KEY, self::defaults() );
		if ( ! is_array( $sanitized ) ) {
			$sanitized = self::defaults();
		}

		if ( isset( $input['currency'] ) ) {
			$valid = array_keys( self::currencies() );
			$sanitized['currency'] = in_array( $input['currency'], $valid, true ) ? $input['currency'] : 'USD';
		}

		if ( isset( $input['listing_title'] ) ) {
			$sanitized['listing_title'] = sanitize_text_field( $input['listing_title'] );
			if ( $sanitized['listing_title'] === '' ) {
				$sanitized['listing_title'] = self::defaults()['listing_title'];
			}
		}

		if ( isset( $input['listing_slug'] ) ) {
			$raw_slug = sanitize_text_field( $input['listing_slug'] );
			$slug     = sanitize_title( $raw_slug, '', 'save' );
			$slug     = str_replace( ' ', '-', strtolower( $slug ) );
			$slug     = preg_replace( '/[^a-z0-9_-]/', '', $slug );

			if ( $slug !== '' && self::is_slug_unique( $slug ) ) {
				$sanitized['listing_slug'] = $slug;
			} elseif ( $slug !== '' ) {
				add_settings_error(
					'cb_listing_anything_general',
					'listing_slug_duplicate',
					__( 'This slug is already in use by another post type or is reserved. Please choose a unique slug.', 'cb-listing-anything' ),
					'error'
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Flush rewrite rules when listing_slug changes so archive and taxonomy URLs update.
	 *
	 * @param mixed $old_value Old option value.
	 * @param mixed $value     New option value.
	 * @param string $option   Option name.
	 */
	public function maybe_flush_rewrite_rules( $old_value, $value, $option ) {
		$old_slug = is_array( $old_value ) && isset( $old_value['listing_slug'] ) ? $old_value['listing_slug'] : '';
		$new_slug = is_array( $value ) && isset( $value['listing_slug'] ) ? $value['listing_slug'] : '';
		if ( $old_slug !== $new_slug ) {
			flush_rewrite_rules();
		}
	}

	public function render_currency_field() {
		$value      = self::get( 'currency', 'USD' );
		$currencies = self::currencies();
		?>
		<select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[currency]" id="cb_listing_currency">
			<?php foreach ( $currencies as $code => $label ) : ?>
			<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $value, $code ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Select the currency to display with listing prices.', 'cb-listing-anything' ); ?></p>
		<?php
	}

	public function render_listing_title_field() {
		$value = self::get( 'listing_title', self::defaults()['listing_title'] );
		?>
		<input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[listing_title]" id="cb_listing_title" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Label used in the admin (e.g. "Listing", "Property").', 'cb-listing-anything' ); ?></p>
		<?php
	}

	public function render_listing_slug_field() {
		$value       = self::get( 'listing_slug', self::defaults()['listing_slug'] );
		$archive_url = home_url( '/' . $value . '/' );
		?>
		<input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[listing_slug]" id="cb_listing_slug" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'URL slug for the listing archive. Must be unique (not used by posts, pages, or other post types). Category and tag archives will use this slug (e.g. slug-category, slug-tag).', 'cb-listing-anything' ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'Note: Permalinks need to be flushed manually when you save so the new URL is active (e.g. go to Settings → Permalinks and click Save).', 'cb-listing-anything' ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'Archive URL:', 'cb-listing-anything' ); ?>
			<a href="<?php echo esc_url( $archive_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $archive_url ); ?></a>
		</p>
		<?php
	}

	public function render_settings_page() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		$tabs        = array(
			'general'  => __( 'General', 'cb-listing-anything' ),
			'display'  => __( 'Display', 'cb-listing-anything' ),
			'advanced' => __( 'Advanced', 'cb-listing-anything' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CB Listing Settings', 'cb-listing-anything' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $slug => $label ) :
					$url    = add_query_arg( array( 'page' => self::MENU_SLUG . '-settings', 'tab' => $slug ), admin_url( 'admin.php' ) );
					$active = ( $current_tab === $slug ) ? ' nav-tab-active' : '';
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="nav-tab<?php echo esc_attr( $active ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<div class="cb-listing-settings-content" style="margin-top: 20px;">
				<?php
				switch ( $current_tab ) {
					case 'display':
						$this->render_tab_display();
						break;
					case 'advanced':
						$this->render_tab_advanced();
						break;
					default:
						$this->render_tab_general();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	private function render_tab_general() {
		settings_errors( 'cb_listing_anything_general' );
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'cb_listing_anything_general' );
			do_settings_sections( 'cb_listing_anything_general' );
			submit_button();
			?>
		</form>
		<?php
	}

	private function render_tab_display() {
		?>
		<div class="card" style="max-width: 800px; padding: 20px;">
			<h2><?php esc_html_e( 'Display Settings', 'cb-listing-anything' ); ?></h2>
			<p><?php esc_html_e( 'Display settings will be available in a future update.', 'cb-listing-anything' ); ?></p>
		</div>
		<?php
	}

	private function render_tab_advanced() {
		?>
		<div class="card" style="max-width: 800px; padding: 20px;">
			<h2><?php esc_html_e( 'Advanced Settings', 'cb-listing-anything' ); ?></h2>
			<p><?php esc_html_e( 'Advanced settings will be available in a future update.', 'cb-listing-anything' ); ?></p>
		</div>
		<?php
	}

	public static function get( $key, $default = '' ) {
		$options  = get_option( self::OPTION_KEY, self::defaults() );
		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
	}

	public static function defaults() {
		return array(
			'currency'       => 'USD',
			'listing_title' => __( 'Listing', 'cb-listing-anything' ),
			'listing_slug'  => 'cb_listing',
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
			if ( $post_type->name === PostTypeConfig::POST_TYPE ) {
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
