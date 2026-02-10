<?php
/**
 * Listing details meta box template.
 *
 * @var array $values Prepared listing meta values.
 */
?>
<table class="form-table">
	<tbody>
		<tr>
			<th scope="row">
				<label for="listing_price"><?php esc_html_e( 'Price', 'listing-items' ); ?></label>
			</th>
			<td>
				<input type="text" id="listing_price" name="listing_price" value="<?php echo esc_attr( $values['listing_price'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., $99.99', 'listing-items' ); ?>" />
				<p class="description"><?php esc_html_e( 'Enter the price for this listing.', 'listing-items' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="listing_location"><?php esc_html_e( 'Location', 'listing-items' ); ?></label>
			</th>
			<td>
				<input type="text" id="listing_location" name="listing_location" value="<?php echo esc_attr( $values['listing_location'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., New York, NY', 'listing-items' ); ?>" />
				<p class="description"><?php esc_html_e( 'Enter the general location.', 'listing-items' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="listing_address"><?php esc_html_e( 'Address', 'listing-items' ); ?></label>
			</th>
			<td>
				<input type="text" id="listing_address" name="listing_address" value="<?php echo esc_attr( $values['listing_address'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Street address', 'listing-items' ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="listing_city"><?php esc_html_e( 'City', 'listing-items' ); ?></label>
			</th>
			<td>
				<input type="text" id="listing_city" name="listing_city" value="<?php echo esc_attr( $values['listing_city'] ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="listing_state"><?php esc_html_e( 'State/Province', 'listing-items' ); ?></label>
			</th>
			<td>
				<input type="text" id="listing_state" name="listing_state" value="<?php echo esc_attr( $values['listing_state'] ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="listing_zip_code"><?php esc_html_e( 'ZIP/Postal Code', 'listing-items' ); ?></label>
			</th>
			<td>
				<input type="text" id="listing_zip_code" name="listing_zip_code" value="<?php echo esc_attr( $values['listing_zip_code'] ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="listing_country"><?php esc_html_e( 'Country', 'listing-items' ); ?></label>
			</th>
			<td>
				<input type="text" id="listing_country" name="listing_country" value="<?php echo esc_attr( $values['listing_country'] ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="listing_contact_email"><?php esc_html_e( 'Contact Email', 'listing-items' ); ?></label>
			</th>
			<td>
				<input type="email" id="listing_contact_email" name="listing_contact_email" value="<?php echo esc_attr( $values['listing_contact_email'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'contact@example.com', 'listing-items' ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="listing_contact_phone"><?php esc_html_e( 'Contact Phone', 'listing-items' ); ?></label>
			</th>
			<td>
				<input type="tel" id="listing_contact_phone" name="listing_contact_phone" value="<?php echo esc_attr( $values['listing_contact_phone'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( '+1 (555) 123-4567', 'listing-items' ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="listing_website"><?php esc_html_e( 'Website', 'listing-items' ); ?></label>
			</th>
			<td>
				<input type="url" id="listing_website" name="listing_website" value="<?php echo esc_attr( $values['listing_website'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'https://example.com', 'listing-items' ); ?>" />
			</td>
		</tr>
	</tbody>
</table>
