<?php
/**
 * Listing details meta box template.
 *
 * @var array $values Prepared listing meta values.
 */

use CBListingAnything\Models\ListingMeta;

$working_days = is_array( $values['listing_working_days'] ) ? $values['listing_working_days'] : array();
$gallery_ids  = array_filter( array_map( 'absint', explode( ',', $values['listing_gallery'] ) ) );
?>
<div class="cb-listing-meta-box">

	<h3 class="cb-listing-meta-box__section"><?php esc_html_e( 'General', 'cb-listing-anything' ); ?></h3>

	<div class="cb-listing-meta-box__row">
		<div class="cb-listing-meta-box__field">
			<label for="listing_price"><?php esc_html_e( 'Price', 'cb-listing-anything' ); ?></label>
			<input type="text" id="listing_price" name="listing_price" value="<?php echo esc_attr( $values['listing_price'] ); ?>" placeholder="<?php esc_attr_e( 'e.g., $99.99', 'cb-listing-anything' ); ?>" />
		</div>
		<div class="cb-listing-meta-box__field">
			<label for="listing_location"><?php esc_html_e( 'Location', 'cb-listing-anything' ); ?></label>
			<input type="text" id="listing_location" name="listing_location" value="<?php echo esc_attr( $values['listing_location'] ); ?>" placeholder="<?php esc_attr_e( 'e.g., New York, NY', 'cb-listing-anything' ); ?>" />
		</div>
	</div>

	<h3 class="cb-listing-meta-box__section"><?php esc_html_e( 'Address', 'cb-listing-anything' ); ?></h3>

	<div class="cb-listing-meta-box__row">
		<div class="cb-listing-meta-box__field">
			<label for="listing_address"><?php esc_html_e( 'Street Address', 'cb-listing-anything' ); ?></label>
			<input type="text" id="listing_address" name="listing_address" value="<?php echo esc_attr( $values['listing_address'] ); ?>" placeholder="<?php esc_attr_e( 'Street address', 'cb-listing-anything' ); ?>" />
		</div>
		<div class="cb-listing-meta-box__field">
			<label for="listing_city"><?php esc_html_e( 'City', 'cb-listing-anything' ); ?></label>
			<input type="text" id="listing_city" name="listing_city" value="<?php echo esc_attr( $values['listing_city'] ); ?>" />
		</div>
	</div>

	<div class="cb-listing-meta-box__row">
		<div class="cb-listing-meta-box__field">
			<label for="listing_state"><?php esc_html_e( 'State / Province', 'cb-listing-anything' ); ?></label>
			<input type="text" id="listing_state" name="listing_state" value="<?php echo esc_attr( $values['listing_state'] ); ?>" />
		</div>
		<div class="cb-listing-meta-box__field">
			<label for="listing_zip_code"><?php esc_html_e( 'ZIP / Postal Code', 'cb-listing-anything' ); ?></label>
			<input type="text" id="listing_zip_code" name="listing_zip_code" value="<?php echo esc_attr( $values['listing_zip_code'] ); ?>" />
		</div>
	</div>

	<div class="cb-listing-meta-box__row">
		<div class="cb-listing-meta-box__field">
			<label for="listing_country"><?php esc_html_e( 'Country', 'cb-listing-anything' ); ?></label>
			<input type="text" id="listing_country" name="listing_country" value="<?php echo esc_attr( $values['listing_country'] ); ?>" />
		</div>
		<div class="cb-listing-meta-box__field"></div>
	</div>

	<h3 class="cb-listing-meta-box__section"><?php esc_html_e( 'Contact', 'cb-listing-anything' ); ?></h3>

	<div class="cb-listing-meta-box__row">
		<div class="cb-listing-meta-box__field">
			<label for="listing_contact_email"><?php esc_html_e( 'Email', 'cb-listing-anything' ); ?></label>
			<input type="email" id="listing_contact_email" name="listing_contact_email" value="<?php echo esc_attr( $values['listing_contact_email'] ); ?>" placeholder="<?php esc_attr_e( 'contact@example.com', 'cb-listing-anything' ); ?>" />
		</div>
		<div class="cb-listing-meta-box__field">
			<label for="listing_contact_phone"><?php esc_html_e( 'Phone', 'cb-listing-anything' ); ?></label>
			<input type="tel" id="listing_contact_phone" name="listing_contact_phone" value="<?php echo esc_attr( $values['listing_contact_phone'] ); ?>" placeholder="<?php esc_attr_e( '+1 (555) 123-4567', 'cb-listing-anything' ); ?>" />
		</div>
	</div>

	<div class="cb-listing-meta-box__row">
		<div class="cb-listing-meta-box__field">
			<label for="listing_website"><?php esc_html_e( 'Website', 'cb-listing-anything' ); ?></label>
			<input type="url" id="listing_website" name="listing_website" value="<?php echo esc_attr( $values['listing_website'] ); ?>" placeholder="<?php esc_attr_e( 'https://example.com', 'cb-listing-anything' ); ?>" />
		</div>
		<div class="cb-listing-meta-box__field"></div>
	</div>

	<h3 class="cb-listing-meta-box__section"><?php esc_html_e( 'Social Links', 'cb-listing-anything' ); ?></h3>

	<div class="cb-listing-meta-box__row">
		<div class="cb-listing-meta-box__field">
			<label for="listing_social_facebook"><?php esc_html_e( 'Facebook', 'cb-listing-anything' ); ?></label>
			<input type="url" id="listing_social_facebook" name="listing_social_facebook" value="<?php echo esc_attr( $values['listing_social_facebook'] ); ?>" placeholder="<?php esc_attr_e( 'https://facebook.com/...', 'cb-listing-anything' ); ?>" />
		</div>
		<div class="cb-listing-meta-box__field">
			<label for="listing_social_twitter"><?php esc_html_e( 'Twitter / X', 'cb-listing-anything' ); ?></label>
			<input type="url" id="listing_social_twitter" name="listing_social_twitter" value="<?php echo esc_attr( $values['listing_social_twitter'] ); ?>" placeholder="<?php esc_attr_e( 'https://x.com/...', 'cb-listing-anything' ); ?>" />
		</div>
	</div>

	<div class="cb-listing-meta-box__row">
		<div class="cb-listing-meta-box__field">
			<label for="listing_social_instagram"><?php esc_html_e( 'Instagram', 'cb-listing-anything' ); ?></label>
			<input type="url" id="listing_social_instagram" name="listing_social_instagram" value="<?php echo esc_attr( $values['listing_social_instagram'] ); ?>" placeholder="<?php esc_attr_e( 'https://instagram.com/...', 'cb-listing-anything' ); ?>" />
		</div>
		<div class="cb-listing-meta-box__field">
			<label for="listing_social_linkedin"><?php esc_html_e( 'LinkedIn', 'cb-listing-anything' ); ?></label>
			<input type="url" id="listing_social_linkedin" name="listing_social_linkedin" value="<?php echo esc_attr( $values['listing_social_linkedin'] ); ?>" placeholder="<?php esc_attr_e( 'https://linkedin.com/...', 'cb-listing-anything' ); ?>" />
		</div>
	</div>

	<div class="cb-listing-meta-box__row">
		<div class="cb-listing-meta-box__field">
			<label for="listing_social_youtube"><?php esc_html_e( 'YouTube', 'cb-listing-anything' ); ?></label>
			<input type="url" id="listing_social_youtube" name="listing_social_youtube" value="<?php echo esc_attr( $values['listing_social_youtube'] ); ?>" placeholder="<?php esc_attr_e( 'https://youtube.com/...', 'cb-listing-anything' ); ?>" />
		</div>
		<div class="cb-listing-meta-box__field"></div>
	</div>

	<h3 class="cb-listing-meta-box__section"><?php esc_html_e( 'Business Hours', 'cb-listing-anything' ); ?></h3>

	<div class="cb-listing-meta-box__row">
		<div class="cb-listing-meta-box__field">
			<label for="listing_opening_time"><?php esc_html_e( 'Opening Time', 'cb-listing-anything' ); ?></label>
			<input type="time" id="listing_opening_time" name="listing_opening_time" value="<?php echo esc_attr( $values['listing_opening_time'] ); ?>" />
		</div>
		<div class="cb-listing-meta-box__field">
			<label for="listing_closing_time"><?php esc_html_e( 'Closing Time', 'cb-listing-anything' ); ?></label>
			<input type="time" id="listing_closing_time" name="listing_closing_time" value="<?php echo esc_attr( $values['listing_closing_time'] ); ?>" />
		</div>
	</div>

	<div class="cb-listing-meta-box__row cb-listing-meta-box__row--full">
		<div class="cb-listing-meta-box__field">
			<label><?php esc_html_e( 'Working Days', 'cb-listing-anything' ); ?></label>
			<div class="cb-listing-meta-box__checkboxes">
				<?php foreach ( ListingMeta::working_days_options() as $key => $label ) : ?>
				<label class="cb-listing-meta-box__checkbox">
					<input type="checkbox" name="listing_working_days[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $working_days, true ) ); ?> />
					<?php echo esc_html( $label ); ?>
				</label>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<h3 class="cb-listing-meta-box__section"><?php esc_html_e( 'Media Gallery', 'cb-listing-anything' ); ?></h3>

	<div class="cb-listing-meta-box__row cb-listing-meta-box__row--full">
		<div class="cb-listing-meta-box__field">
			<div class="cb-listing-gallery">
				<div class="cb-listing-gallery__preview" id="cb-listing-gallery-preview">
					<?php foreach ( $gallery_ids as $img_id ) :
						$thumb = wp_get_attachment_image_url( $img_id, 'thumbnail' );
						if ( ! $thumb ) { continue; }
					?>
					<div class="cb-listing-gallery__item" data-id="<?php echo esc_attr( $img_id ); ?>">
						<img src="<?php echo esc_url( $thumb ); ?>" alt="" />
						<button type="button" class="cb-listing-gallery__remove" aria-label="<?php esc_attr_e( 'Remove', 'cb-listing-anything' ); ?>">&times;</button>
					</div>
					<?php endforeach; ?>
				</div>
				<input type="hidden" id="listing_gallery" name="listing_gallery" value="<?php echo esc_attr( $values['listing_gallery'] ); ?>" />
				<button type="button" class="button cb-listing-gallery__add" id="cb-listing-gallery-add">
					<?php esc_html_e( 'Add Images', 'cb-listing-anything' ); ?>
				</button>
			</div>
		</div>
	</div>

</div>
