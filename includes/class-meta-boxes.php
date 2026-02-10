<?php
/**
 * Meta Boxes
 *
 * @package Listing_Items
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Listing_Items_Meta_Boxes
 */
class Listing_Items_Meta_Boxes {

	/**
	 * Initialize meta boxes
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_data' ) );
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'listing_details',
			__( 'Listing Details', 'listing-items' ),
			array( $this, 'render_listing_details_meta_box' ),
			'listing',
			'normal',
			'high'
		);
	}

	/**
	 * Render listing details meta box
	 *
	 * @param WP_Post $post The post object
	 */
	public function render_listing_details_meta_box( $post ) {
		// Add nonce for security
		wp_nonce_field( 'listing_details_meta_box', 'listing_details_meta_box_nonce' );

		// Get existing values
		$price = get_post_meta( $post->ID, '_listing_price', true );
		$location = get_post_meta( $post->ID, '_listing_location', true );
		$contact_email = get_post_meta( $post->ID, '_listing_contact_email', true );
		$contact_phone = get_post_meta( $post->ID, '_listing_contact_phone', true );
		$website = get_post_meta( $post->ID, '_listing_website', true );
		$address = get_post_meta( $post->ID, '_listing_address', true );
		$city = get_post_meta( $post->ID, '_listing_city', true );
		$state = get_post_meta( $post->ID, '_listing_state', true );
		$zip_code = get_post_meta( $post->ID, '_listing_zip_code', true );
		$country = get_post_meta( $post->ID, '_listing_country', true );
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="listing_price"><?php esc_html_e( 'Price', 'listing-items' ); ?></label>
					</th>
					<td>
						<input type="text" id="listing_price" name="listing_price" value="<?php echo esc_attr( $price ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., $99.99', 'listing-items' ); ?>" />
						<p class="description"><?php esc_html_e( 'Enter the price for this listing.', 'listing-items' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="listing_location"><?php esc_html_e( 'Location', 'listing-items' ); ?></label>
					</th>
					<td>
						<input type="text" id="listing_location" name="listing_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., New York, NY', 'listing-items' ); ?>" />
						<p class="description"><?php esc_html_e( 'Enter the general location.', 'listing-items' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="listing_address"><?php esc_html_e( 'Address', 'listing-items' ); ?></label>
					</th>
					<td>
						<input type="text" id="listing_address" name="listing_address" value="<?php echo esc_attr( $address ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Street address', 'listing-items' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="listing_city"><?php esc_html_e( 'City', 'listing-items' ); ?></label>
					</th>
					<td>
						<input type="text" id="listing_city" name="listing_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="listing_state"><?php esc_html_e( 'State/Province', 'listing-items' ); ?></label>
					</th>
					<td>
						<input type="text" id="listing_state" name="listing_state" value="<?php echo esc_attr( $state ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="listing_zip_code"><?php esc_html_e( 'ZIP/Postal Code', 'listing-items' ); ?></label>
					</th>
					<td>
						<input type="text" id="listing_zip_code" name="listing_zip_code" value="<?php echo esc_attr( $zip_code ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="listing_country"><?php esc_html_e( 'Country', 'listing-items' ); ?></label>
					</th>
					<td>
						<input type="text" id="listing_country" name="listing_country" value="<?php echo esc_attr( $country ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="listing_contact_email"><?php esc_html_e( 'Contact Email', 'listing-items' ); ?></label>
					</th>
					<td>
						<input type="email" id="listing_contact_email" name="listing_contact_email" value="<?php echo esc_attr( $contact_email ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'contact@example.com', 'listing-items' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="listing_contact_phone"><?php esc_html_e( 'Contact Phone', 'listing-items' ); ?></label>
					</th>
					<td>
						<input type="tel" id="listing_contact_phone" name="listing_contact_phone" value="<?php echo esc_attr( $contact_phone ); ?>" class="regular-text" placeholder="<?php esc_attr_e( '+1 (555) 123-4567', 'listing-items' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="listing_website"><?php esc_html_e( 'Website', 'listing-items' ); ?></label>
					</th>
					<td>
						<input type="url" id="listing_website" name="listing_website" value="<?php echo esc_attr( $website ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'https://example.com', 'listing-items' ); ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save meta data
	 *
	 * @param int $post_id The post ID
	 */
	public function save_meta_data( $post_id ) {
		// Check if nonce is set
		if ( ! isset( $_POST['listing_details_meta_box_nonce'] ) ) {
			return;
		}

		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['listing_details_meta_box_nonce'], 'listing_details_meta_box' ) ) {
			return;
		}

		// Check if autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check user permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check post type
		if ( 'listing' !== get_post_type( $post_id ) ) {
			return;
		}

		// Save meta fields
		$fields = array(
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

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
			} else {
				delete_post_meta( $post_id, '_' . $field );
			}
		}
	}
}
