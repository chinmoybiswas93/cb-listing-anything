<?php

namespace CBListingAnything\Config;

/**
 * Listing meta field definitions (keys and optional type/label).
 */
class ListingMeta {

	/**
	 * Get the list of listing meta field keys.
	 *
	 * @return array<string>
	 */
	public static function field_keys() {
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
}
