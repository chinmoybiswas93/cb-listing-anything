<?php

namespace CBListingAnything\Controllers;

class SettingsController {

	const OPTION_KEY = 'cb_listing_anything_settings';
	const MENU_SLUG  = 'cb-listing-anything';

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'parent_file', array( $this, 'fix_taxonomy_parent_menu' ) );
	}

	public function fix_taxonomy_parent_menu( $parent_file ) {
		$screen = get_current_screen();

		if ( $screen && 'listing' === $screen->post_type && in_array( $screen->taxonomy, array( 'listing_category', 'listing_tag' ), true ) ) {
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
			'edit-tags.php?taxonomy=listing_category&post_type=listing'
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Tags', 'cb-listing-anything' ),
			__( 'Tags', 'cb-listing-anything' ),
			'manage_categories',
			'edit-tags.php?taxonomy=listing_tag&post_type=listing'
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
	}

	public function sanitize_settings( $input ) {
		$sanitized = self::defaults();

		if ( isset( $input['currency'] ) ) {
			$valid = array_keys( self::currencies() );
			$sanitized['currency'] = in_array( $input['currency'], $valid, true ) ? $input['currency'] : 'USD';
		}

		return $sanitized;
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
			'currency' => 'USD',
		);
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
