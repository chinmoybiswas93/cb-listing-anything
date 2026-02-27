<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Core\AbstractController;

/**
 * Media-related hooks for front-end features.
 */
class MediaController extends AbstractController {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_dashboard_gallery_attachments' ) );
	}

	/**
	 * Limit dashboard gallery media frame to current user's uploads.
	 *
	 * @param array $query Attachment query args.
	 * @return array
	 */
	public function filter_dashboard_gallery_attachments( $query ) {
		if ( empty( $query['cbld_dashboard_gallery'] ) ) {
			return $query;
		}

		if ( ! is_user_logged_in() ) {
			return $query;
		}

		$query['author'] = get_current_user_id();

		return $query;
	}
}

