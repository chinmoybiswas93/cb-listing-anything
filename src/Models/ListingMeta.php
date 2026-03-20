<?php

namespace CBListingAnything\Models;

class ListingMeta extends AbstractModel {

	/**
	 * Get the list of listing meta field keys.
	 *
	 * @return array<string>
	 */
	public static function fields() {
		return array_keys( self::definitions() );
	}

	/**
	 * Get full field definitions.
	 *
	 * @return array<string, array{label: string, category: string, type: string}>
	 */
	public static function definitions() {
		return array(
			'listing_price'            => array( 'label' => __( 'Price', 'cb-listing-anything' ), 'category' => 'general', 'type' => 'text' ),
			'listing_location'         => array( 'label' => __( 'Location', 'cb-listing-anything' ), 'category' => 'general', 'type' => 'text' ),
			'listing_subtitle'         => array( 'label' => __( 'Subtitle', 'cb-listing-anything' ), 'category' => 'general', 'type' => 'text' ),
			'listing_description'      => array( 'label' => __( 'Description', 'cb-listing-anything' ), 'category' => 'general', 'type' => 'rich_text' ),
			'listing_address'          => array( 'label' => __( 'Street Address', 'cb-listing-anything' ), 'category' => 'address', 'type' => 'text' ),
			'listing_city'             => array( 'label' => __( 'City', 'cb-listing-anything' ), 'category' => 'address', 'type' => 'text' ),
			'listing_state'            => array( 'label' => __( 'State / Province', 'cb-listing-anything' ), 'category' => 'address', 'type' => 'text' ),
			'listing_zip_code'         => array( 'label' => __( 'ZIP / Postal Code', 'cb-listing-anything' ), 'category' => 'address', 'type' => 'text' ),
			'listing_country'          => array( 'label' => __( 'Country', 'cb-listing-anything' ), 'category' => 'address', 'type' => 'text' ),
			'listing_contact_email'    => array( 'label' => __( 'Contact Email', 'cb-listing-anything' ), 'category' => 'contact', 'type' => 'email' ),
			'listing_contact_phone'    => array( 'label' => __( 'Contact Phone', 'cb-listing-anything' ), 'category' => 'contact', 'type' => 'tel' ),
			'listing_website'          => array( 'label' => __( 'Website', 'cb-listing-anything' ), 'category' => 'contact', 'type' => 'url' ),
			'listing_social_facebook'  => array( 'label' => __( 'Facebook URL', 'cb-listing-anything' ), 'category' => 'social', 'type' => 'url' ),
			'listing_social_twitter'   => array( 'label' => __( 'Twitter / X URL', 'cb-listing-anything' ), 'category' => 'social', 'type' => 'url' ),
			'listing_social_instagram' => array( 'label' => __( 'Instagram URL', 'cb-listing-anything' ), 'category' => 'social', 'type' => 'url' ),
			'listing_social_linkedin'  => array( 'label' => __( 'LinkedIn URL', 'cb-listing-anything' ), 'category' => 'social', 'type' => 'url' ),
			'listing_social_youtube'   => array( 'label' => __( 'YouTube URL', 'cb-listing-anything' ), 'category' => 'social', 'type' => 'url' ),
			'listing_opening_time'     => array( 'label' => __( 'Opening Time', 'cb-listing-anything' ), 'category' => 'business_hours', 'type' => 'time' ),
			'listing_closing_time'     => array( 'label' => __( 'Closing Time', 'cb-listing-anything' ), 'category' => 'business_hours', 'type' => 'time' ),
			'listing_working_days'     => array( 'label' => __( 'Working Days', 'cb-listing-anything' ), 'category' => 'business_hours', 'type' => 'checkbox_group' ),
			'listing_gallery'          => array( 'label' => __( 'Media Gallery', 'cb-listing-anything' ), 'category' => 'media', 'type' => 'media_gallery' ),
		);
	}

	/**
	 * Get field categories.
	 *
	 * @return array<string, array{label: string}>
	 */
	public static function categories() {
		return array(
			'general'        => array( 'label' => __( 'General', 'cb-listing-anything' ) ),
			'address'        => array( 'label' => __( 'Address', 'cb-listing-anything' ) ),
			'contact'        => array( 'label' => __( 'Contact', 'cb-listing-anything' ) ),
			'social'         => array( 'label' => __( 'Social Links', 'cb-listing-anything' ) ),
			'business_hours' => array( 'label' => __( 'Business Hours', 'cb-listing-anything' ) ),
			'media'          => array( 'label' => __( 'Media Gallery', 'cb-listing-anything' ) ),
		);
	}

