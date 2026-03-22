<?php

namespace CBListingAnything\Rest;

use CBListingAnything\Controllers\SettingsController;
use CBListingAnything\Models\ListingMeta as ListingMetaModel;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class AdminSettingsController extends AbstractRestController {

	public function register_routes() {
		$ns = $this->rest_namespace();

		register_rest_route( $ns, '/admin/settings', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'can_manage' ),
			),
			array(
				'methods'             => 'PATCH',
				'callback'            => array( $this, 'patch_settings' ),
				'permission_callback' => array( $this, 'can_manage' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'patch_settings' ),
				'permission_callback' => array( $this, 'can_manage' ),
			),
		) );
	}

	public function can_manage() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		$settings = get_option( SettingsController::OPTION_KEY, SettingsController::defaults() );
		if ( ! is_array( $settings ) ) {
			$settings = SettingsController::defaults();
		}

		return new WP_REST_Response( array(
			'settings'     => $settings,
			'fieldGroups'  => $this->field_groups_payload(),
			'currencies'   => SettingsController::currencies(),
			'archiveUrl'   => home_url( '/' . SettingsController::get( 'listing_slug', 'cb_listing' ) . '/' ),
			'postType'     => crocodevs_config( 'post_type.slug' ),
		), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function patch_settings( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			$body = array();
		}

		$patch = array();
		if ( isset( $body['currency'] ) ) {
			$patch['currency'] = $body['currency'];
		}
		if ( isset( $body['listing_title'] ) ) {
			$patch['listing_title'] = $body['listing_title'];
		}
		if ( isset( $body['listing_slug'] ) ) {
			$patch['listing_slug'] = $body['listing_slug'];
		}
		if ( array_key_exists( 'enabled_fields', $body ) ) {
			$patch['enabled_fields'] = is_array( $body['enabled_fields'] ) ? $body['enabled_fields'] : array();
		}

		$result = SettingsController::merge_settings_patch( $patch );

		if ( ! empty( $result['errors'] ) ) {
			return new WP_Error(
				'cb_listing_settings_invalid',
				implode( ' ', $result['errors'] ),
				array( 'status' => 400, 'errors' => $result['errors'] )
			);
		}

		update_option( SettingsController::OPTION_KEY, $result['settings'] );

		return $this->get_settings();
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function field_groups_payload() {
		$grouped    = ListingMetaModel::fields_by_category();
		$categories = ListingMetaModel::categories();
		$out        = array();

		foreach ( $categories as $slug => $cat ) {
			if ( empty( $grouped[ $slug ]['fields'] ) ) {
				continue;
			}
			$fields = array();
			foreach ( $grouped[ $slug ]['fields'] as $key => $def ) {
				$type = isset( $def['type'] ) ? $def['type'] : 'text';
				$fields[] = array(
					'key'       => $key,
					'label'     => isset( $def['label'] ) ? $def['label'] : $key,
					'type'      => $type,
					'typeLabel' => ListingMetaModel::get_type_label( $type ),
				);
			}
			$out[] = array(
				'id'     => $slug,
				'label'  => isset( $cat['label'] ) ? $cat['label'] : $slug,
				'fields' => $fields,
			);
		}

		return $out;
	}
}
