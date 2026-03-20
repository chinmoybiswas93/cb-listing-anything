<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Models\ListingMeta;
use CrocoDevs\Validation\Validator;

/**
 * Handles all non-rendering logic for the user dashboard block:
 * login, delete, add/edit listing form submission, and form pre-population.
 */
class UserDashboardController {

	/**
	 * Process a front-end login attempt.
	 *
	 * @return array{errors: string[], username: string}
	 */
	public static function handle_login() {
		$errors   = array();
		$username = '';

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' )
			|| ! isset( $_POST['cb_listing_user_dashboard_login'] )
			|| '1' !== $_POST['cb_listing_user_dashboard_login']
		) {
			return compact( 'errors', 'username' );
		}

		$username = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '';
		$password = isset( $_POST['pwd'] ) ? wp_unslash( $_POST['pwd'] ) : '';
		// phpcs:enable

		if ( ! isset( $_POST['_cb_listing_login_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_cb_listing_login_nonce'] ) ), 'cb_listing_user_login' )
		) {
			$errors[] = __( 'Security check failed. Please try again.', 'cb-listing-anything' );
			return compact( 'errors', 'username' );
		}

		if ( '' === $username || '' === $password ) {
			$errors[] = __( 'Please enter both username (or email) and password.', 'cb-listing-anything' );
			return compact( 'errors', 'username' );
		}

		$user = wp_signon( array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => ! empty( $_POST['rememberme'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		) );

		if ( is_wp_error( $user ) ) {
			$errors[] = $user->get_error_message();
			return compact( 'errors', 'username' );
		}

		wp_safe_redirect( get_permalink() );
		exit;
	}

	/**
	 * Handle the "delete listing" dashboard action.
	 *
	 * Redirects on completion. Returns true if handled, false if not applicable.
	 *
	 * @param string $base_url URL to redirect to.
	 *
	 * @return bool
	 */
	public static function handle_delete( $base_url ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}

		$action = isset( $_POST['cb_listing_user_dashboard_action'] ) ? sanitize_key( wp_unslash( $_POST['cb_listing_user_dashboard_action'] ) ) : '';
		if ( 'delete_listing' !== $action ) {
			return false;
		}

		$delete_post_id = isset( $_POST['cb_listing_post_id'] ) ? absint( wp_unslash( $_POST['cb_listing_post_id'] ) ) : 0;
		// phpcs:enable

		$notice_key = 'cb_listing_anything_dashboard_notice_' . get_current_user_id();

		if ( ! isset( $_POST['_cb_listing_delete_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_cb_listing_delete_nonce'] ) ), 'cb_listing_delete_listing' )
		) {
			set_transient( $notice_key, 'delete_failed', MINUTE_IN_SECONDS );
			wp_safe_redirect( add_query_arg( 'tab', 'listings', $base_url ) );
			exit;
		}

		if ( $delete_post_id && current_user_can( 'delete_post', $delete_post_id ) ) {
			wp_trash_post( $delete_post_id );
			set_transient( $notice_key, 'deleted', MINUTE_IN_SECONDS );
		} else {
			set_transient( $notice_key, 'delete_failed', MINUTE_IN_SECONDS );
		}

		wp_safe_redirect( add_query_arg( 'tab', 'listings', $base_url ) );
		exit;
	}

	/**
	 * Handle add / edit listing form submission.
	 *
	 * @param bool   $can_submit   Whether the current user may submit listings.
	 * @param int    $editing_post_id  Post ID being edited (0 for new).
	 * @param array  $meta_field_keys  ListingMeta::fields() result.
	 *
	 * @return array{
	 *     errors: string[],
	 *     success_message: string,
	 *     form_title: string,
	 *     form_content: string,
	 *     form_category_id: int,
	 *     form_tag_ids: int[],
	 *     form_tags_other: string,
	 *     form_featured_id: int,
	 *     form_price: string,
	 *     form_meta: array,
	 *     editing_post_id: int
	 * }|null  Returns null when not handling a submission.
	 */
	public static function handle_submission( $can_submit, $editing_post_id, array $meta_field_keys ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return null;
		}

		$action = isset( $_POST['cb_listing_user_dashboard_action'] ) ? sanitize_key( wp_unslash( $_POST['cb_listing_user_dashboard_action'] ) ) : '';
		if ( ! in_array( $action, array( 'add_listing', 'edit_listing' ), true ) ) {
			return null;
		}

		$errors          = array();
		$success_message = '';

		if ( ! isset( $_POST['_cb_listing_add_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_cb_listing_add_nonce'] ) ), 'cb_listing_add_listing' )
		) {
			$errors[] = __( 'Security check failed. Please try again.', 'cb-listing-anything' );
			return self::build_result( $errors, '', '', '', 0, array(), '', 0, '', self::empty_meta( $meta_field_keys ), $editing_post_id );
		}

		if ( ! $can_submit ) {
			$errors[] = __( 'You are not allowed to submit listings from this account.', 'cb-listing-anything' );
			return self::build_result( $errors, '', '', '', 0, array(), '', 0, '', self::empty_meta( $meta_field_keys ), $editing_post_id );
		}

		$data = self::extract_form_data( $meta_field_keys );
		// phpcs:enable

		$validation = Validator::make( $data, self::submission_rules() );

		if ( $validation->fails() ) {
			$flat_errors = array();
			foreach ( $validation->errors() as $field_errors ) {
				foreach ( $field_errors as $msg ) {
					$flat_errors[] = $msg;
				}
			}

			return self::build_result(
				$flat_errors, '', $data['cb_listing_title'], $data['cb_listing_content'],
				$data['cb_listing_category'], $data['cb_listing_tags'], $data['cb_listing_tags_other'],
				$data['cb_listing_featured_image_id'], $data['listing_price'],
				$data['_meta'], $editing_post_id
			);
		}

		$form_title       = sanitize_text_field( $data['cb_listing_title'] );
		$form_content     = wp_kses_post( $data['cb_listing_content'] );
		$form_category_id = absint( $data['cb_listing_category'] );
		$form_tag_ids     = is_array( $data['cb_listing_tags'] ) ? array_map( 'absint', $data['cb_listing_tags'] ) : array();
		$form_tags_other  = sanitize_text_field( $data['cb_listing_tags_other'] );
		$form_featured_id = absint( $data['cb_listing_featured_image_id'] );

		$form_meta = array();
		foreach ( $meta_field_keys as $field_key ) {
			$raw_value = isset( $data['_meta'][ $field_key ] ) ? $data['_meta'][ $field_key ] : ( ListingMeta::is_array_field( $field_key ) ? array() : '' );
			$form_meta[ $field_key ] = ListingMeta::sanitize( $field_key, $raw_value );
		}

		$form_price = isset( $form_meta['listing_price'] ) ? $form_meta['listing_price'] : '';

		$form_tag_ids = self::process_other_tags( $form_tag_ids, $form_tags_other );

		if ( empty( $errors ) ) {
			$result = self::save_listing( $action, $form_title, $form_content, $editing_post_id, $form_category_id, $form_tag_ids, $form_meta, $form_featured_id, $meta_field_keys );
			$errors          = $result['errors'];
			$success_message = $result['success_message'];
			$editing_post_id = $result['editing_post_id'];

			if ( ! empty( $success_message ) && 'add_listing' === $action ) {
				$form_title       = '';
				$form_content     = '';
				$form_category_id = 0;
				$form_tag_ids     = array();
				$form_tags_other  = '';
				$form_featured_id = 0;
				$form_meta        = self::empty_meta( $meta_field_keys );
				$form_price       = '';
			}
		}

		return self::build_result( $errors, $success_message, $form_title, $form_content, $form_category_id, $form_tag_ids, $form_tags_other, $form_featured_id, $form_price, $form_meta, $editing_post_id );
	}

	/**
	 * Pre-populate form fields from an existing listing.
	 *
	 * @param int   $editing_post_id
	 * @param array $meta_field_keys
	 *
	 * @return array|null Null when not applicable.
	 */
	public static function prepopulate_form( $editing_post_id, array $meta_field_keys ) {
		if ( ! $editing_post_id || 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return null;
		}

		$existing_post = get_post( $editing_post_id );
		if ( ! $existing_post || crocodevs_config( 'post_type.slug' ) !== $existing_post->post_type ) {
			return null;
		}

		$form_title       = $existing_post->post_title;
		$form_content     = $existing_post->post_content;
		$form_featured_id = (int) get_post_thumbnail_id( $existing_post );

		$existing_categories = wp_get_object_terms( $editing_post_id, crocodevs_config( 'taxonomies.category' ), array( 'fields' => 'ids' ) );
		$form_category_id    = ( ! is_wp_error( $existing_categories ) && ! empty( $existing_categories ) ) ? (int) $existing_categories[0] : 0;

		$existing_tags = wp_get_object_terms( $editing_post_id, crocodevs_config( 'taxonomies.tag' ), array( 'fields' => 'ids' ) );
		$form_tag_ids  = ! is_wp_error( $existing_tags ) ? array_map( 'intval', $existing_tags ) : array();

		$form_meta = array();
		foreach ( $meta_field_keys as $field_key ) {
			$meta_key   = ListingMeta::key( $field_key );
			$meta_value = get_post_meta( $editing_post_id, $meta_key, true );

			if ( ListingMeta::is_array_field( $field_key ) ) {
				$form_meta[ $field_key ] = is_array( $meta_value ) ? $meta_value : array();
			} else {
				$form_meta[ $field_key ] = is_string( $meta_value ) ? $meta_value : (string) $meta_value;
			}
		}

		$form_price = isset( $form_meta['listing_price'] ) ? $form_meta['listing_price'] : '';

		return self::build_result( array(), '', $form_title, $form_content, $form_category_id, $form_tag_ids, '', $form_featured_id, $form_price, $form_meta, $editing_post_id );
	}

	// ------------------------------------------------------------------
	// Validation rules & data extraction
	// ------------------------------------------------------------------

	/**
	 * Validation rules for the add/edit listing form.
	 *
	 * @return array<string, string>
	 */
	private static function submission_rules() {
		return array(
			'cb_listing_title'             => 'required|string|max:200',
			'cb_listing_content'           => 'nullable|string',
			'cb_listing_category'          => 'nullable|integer',
			'cb_listing_tags'              => 'nullable|array',
			'cb_listing_tags_other'        => 'nullable|string|max:500',
			'cb_listing_featured_image_id' => 'nullable|integer',
			'listing_price'                => 'nullable|string|max:50',
			'listing_contact_email'        => 'nullable|email',
			'listing_website'              => 'nullable|url',
			'listing_contact_phone'        => 'nullable|string|max:30',
		);
	}

	/**
	 * Extract and structure form data from $_POST for validation.
	 *
	 * @param array $meta_field_keys
	 *
	 * @return array
	 */
	private static function extract_form_data( array $meta_field_keys ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$data = array(
			'cb_listing_title'             => isset( $_POST['cb_listing_title'] ) ? wp_unslash( $_POST['cb_listing_title'] ) : '',
			'cb_listing_content'           => isset( $_POST['cb_listing_content'] ) ? wp_unslash( $_POST['cb_listing_content'] ) : '',
			'cb_listing_category'          => isset( $_POST['cb_listing_category'] ) ? absint( $_POST['cb_listing_category'] ) : 0,
			'cb_listing_tags'              => isset( $_POST['cb_listing_tags'] ) && is_array( $_POST['cb_listing_tags'] ) ? array_map( 'absint', wp_unslash( $_POST['cb_listing_tags'] ) ) : array(),
			'cb_listing_tags_other'        => isset( $_POST['cb_listing_tags_other'] ) ? wp_unslash( $_POST['cb_listing_tags_other'] ) : '',
			'cb_listing_featured_image_id' => isset( $_POST['cb_listing_featured_image_id'] ) ? absint( wp_unslash( $_POST['cb_listing_featured_image_id'] ) ) : 0,
		);

		$meta = array();
		foreach ( $meta_field_keys as $field_key ) {
			if ( 'listing_working_days' === $field_key ) {
				$meta[ $field_key ] = isset( $_POST['listing_working_days'] ) && is_array( $_POST['listing_working_days'] ) ? wp_unslash( $_POST['listing_working_days'] ) : array();
			} elseif ( isset( $_POST[ $field_key ] ) ) {
				$meta[ $field_key ] = wp_unslash( $_POST[ $field_key ] );
			} elseif ( 'listing_price' === $field_key && isset( $_POST['cb_listing_price'] ) ) {
				$meta[ $field_key ] = wp_unslash( $_POST['cb_listing_price'] );
			} else {
				$meta[ $field_key ] = ListingMeta::is_array_field( $field_key ) ? array() : '';
			}
		}
		// phpcs:enable

		$data['listing_price']         = isset( $meta['listing_price'] ) ? $meta['listing_price'] : '';
		$data['listing_contact_email'] = isset( $meta['listing_contact_email'] ) ? $meta['listing_contact_email'] : '';
		$data['listing_website']       = isset( $meta['listing_website'] ) ? $meta['listing_website'] : '';
		$data['listing_contact_phone'] = isset( $meta['listing_contact_phone'] ) ? $meta['listing_contact_phone'] : '';
		$data['_meta']                 = $meta;

		return $data;
	}

	// ------------------------------------------------------------------
	// Private helpers
	// ------------------------------------------------------------------

	/**
	 * @return array
	 */
	private static function empty_meta( array $meta_field_keys ) {
		$meta = array();
		foreach ( $meta_field_keys as $field_key ) {
			$meta[ $field_key ] = ListingMeta::is_array_field( $field_key ) ? array() : '';
		}
		return $meta;
	}

	/**
	 * Process comma-separated "other" tags into real taxonomy terms.
	 *
	 * @param int[]  $form_tag_ids
	 * @param string $form_tags_other
	 *
	 * @return int[]
	 */
	private static function process_other_tags( array $form_tag_ids, $form_tags_other ) {
		if ( '' === $form_tags_other ) {
			return $form_tag_ids;
		}

		$raw_tags = explode( ',', $form_tags_other );
		$extra    = array();

		foreach ( $raw_tags as $raw_tag ) {
			$tag_name = trim( $raw_tag );
			if ( '' === $tag_name ) {
				continue;
			}

			$existing = get_term_by( 'name', $tag_name, crocodevs_config( 'taxonomies.tag' ) );
			if ( $existing && ! is_wp_error( $existing ) ) {
				$extra[] = (int) $existing->term_id;
			} else {
				$created = wp_insert_term( $tag_name, crocodevs_config( 'taxonomies.tag' ) );
				if ( ! is_wp_error( $created ) && isset( $created['term_id'] ) ) {
					$extra[] = (int) $created['term_id'];
				}
			}
		}

		return ! empty( $extra ) ? array_unique( array_merge( $form_tag_ids, $extra ) ) : $form_tag_ids;
	}

	/**
	 * Create or update a listing post and sync meta / taxonomies / thumbnail.
	 *
	 * @return array{errors: string[], success_message: string, editing_post_id: int}
	 */
	private static function save_listing( $action, $form_title, $form_content, $editing_post_id, $form_category_id, $form_tag_ids, $form_meta, $form_featured_id, $meta_field_keys ) {
		$errors          = array();
		$success_message = '';
		$current_user    = wp_get_current_user();
		$post_id         = 0;

		if ( 'edit_listing' === $action ) {
			$editing_post_id = isset( $_POST['cb_listing_post_id'] ) ? absint( wp_unslash( $_POST['cb_listing_post_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $editing_post_id ) {
				$existing_post = get_post( $editing_post_id );
				if ( $existing_post && crocodevs_config( 'post_type.slug' ) === $existing_post->post_type
					&& ( current_user_can( 'manage_options' ) || (int) $existing_post->post_author === (int) $current_user->ID )
				) {
					$post_id       = $editing_post_id;
					$update_result = wp_update_post( array(
						'ID'           => $post_id,
						'post_title'   => $form_title,
						'post_content' => $form_content,
					), true );
					if ( is_wp_error( $update_result ) ) {
						$errors[] = $update_result->get_error_message();
					}
				} else {
					$errors[] = __( 'You are not allowed to edit this listing.', 'cb-listing-anything' );
				}
			} else {
				$errors[] = __( 'Invalid listing specified for editing.', 'cb-listing-anything' );
			}
		} else {
			$post_id = wp_insert_post( array(
				'post_type'    => crocodevs_config( 'post_type.slug' ),
				'post_title'   => $form_title,
				'post_content' => $form_content,
				'post_status'  => 'pending',
				'post_author'  => get_current_user_id(),
			), true );
			if ( is_wp_error( $post_id ) ) {
				$errors[] = $post_id->get_error_message();
				$post_id  = 0;
			}
		}

		if ( ! empty( $errors ) || ! $post_id ) {
			return array( 'errors' => $errors, 'success_message' => '', 'editing_post_id' => $editing_post_id );
		}

		// Sync taxonomies.
		wp_set_object_terms( $post_id, $form_category_id ? array( $form_category_id ) : array(), crocodevs_config( 'taxonomies.category' ), false );
		wp_set_object_terms( $post_id, ! empty( $form_tag_ids ) ? $form_tag_ids : array(), crocodevs_config( 'taxonomies.tag' ), false );

		// Sync meta.
		foreach ( $meta_field_keys as $field_key ) {
			$meta_key   = ListingMeta::key( $field_key );
			$meta_value = isset( $form_meta[ $field_key ] ) ? $form_meta[ $field_key ] : '';

			if ( ListingMeta::is_array_field( $field_key ) ) {
				update_post_meta( $post_id, $meta_key, is_array( $meta_value ) ? array_values( $meta_value ) : array() );
			} else {
				if ( '' !== $meta_value ) {
					update_post_meta( $post_id, $meta_key, $meta_value );
				} else {
					delete_post_meta( $post_id, $meta_key );
				}
			}
		}

		// Sync featured image.
		if ( $form_featured_id ) {
			set_post_thumbnail( $post_id, $form_featured_id );
		} else {
			delete_post_thumbnail( $post_id );
		}

		$success_message = 'edit_listing' === $action
			? __( 'Listing updated.', 'cb-listing-anything' )
			: __( 'Listing submitted for review.', 'cb-listing-anything' );

		return array( 'errors' => array(), 'success_message' => $success_message, 'editing_post_id' => $editing_post_id );
	}

	/**
	 * Package form state into a standard result array.
	 */
	private static function build_result( $errors, $success_message, $form_title, $form_content, $form_category_id, $form_tag_ids, $form_tags_other, $form_featured_id, $form_price, $form_meta, $editing_post_id ) {
		return array(
			'errors'           => $errors,
			'success_message'  => $success_message,
			'form_title'       => $form_title,
			'form_content'     => $form_content,
			'form_category_id' => $form_category_id,
			'form_tag_ids'     => $form_tag_ids,
			'form_tags_other'  => $form_tags_other,
			'form_featured_id' => $form_featured_id,
			'form_price'       => $form_price,
			'form_meta'        => $form_meta,
			'editing_post_id'  => $editing_post_id,
		);
	}
}
