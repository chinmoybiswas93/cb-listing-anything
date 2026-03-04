<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Config\PostType as PostTypeConfig;
use CBListingAnything\Config\Taxonomies as TaxonomiesConfig;
use CBListingAnything\Core\AbstractController;
use CBListingAnything\Models\ListingMeta as ListingMetaModel;

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

		if ( array_key_exists( 'enabled_fields', $input ) ) {
			$raw_enabled = $input['enabled_fields'];

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

			// If admin submits Fields tab with nothing selected, fall back to all fields enabled.
			if ( empty( $enabled ) ) {
				$enabled = ListingMetaModel::fields();
			}

			$sanitized['enabled_fields'] = $enabled;
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
			'fields'   => __( 'Fields', 'cb-listing-anything' ),
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
					case 'fields':
						$this->render_tab_fields();
						break;
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

	/**
	 * Render the Fields settings tab.
	 *
	 * @return void
	 */
	private function render_tab_fields() {
		$definitions      = ListingMetaModel::definitions();
		$categories       = ListingMetaModel::categories();
		$grouped          = ListingMetaModel::fields_by_category();
		$current_enabled  = ListingMetaModel::normalize_enabled_fields( self::get( 'enabled_fields', null ) );
		$enabled_lookup   = array_fill_keys( $current_enabled, true );
		$option_key_esc   = esc_attr( self::OPTION_KEY );
		$settings_group   = 'cb_listing_anything_general';
		?>
		<style>
			.cb-listing-fields-settings {
				max-width: 960px;
			}
			.cb-listing-fields-card {
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 8px;
				padding: 20px 22px;
				margin-bottom: 20px;
				box-shadow: 0 1px 2px rgba(0,0,0,0.02);
			}
			.cb-listing-fields-card-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				gap: 12px;
				margin-bottom: 8px;
			}
			.cb-listing-fields-card-header h2 {
				margin: 0;
				font-size: 15px;
				font-weight: 600;
			}
			.cb-listing-fields-count {
				font-size: 12px;
				color: #50575e;
				background: #f6f7f7;
				border-radius: 999px;
				padding: 2px 10px;
			}
			.cb-listing-fields-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
				gap: 8px 16px;
				margin-top: 12px;
			}
			.cb-listing-field {
				display: flex;
				align-items: flex-start;
				gap: 8px;
				padding: 8px 10px;
				border-radius: 6px;
				border: 1px solid transparent;
			}
			.cb-listing-field input[type="checkbox"] {
				margin-top: 2px;
			}
			.cb-listing-field-main {
				display: flex;
				flex-direction: column;
				gap: 2px;
			}
			.cb-listing-field-label {
				font-size: 13px;
				font-weight: 500;
				color: #1e1e1e;
			}
			.cb-listing-field-meta {
				font-size: 11px;
				color: #646970;
				text-transform: capitalize;
			}
			.cb-listing-field:hover {
				background: #f6f7f7;
				border-color: #d0d1d4;
			}
			@media (max-width: 782px) {
				.cb-listing-fields-card {
					padding: 16px;
				}
				.cb-listing-fields-grid {
					grid-template-columns: 1fr;
				}
			}
		</style>
		<form method="post" action="options.php">
			<?php
			settings_fields( $settings_group );
			?>
			<div class="cb-listing-fields-settings">
				<?php foreach ( $categories as $slug => $category ) :
					$label       = isset( $category['label'] ) ? $category['label'] : ucfirst( $slug );
					$fields_in_category = isset( $grouped[ $slug ]['fields'] ) ? $grouped[ $slug ]['fields'] : array();
					$total_count       = count( $fields_in_category );

					if ( 0 === $total_count ) {
						continue;
					}

					$enabled_count = 0;
					foreach ( $fields_in_category as $field_key => $field_def ) {
						if ( isset( $enabled_lookup[ $field_key ] ) ) {
							$enabled_count++;
						}
					}

					?>
					<div class="cb-listing-fields-card">
						<div class="cb-listing-fields-card-header">
							<h2><?php echo esc_html( $label ); ?></h2>
							<span class="cb-listing-fields-count">
								<?php
								printf(
									/* translators: 1: enabled count, 2: total fields */
									'%1$d / %2$d %3$s',
									(int) $enabled_count,
									(int) $total_count,
									1 === $total_count ? esc_html__( 'field', 'cb-listing-anything' ) : esc_html__( 'fields', 'cb-listing-anything' )
								);
								?>
							</span>
						</div>
						<div class="cb-listing-fields-grid">
							<?php foreach ( $fields_in_category as $field_key => $field_def ) :
								$field_label = isset( $field_def['label'] ) ? $field_def['label'] : $field_key;
								$field_type  = isset( $field_def['type'] ) ? $field_def['type'] : 'text';
								$checked     = isset( $enabled_lookup[ $field_key ] );
								?>
								<label class="cb-listing-field">
									<input
										type="checkbox"
										name="<?php echo $option_key_esc; ?>[enabled_fields][]"
										value="<?php echo esc_attr( $field_key ); ?>"
										<?php checked( $checked ); ?>
									/>
									<span class="cb-listing-field-main">
										<span class="cb-listing-field-label"><?php echo esc_html( $field_label ); ?></span>
										<span class="cb-listing-field-meta">
											<?php
											// Human-readable type label.
											switch ( $field_type ) {
												case 'email':
													$type_label = __( 'Email field', 'cb-listing-anything' );
													break;
												case 'tel':
													$type_label = __( 'Phone field', 'cb-listing-anything' );
													break;
												case 'url':
													$type_label = __( 'URL field', 'cb-listing-anything' );
													break;
												case 'time':
													$type_label = __( 'Time field', 'cb-listing-anything' );
													break;
												case 'checkbox_group':
													$type_label = __( 'Checkbox group', 'cb-listing-anything' );
													break;
												case 'media_gallery':
													$type_label = __( 'Media gallery', 'cb-listing-anything' );
													break;
												case 'rich_text':
													$type_label = __( 'Rich text editor', 'cb-listing-anything' );
													break;
												default:
													$type_label = __( 'Text field', 'cb-listing-anything' );
													break;
											}
											echo esc_html( $type_label );
											?>
										</span>
									</span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?php submit_button(); ?>
		</form>
		<?php
	}

	public static function get( $key, $default = '' ) {
		$options  = get_option( self::OPTION_KEY, self::defaults() );
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
