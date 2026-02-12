<?php
/**
 * Listing details meta box template.
 *
 * @var array $values Prepared listing meta values.
 */
?>
<div class="cb-listing-meta-box">
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

	<div class="cb-listing-meta-box__row">
		<div class="cb-listing-meta-box__field">
			<label for="listing_address"><?php esc_html_e( 'Address', 'cb-listing-anything' ); ?></label>
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
		<div class="cb-listing-meta-box__field">
			<label for="listing_website"><?php esc_html_e( 'Website', 'cb-listing-anything' ); ?></label>
			<input type="url" id="listing_website" name="listing_website" value="<?php echo esc_attr( $values['listing_website'] ); ?>" placeholder="<?php esc_attr_e( 'https://example.com', 'cb-listing-anything' ); ?>" />
		</div>
	</div>

	<div class="cb-listing-meta-box__row">
		<div class="cb-listing-meta-box__field">
			<label for="listing_contact_email"><?php esc_html_e( 'Contact Email', 'cb-listing-anything' ); ?></label>
			<input type="email" id="listing_contact_email" name="listing_contact_email" value="<?php echo esc_attr( $values['listing_contact_email'] ); ?>" placeholder="<?php esc_attr_e( 'contact@example.com', 'cb-listing-anything' ); ?>" />
		</div>
		<div class="cb-listing-meta-box__field">
			<label for="listing_contact_phone"><?php esc_html_e( 'Contact Phone', 'cb-listing-anything' ); ?></label>
			<input type="tel" id="listing_contact_phone" name="listing_contact_phone" value="<?php echo esc_attr( $values['listing_contact_phone'] ); ?>" placeholder="<?php esc_attr_e( '+1 (555) 123-4567', 'cb-listing-anything' ); ?>" />
		</div>
	</div>
</div>
