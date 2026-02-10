<?php

namespace CBListingAnything\Models;

class ListingMeta {

	/**
	 * Return all supported listing meta fields.
	 *
	 * @return string[]
	 */
	public static function fields() {
		return array(
			'listing_price',
			'listing_location',
			'listing_address',
			'listing_city',
			'listing_state',
			'listing_zip_code',
			'listing_country',
			'listing_contact_email',
			'listing_contact_phone',
			'listing_website',
		);
	}

	/**
	 * Build a prefixed meta key from a field name.
	 *
	 * @param string $field Meta field name.
	 * @return string
	 */
	public static function key( $field ) {
		return '_' . $field;
	}

	/**
	 * Sanitize a field value based on the field type.
	 *
	 * @param string $field Meta field name.
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function sanitize( $field, $value ) {
		switch ( $field ) {
			case 'listing_contact_email':
				return sanitize_email( $value );
			case 'listing_website':
				return esc_url_raw( $value );
			default:
				return sanitize_text_field( $value );
		}
	}
}