	/**
	 * Get supported field types.
	 *
	 * @return array<string, array{label: string}>
	 */
	public static function supported_field_types() {
		return array(
			'text'           => array( 'label' => __( 'Text field', 'cb-listing-anything' ) ),
			'email'          => array( 'label' => __( 'Email field', 'cb-listing-anything' ) ),
			'tel'            => array( 'label' => __( 'Phone field', 'cb-listing-anything' ) ),
			'url'            => array( 'label' => __( 'URL field', 'cb-listing-anything' ) ),
			'time'           => array( 'label' => __( 'Time field', 'cb-listing-anything' ) ),
			'rich_text'      => array( 'label' => __( 'Rich text editor', 'cb-listing-anything' ) ),
			'checkbox_group' => array( 'label' => __( 'Checkbox group', 'cb-listing-anything' ) ),
			'media_gallery'  => array( 'label' => __( 'Media gallery', 'cb-listing-anything' ) ),
		);
	}

	/**
	 * Get human-readable label for a field type slug.
	 *
	 * @param string $type_slug Type key (e.g. text, email, rich_text).
	 * @return string
	 */
	public static function get_type_label( $type_slug ) {
		$types = self::supported_field_types();

		if ( isset( $types[ $type_slug ] ) ) {
			return $types[ $type_slug ]['label'];
		}

		return ucfirst( str_replace( '_', ' ', $type_slug ) );
	}

	/**
	 * Get fields grouped by category.
	 *
	 * @return array<string, array{label: string, fields: array<string, array{label: string, category: string}>}>
	 */
	public static function fields_by_category() {
		$definitions = self::definitions();
		$categories  = self::categories();
		$grouped     = array();

		// Initialize groups to keep category order.
		foreach ( $categories as $slug => $category ) {
			$grouped[ $slug ] = array(
				'label'  => $category['label'],
				'fields' => array(),
			);
		}

		foreach ( $definitions as $key => $definition ) {
			$category = isset( $definition['category'] ) ? $definition['category'] : 'general';

			if ( ! isset( $grouped[ $category ] ) ) {
				$grouped[ $category ] = array(
					'label'  => $category,
					'fields' => array(),
				);
			}

			$grouped[ $category ]['fields'][ $key ] = $definition;
		}

		return $grouped;
	}

	/**
	 * Normalize enabled fields setting to a clean list of field keys.
	 *
	 * @param mixed $value Raw setting value.
	 * @return array<string>
	 */
	public static function normalize_enabled_fields( $value ) {
		$all_fields = self::fields();

		if ( ! is_array( $value ) ) {
			return $all_fields;
		}

		// If stored as associative map field_key => bool, convert to keys.
		$keys = array();
		foreach ( $value as $maybe_key => $maybe_enabled ) {
			if ( is_string( $maybe_key ) && in_array( $maybe_key, $all_fields, true ) ) {
				// Treat truthy value as enabled.
				if ( $maybe_enabled ) {
					$keys[] = $maybe_key;
				}
				continue;
			}

			if ( is_string( $maybe_enabled ) && in_array( $maybe_enabled, $all_fields, true ) ) {
				$keys[] = $maybe_enabled;
			}
		}

		$keys = array_values( array_unique( $keys ) );

		if ( empty( $keys ) ) {
			return $all_fields;
		}

		return $keys;
	}

	public static function key( $field ) {
		return '_' . $field;
	}

	public static function sanitize( $field, $value ) {
		switch ( $field ) {
			case 'listing_contact_email':
				return sanitize_email( $value );

			case 'listing_website':
			case 'listing_social_facebook':
			case 'listing_social_twitter':
			case 'listing_social_instagram':
			case 'listing_social_linkedin':
			case 'listing_social_youtube':
				return esc_url_raw( $value );

			case 'listing_working_days':
				if ( is_array( $value ) ) {
					return array_map( 'sanitize_text_field', $value );
				}
				return array();

			case 'listing_description':
				// Allow safe HTML for rich text description.
				return wp_kses_post( $value );

			case 'listing_gallery':
				if ( is_array( $value ) ) {
					return implode( ',', array_map( 'absint', $value ) );
				}
				return sanitize_text_field( $value );

			default:
				return sanitize_text_field( $value );
		}
	}

	public static function is_array_field( $field ) {
		return in_array( $field, array( 'listing_working_days' ), true );
	}

	public static function working_days_options() {
		return array(
			'monday'    => __( 'Monday', 'cb-listing-anything' ),
			'tuesday'   => __( 'Tuesday', 'cb-listing-anything' ),
			'wednesday' => __( 'Wednesday', 'cb-listing-anything' ),
			'thursday'  => __( 'Thursday', 'cb-listing-anything' ),
			'friday'    => __( 'Friday', 'cb-listing-anything' ),
			'saturday'  => __( 'Saturday', 'cb-listing-anything' ),
			'sunday'    => __( 'Sunday', 'cb-listing-anything' ),
		);
	}
}
