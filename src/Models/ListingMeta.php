<?php

namespace CBListingAnything\Models;

use CBListingAnything\Config\ListingMeta as ListingMetaConfig;

class ListingMeta extends AbstractModel {

	/**
	 * Get the list of listing meta field keys (from config).
	 *
	 * @return array<string>
	 */
	public static function fields() {
		return ListingMetaConfig::field_keys();
	}

	/**
	 * Get full field definitions from config.
	 *
	 * @return array<string, array{label: string, category: string}>
	 */
	public static function definitions() {
		return ListingMetaConfig::field_definitions();
	}

	/**
	 * Get field categories from config.
	 *
	 * @return array<string, array{label: string}>
	 */
	public static function categories() {
		return ListingMetaConfig::categories();
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
