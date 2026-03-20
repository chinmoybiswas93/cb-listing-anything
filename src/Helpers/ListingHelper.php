<?php

namespace CBListingAnything\Helpers;

use CrocoDevs\Database\QueryBuilder;

/**
 * Shared helpers for listing data retrieval and logic.
 */
class ListingHelper {

	/**
	 * All meta keys fetched for a listing (without the leading underscore).
	 */
	private const META_KEYS = array(
		'price'            => '_listing_price',
		'address'          => '_listing_address',
		'city'             => '_listing_city',
		'state'            => '_listing_state',
		'zip'              => '_listing_zip_code',
		'country'          => '_listing_country',
		'location'         => '_listing_location',
		'email'            => '_listing_contact_email',
		'phone'            => '_listing_contact_phone',
		'website'          => '_listing_website',
		'facebook'         => '_listing_social_facebook',
		'twitter'          => '_listing_social_twitter',
		'instagram'        => '_listing_social_instagram',
		'linkedin'         => '_listing_social_linkedin',
		'youtube'          => '_listing_social_youtube',
		'opening_time'     => '_listing_opening_time',
		'closing_time'     => '_listing_closing_time',
		'working_days'     => '_listing_working_days',
		'gallery'          => '_listing_gallery',
	);

	/**
	 * Get all listing meta as an associative array.
	 *
	 * @param int $post_id
	 *
	 * @return array Keyed by short name (price, address, phone, ...).
	 */
	public static function get_listing_meta( $post_id ) {
		$meta = array();
		foreach ( self::META_KEYS as $short => $key ) {
			$meta[ $short ] = get_post_meta( $post_id, $key, true );
		}

		return $meta;
	}

	/**
	 * Determine whether a listing is currently open based on its hours and working days.
	 *
	 * @param int         $post_id     Post ID (used when $opening_time / $closing_time / $working_days are null).
	 * @param string|null $opening_time If already fetched, pass directly.
	 * @param string|null $closing_time If already fetched, pass directly.
	 * @param array|null  $working_days If already fetched, pass directly.
	 *
	 * @return bool
	 */
	public static function is_open( $post_id = 0, $opening_time = null, $closing_time = null, $working_days = null ) {
		if ( null === $opening_time ) {
			$opening_time = get_post_meta( $post_id, '_listing_opening_time', true );
		}
		if ( null === $closing_time ) {
			$closing_time = get_post_meta( $post_id, '_listing_closing_time', true );
		}
		if ( null === $working_days ) {
			$working_days = get_post_meta( $post_id, '_listing_working_days', true );
		}

		if ( ! $opening_time || ! $closing_time || ! is_array( $working_days ) ) {
			return false;
		}

		$current_day  = strtolower( wp_date( 'l' ) );
		$current_time = wp_date( 'H:i' );

		return in_array( $current_day, $working_days, true )
			&& $current_time >= $opening_time
			&& $current_time <= $closing_time;
	}

	/**
	 * Build a full address string from individual listing meta parts.
	 *
	 * @param int         $post_id
	 * @param string|null $address
	 * @param string|null $city
	 * @param string|null $state
	 * @param string|null $zip
	 * @param string|null $country
	 *
	 * @return string
	 */
	public static function build_full_address( $post_id = 0, $address = null, $city = null, $state = null, $zip = null, $country = null ) {
		if ( null === $address ) {
			$address = get_post_meta( $post_id, '_listing_address', true );
		}
		if ( null === $city ) {
			$city = get_post_meta( $post_id, '_listing_city', true );
		}
		if ( null === $state ) {
			$state = get_post_meta( $post_id, '_listing_state', true );
		}
		if ( null === $zip ) {
			$zip = get_post_meta( $post_id, '_listing_zip_code', true );
		}
		if ( null === $country ) {
			$country = get_post_meta( $post_id, '_listing_country', true );
		}

		return implode( ', ', array_filter( array( $address, $city, $state, $zip, $country ) ) );
	}

	/**
	 * Get a preview post ID when inside a REST_REQUEST context and the current post is invalid.
	 *
	 * Falls back to the first published listing. Returns 0 if none found.
	 *
	 * @return int
	 */
	public static function get_preview_post_id() {
		if ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return 0;
		}

		$preview_query = QueryBuilder::make()
			->postType( crocodevs_config( 'post_type.slug' ) )
			->perPage( 1 )
			->status( 'publish' )
			->fields( 'ids' )
			->noFoundRows()
			->get();

		if ( ! $preview_query->have_posts() ) {
			return 0;
		}

		$id = $preview_query->posts[0];
		wp_reset_postdata();

		return (int) $id;
	}

	/**
	 * Parse gallery IDs from the raw meta value.
	 *
	 * @param string $gallery_raw Comma-separated attachment IDs.
	 *
	 * @return int[]
	 */
	public static function parse_gallery_ids( $gallery_raw ) {
		if ( ! $gallery_raw ) {
			return array();
		}

		return array_filter( array_map( 'absint', explode( ',', $gallery_raw ) ) );
	}

	/**
	 * Build the combined image list (featured + gallery, no duplicates).
	 *
	 * @param int   $post_id
	 * @param int[] $gallery_ids
	 *
	 * @return int[]
	 */
	public static function build_image_list( $post_id, array $gallery_ids = array() ) {
		$featured_id = (int) get_post_thumbnail_id( $post_id );
		$all_images  = array();

		if ( $featured_id ) {
			$all_images[] = $featured_id;
		}

		foreach ( $gallery_ids as $gid ) {
			if ( $gid && $gid !== $featured_id ) {
				$all_images[] = $gid;
			}
		}

		return $all_images;
	}
}
