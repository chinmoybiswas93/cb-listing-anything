<?php

namespace CBListingAnything\Config;

/**
 * Listing meta field definitions (keys, labels, categories).
 */
class ListingMeta {

	/**
	 * Get the full listing meta field definitions.
	 *
	 * @return array<string, array{label: string, category: string}>
	 */
	public static function field_definitions() {
		return array(
			'listing_price'            => array(
				'label'    => __( 'Price', 'cb-listing-anything' ),
				'category' => 'general',
				'type'     => 'text',
			),
			'listing_location'         => array(
				'label'    => __( 'Location', 'cb-listing-anything' ),
				'category' => 'general',
				'type'     => 'text',
			),
			'listing_description'      => array(
				'label'    => __( 'Description', 'cb-listing-anything' ),
				'category' => 'general',
				'type'     => 'rich_text',
			),
			'listing_address'          => array(
				'label'    => __( 'Street Address', 'cb-listing-anything' ),
				'category' => 'address',
				'type'     => 'text',
			),
			'listing_city'             => array(
				'label'    => __( 'City', 'cb-listing-anything' ),
				'category' => 'address',
				'type'     => 'text',
			),
			'listing_state'            => array(
				'label'    => __( 'State / Province', 'cb-listing-anything' ),
				'category' => 'address',
				'type'     => 'text',
			),
			'listing_zip_code'         => array(
				'label'    => __( 'ZIP / Postal Code', 'cb-listing-anything' ),
				'category' => 'address',
				'type'     => 'text',
			),
			'listing_country'          => array(
				'label'    => __( 'Country', 'cb-listing-anything' ),
				'category' => 'address',
				'type'     => 'text',
			),
			'listing_contact_email'    => array(
				'label'    => __( 'Contact Email', 'cb-listing-anything' ),
				'category' => 'contact',
				'type'     => 'email',
			),
			'listing_contact_phone'    => array(
				'label'    => __( 'Contact Phone', 'cb-listing-anything' ),
				'category' => 'contact',
				'type'     => 'tel',
			),
			'listing_website'          => array(
				'label'    => __( 'Website', 'cb-listing-anything' ),
				'category' => 'contact',
				'type'     => 'url',
			),
			'listing_social_facebook'  => array(
				'label'    => __( 'Facebook URL', 'cb-listing-anything' ),
				'category' => 'social',
				'type'     => 'url',
			),
			'listing_social_twitter'   => array(
				'label'    => __( 'Twitter / X URL', 'cb-listing-anything' ),
				'category' => 'social',
				'type'     => 'url',
			),
			'listing_social_instagram' => array(
				'label'    => __( 'Instagram URL', 'cb-listing-anything' ),
				'category' => 'social',
				'type'     => 'url',
			),
			'listing_social_linkedin'  => array(
				'label'    => __( 'LinkedIn URL', 'cb-listing-anything' ),
				'category' => 'social',
				'type'     => 'url',
			),
			'listing_social_youtube'   => array(
				'label'    => __( 'YouTube URL', 'cb-listing-anything' ),
				'category' => 'social',
				'type'     => 'url',
			),
			'listing_opening_time'     => array(
				'label'    => __( 'Opening Time', 'cb-listing-anything' ),
				'category' => 'business_hours',
				'type'     => 'time',
			),
			'listing_closing_time'     => array(
				'label'    => __( 'Closing Time', 'cb-listing-anything' ),
				'category' => 'business_hours',
				'type'     => 'time',
			),
			'listing_working_days'     => array(
				'label'    => __( 'Working Days', 'cb-listing-anything' ),
				'category' => 'business_hours',
				'type'     => 'checkbox_group',
			),
			'listing_gallery'          => array(
				'label'    => __( 'Media Gallery', 'cb-listing-anything' ),
				'category' => 'media',
				'type'     => 'media_gallery',
			),
		);
	}

	/**
	 * Get the list of listing meta field keys (for backward compatibility).
	 *
	 * @return array<string>
	 */
	public static function field_keys() {
		return array_keys( self::field_definitions() );
	}

	/**
	 * Get the available field categories.
	 *
	 * @return array<string, array{label: string}>
	 */
	public static function categories() {
		return array(
			'general'        => array(
				'label' => __( 'General', 'cb-listing-anything' ),
			),
			'address'        => array(
				'label' => __( 'Address', 'cb-listing-anything' ),
			),
			'contact'        => array(
				'label' => __( 'Contact', 'cb-listing-anything' ),
			),
			'social'         => array(
				'label' => __( 'Social Links', 'cb-listing-anything' ),
			),
			'business_hours' => array(
				'label' => __( 'Business Hours', 'cb-listing-anything' ),
			),
			'media'          => array(
				'label' => __( 'Media Gallery', 'cb-listing-anything' ),
			),
		);
	}
}
