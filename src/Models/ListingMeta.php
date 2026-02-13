<?php

namespace CBListingAnything\Models;

class ListingMeta {

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
			'listing_social_facebook',
			'listing_social_twitter',
			'listing_social_instagram',
			'listing_social_linkedin',
			'listing_social_youtube',
			'listing_opening_time',
			'listing_closing_time',
			'listing_working_days',
			'listing_gallery',
		);
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
