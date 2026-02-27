<?php
/**
 * Render the CB Listing User Dashboard block.
 *
 * @package CBListingAnything
 */

use CBListingAnything\Models\ListingMeta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle login submissions when user is not logged in.
$login_errors   = array();
$login_username = '';

if ( ! is_user_logged_in() && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['cb_listing_user_dashboard_login'] ) && '1' === $_POST['cb_listing_user_dashboard_login'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$login_username = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$login_password = isset( $_POST['pwd'] ) ? wp_unslash( $_POST['pwd'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! isset( $_POST['_cb_listing_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_cb_listing_login_nonce'] ) ), 'cb_listing_user_login' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$login_errors[] = __( 'Security check failed. Please try again.', 'cb-listing-anything' );
	} elseif ( '' === $login_username || '' === $login_password ) {
		$login_errors[] = __( 'Please enter both username (or email) and password.', 'cb-listing-anything' );
	} else {
		$creds = array(
			'user_login'    => $login_username,
			'user_password' => $login_password,
			'remember'      => ! empty( $_POST['rememberme'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);

		// Let WordPress decide whether to use a secure cookie based on the current context.
		$user = wp_signon( $creds );

		if ( is_wp_error( $user ) ) {
			$login_errors[] = $user->get_error_message();
		} else {
			wp_safe_redirect( get_permalink() );
			exit;
		}
	}
}

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'cb-listing-user-dashboard',
	)
);

// If user is not logged in, show login form.
if ( ! is_user_logged_in() ) :
	?>
	<div <?php echo $wrapper; ?>>
		<div class="cb-listing-user-dashboard__login-layout">
			<h2 class="cb-listing-user-dashboard__login-title">
				<?php esc_html_e( 'Please Login', 'cb-listing-anything' ); ?>
			</h2>
			<div class="cb-listing-user-dashboard__login">
				<?php if ( ! empty( $login_errors ) ) : ?>
					<div class="cb-listing-user-dashboard__notice cb-listing-user-dashboard__notice--error">
						<ul>
							<?php foreach ( $login_errors as $message ) : ?>
								<li><?php echo wp_kses_post( $message ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<form name="loginform" id="loginform" action="<?php echo esc_url( get_permalink() ); ?>" method="post">
					<?php wp_nonce_field( 'cb_listing_user_login', '_cb_listing_login_nonce' ); ?>
					<input type="hidden" name="cb_listing_user_dashboard_login" value="1" />

					<p class="login-username">
						<label for="user_login"><?php esc_html_e( 'Username or Email Address', 'cb-listing-anything' ); ?></label>
						<input type="text" name="log" id="user_login" autocomplete="username" class="input" value="<?php echo esc_attr( $login_username ); ?>" size="20" />
					</p>

					<p class="login-password">
						<label for="user_pass"><?php esc_html_e( 'Password', 'cb-listing-anything' ); ?></label>
						<input type="password" name="pwd" id="user_pass" autocomplete="current-password" class="input" value="" size="20" />
					</p>

					<p class="login-remember">
						<label>
							<input name="rememberme" type="checkbox" id="rememberme" value="forever" />
							<?php esc_html_e( 'Remember Me', 'cb-listing-anything' ); ?>
						</label>
					</p>

					<p class="login-submit">
						<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="<?php esc_attr_e( 'Log In', 'cb-listing-anything' ); ?>" />
					</p>
				</form>
			</div>
		</div>
	</div>
	<?php
	return;
endif;

$current_user = wp_get_current_user();
$tab          = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'profile'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// Only admins and List Contributors can submit listings via the dashboard.
$user_roles = (array) $current_user->roles;
$can_submit = current_user_can( 'manage_options' ) || in_array( 'cb_listing_contributor', $user_roles, true );

if ( ! in_array( $tab, array( 'profile', 'add', 'listings' ), true ) ) {
	$tab = 'profile';
}

// Base URL used for tab and action links.
$base_url = remove_query_arg(
	array(
		'tab',
		'edit_listing',
		'cbld_page',
		'cbld_msg',
	)
);

// Initialise form state.
$errors           = array();
$success_message  = '';
$form_title       = '';
$form_content     = '';
$form_category_id = 0;
$form_tag_ids     = array();
$form_tags_other  = '';

// Meta fields mirror the admin meta box configuration.
$meta_field_keys = ListingMeta::fields();
$form_meta       = array();

foreach ( $meta_field_keys as $field_key ) {
	if ( ListingMeta::is_array_field( $field_key ) ) {
		$form_meta[ $field_key ] = array();
	} else {
		$form_meta[ $field_key ] = '';
	}
}

// Detect edit context from query string.
$editing_post_id = 0;

if ( isset( $_GET['edit_listing'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$maybe_id = absint( wp_unslash( $_GET['edit_listing'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $maybe_id ) {
		$maybe_post = get_post( $maybe_id );

		if ( $maybe_post && 'cb_listing' === $maybe_post->post_type ) {
			if ( current_user_can( 'manage_options' ) || (int) $maybe_post->post_author === (int) $current_user->ID ) {
				$editing_post_id = $maybe_id;
			}
		}
	}
}

// If user clicked Edit from listings tab, switch to Add tab to reuse the form.
if ( $editing_post_id && 'listings' === $tab ) {
	$tab = 'add';
}

// Handle dashboard actions (e.g., delete listing) regardless of active tab.
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$dashboard_action = isset( $_POST['cb_listing_user_dashboard_action'] ) ? sanitize_key( wp_unslash( $_POST['cb_listing_user_dashboard_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'delete_listing' === $dashboard_action ) {
		$delete_post_id = isset( $_POST['cb_listing_post_id'] ) ? absint( wp_unslash( $_POST['cb_listing_post_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! isset( $_POST['_cb_listing_delete_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_cb_listing_delete_nonce'] ) ), 'cb_listing_delete_listing' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$redirect_url = add_query_arg(
				array(
					'tab'      => 'listings',
					'cbld_msg' => 'delete_failed',
				),
				$base_url
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}

		if ( $delete_post_id && current_user_can( 'delete_post', $delete_post_id ) ) {
			wp_trash_post( $delete_post_id );

			$redirect_url = add_query_arg(
				array(
					'tab'      => 'listings',
					'cbld_msg' => 'deleted',
				),
				$base_url
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$redirect_url = add_query_arg(
			array(
				'tab'      => 'listings',
				'cbld_msg' => 'delete_failed',
			),
			$base_url
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}
}

// Handle Add / Edit Listing form submission.
if ( 'add' === $tab && 'POST' === $_SERVER['REQUEST_METHOD'] && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$dashboard_action = isset( $_POST['cb_listing_user_dashboard_action'] ) ? sanitize_key( wp_unslash( $_POST['cb_listing_user_dashboard_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( in_array( $dashboard_action, array( 'add_listing', 'edit_listing' ), true ) ) {
		if ( ! isset( $_POST['_cb_listing_add_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_cb_listing_add_nonce'] ) ), 'cb_listing_add_listing' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$errors[] = __( 'Security check failed. Please try again.', 'cb-listing-anything' );
		} elseif ( ! $can_submit ) {
			$errors[] = __( 'You are not allowed to submit listings from this account.', 'cb-listing-anything' );
		} else {
			$form_title       = isset( $_POST['cb_listing_title'] ) ? sanitize_text_field( wp_unslash( $_POST['cb_listing_title'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_content     = isset( $_POST['cb_listing_content'] ) ? wp_kses_post( wp_unslash( $_POST['cb_listing_content'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_category_id = isset( $_POST['cb_listing_category'] ) ? absint( $_POST['cb_listing_category'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_tag_ids     = isset( $_POST['cb_listing_tags'] ) && is_array( $_POST['cb_listing_tags'] ) ? array_map( 'absint', wp_unslash( $_POST['cb_listing_tags'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_tags_other  = isset( $_POST['cb_listing_tags_other'] ) ? sanitize_text_field( wp_unslash( $_POST['cb_listing_tags_other'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// Populate meta values from request.
			foreach ( $meta_field_keys as $field_key ) {
				if ( 'listing_working_days' === $field_key ) {
					$raw_value = isset( $_POST['listing_working_days'] ) && is_array( $_POST['listing_working_days'] ) ? wp_unslash( $_POST['listing_working_days'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				} elseif ( isset( $_POST[ $field_key ] ) ) {
					$raw_value = wp_unslash( $_POST[ $field_key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				} elseif ( 'listing_price' === $field_key && isset( $_POST['cb_listing_price'] ) ) {
					// Back-compat with previous price field name.
					$raw_value = wp_unslash( $_POST['cb_listing_price'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				} else {
					$raw_value = ListingMeta::is_array_field( $field_key ) ? array() : '';
				}

				$form_meta[ $field_key ] = ListingMeta::sanitize( $field_key, $raw_value );
			}

			// Normalise price alias for existing form code.
			$form_price = isset( $form_meta['listing_price'] ) ? $form_meta['listing_price'] : '';

			if ( '' === $form_title ) {
				$errors[] = __( 'Please enter a title for your listing.', 'cb-listing-anything' );
			}

			// Process \"Other\" comma-separated tags into real terms.
			if ( '' !== $form_tags_other ) {
				$extra_tag_ids = array();
				$raw_tags      = explode( ',', $form_tags_other );

				foreach ( $raw_tags as $raw_tag ) {
					$tag_name = trim( $raw_tag );

					if ( '' === $tag_name ) {
						continue;
					}

					$existing = get_term_by( 'name', $tag_name, 'cb_listing_tag' );

					if ( $existing && ! is_wp_error( $existing ) ) {
						$extra_tag_ids[] = (int) $existing->term_id;
					} else {
						$created = wp_insert_term( $tag_name, 'cb_listing_tag' );

						if ( ! is_wp_error( $created ) && isset( $created['term_id'] ) ) {
							$extra_tag_ids[] = (int) $created['term_id'];
						}
					}
				}

				if ( ! empty( $extra_tag_ids ) ) {
					$form_tag_ids = array_unique( array_merge( $form_tag_ids, $extra_tag_ids ) );
				}
			}

			if ( empty( $errors ) ) {
				$post_id = 0;

				if ( 'edit_listing' === $dashboard_action ) {
					// For edits, trust the hidden post ID and re-validate permissions.
					$editing_post_id = isset( $_POST['cb_listing_post_id'] ) ? absint( wp_unslash( $_POST['cb_listing_post_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

					if ( $editing_post_id ) {
						$existing_post = get_post( $editing_post_id );

						if ( $existing_post && 'cb_listing' === $existing_post->post_type && ( current_user_can( 'manage_options' ) || (int) $existing_post->post_author === (int) $current_user->ID ) ) {
							$post_id = $editing_post_id;

							$update_result = wp_update_post(
								array(
									'ID'           => $post_id,
									'post_title'   => $form_title,
									'post_content' => $form_content,
								),
								true
							);

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
					$post_id = wp_insert_post(
						array(
							'post_type'    => 'cb_listing',
							'post_title'   => $form_title,
							'post_content' => $form_content,
							'post_status'  => 'pending',
							'post_author'  => get_current_user_id(),
						),
						true
					);

					if ( is_wp_error( $post_id ) ) {
						$errors[] = $post_id->get_error_message();
					}
				}

			}

			if ( empty( $errors ) && $post_id ) {
				// Sync taxonomies.
				if ( $form_category_id ) {
					wp_set_object_terms( $post_id, array( $form_category_id ), 'cb_listing_category', false );
				} else {
					wp_set_object_terms( $post_id, array(), 'cb_listing_category', false );
				}

				if ( ! empty( $form_tag_ids ) ) {
					wp_set_object_terms( $post_id, $form_tag_ids, 'cb_listing_tag', false );
				} else {
					wp_set_object_terms( $post_id, array(), 'cb_listing_tag', false );
				}

				// Sync meta fields using shared config.
				foreach ( $meta_field_keys as $field_key ) {
					$meta_key   = ListingMeta::key( $field_key );
					$meta_value = isset( $form_meta[ $field_key ] ) ? $form_meta[ $field_key ] : '';

					if ( ListingMeta::is_array_field( $field_key ) ) {
						$meta_value = is_array( $meta_value ) ? array_values( $meta_value ) : array();
						update_post_meta( $post_id, $meta_key, $meta_value );
					} else {
						if ( '' !== $meta_value ) {
							update_post_meta( $post_id, $meta_key, $meta_value );
						} else {
							delete_post_meta( $post_id, $meta_key );
						}
					}
				}

				if ( 'edit_listing' === $dashboard_action ) {
					$success_message = __( 'Listing updated.', 'cb-listing-anything' );
				} else {
					$success_message  = __( 'Listing submitted for review.', 'cb-listing-anything' );
					$form_title       = '';
					$form_content     = '';
					$form_category_id = 0;
					$form_tag_ids     = array();
					$form_tags_other  = '';

					foreach ( $meta_field_keys as $field_key ) {
						if ( ListingMeta::is_array_field( $field_key ) ) {
							$form_meta[ $field_key ] = array();
						} else {
							$form_meta[ $field_key ] = '';
						}
					}
				}
			}
		}
	}
}

// When editing an existing listing, pre-populate the form from its current data on initial load.
if ( $editing_post_id && 'add' === $tab && 'POST' !== $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$existing_post = get_post( $editing_post_id );

	if ( $existing_post && 'cb_listing' === $existing_post->post_type ) {
		$form_title   = $existing_post->post_title;
		$form_content = $existing_post->post_content;

		$existing_categories = wp_get_object_terms(
			$editing_post_id,
			'cb_listing_category',
			array(
				'fields' => 'ids',
			)
		);

		if ( ! is_wp_error( $existing_categories ) && ! empty( $existing_categories ) ) {
			$form_category_id = (int) $existing_categories[0];
		}

		$existing_tags = wp_get_object_terms(
			$editing_post_id,
			'cb_listing_tag',
			array(
				'fields' => 'ids',
			)
		);

		if ( ! is_wp_error( $existing_tags ) ) {
			$form_tag_ids = array_map( 'intval', $existing_tags );
		}

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
	}
}

// Preload taxonomies for the add tab form.
$categories = array();
$tags       = array();

if ( 'add' === $tab ) {
	$category_terms = get_terms(
		array(
			'taxonomy'   => 'cb_listing_category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	if ( ! is_wp_error( $category_terms ) ) {
		$categories = $category_terms;
	}

	$tag_terms = get_terms(
		array(
			'taxonomy'   => 'cb_listing_tag',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	if ( ! is_wp_error( $tag_terms ) ) {
		$tags = $tag_terms;
	}
}
?>
<div <?php echo $wrapper; ?>>
	<div class="cb-listing-user-dashboard__header">
		<h2 class="cb-listing-user-dashboard__greeting">
			<?php
			printf(
				/* translators: %s: current user display name. */
				esc_html__( 'Hello, %s', 'cb-listing-anything' ),
				esc_html( $current_user->display_name )
			);
			?>
		</h2>
	</div>
	<div class="cb-listing-user-dashboard__inner">
		<aside class="cb-listing-user-dashboard__nav">
			<ul class="cb-listing-user-dashboard__nav-list">
				<li class="cb-listing-user-dashboard__nav-item <?php echo 'profile' === $tab ? 'cb-listing-user-dashboard__nav-item--active' : ''; ?>">
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'profile', $base_url ) ); ?>">
						<?php esc_html_e( 'Profile', 'cb-listing-anything' ); ?>
					</a>
				</li>
				<li class="cb-listing-user-dashboard__nav-item <?php echo 'add' === $tab ? 'cb-listing-user-dashboard__nav-item--active' : ''; ?>">
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'add', $base_url ) ); ?>">
						<?php esc_html_e( 'Add Listing', 'cb-listing-anything' ); ?>
					</a>
				</li>
				<li class="cb-listing-user-dashboard__nav-item <?php echo 'listings' === $tab ? 'cb-listing-user-dashboard__nav-item--active' : ''; ?>">
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'listings', $base_url ) ); ?>">
						<?php esc_html_e( 'My Listings', 'cb-listing-anything' ); ?>
					</a>
				</li>
				<li class="cb-listing-user-dashboard__nav-item cb-listing-user-dashboard__nav-item--logout">
					<a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>">
						<?php esc_html_e( 'Log out', 'cb-listing-anything' ); ?>
					</a>
				</li>
			</ul>
		</aside>

		<section class="cb-listing-user-dashboard__content">
<?php if ( 'profile' === $tab ) : ?>
				<div class="cb-listing-user-dashboard__section cb-listing-user-dashboard__section--profile">
					<h3><?php esc_html_e( 'Profile details', 'cb-listing-anything' ); ?></h3>
					<ul class="cb-listing-user-dashboard__profile-list">
						<li class="cb-listing-user-dashboard__profile-field">
							<strong><?php esc_html_e( 'Name', 'cb-listing-anything' ); ?>:</strong>
							<span><?php echo esc_html( $current_user->display_name ); ?></span>
						</li>
						<li class="cb-listing-user-dashboard__profile-field">
							<strong><?php esc_html_e( 'Username', 'cb-listing-anything' ); ?>:</strong>
							<span><?php echo esc_html( $current_user->user_login ); ?></span>
						</li>
						<li class="cb-listing-user-dashboard__profile-field">
							<strong><?php esc_html_e( 'Email', 'cb-listing-anything' ); ?>:</strong>
							<span><?php echo esc_html( $current_user->user_email ); ?></span>
						</li>
					</ul>
				</div>
			<?php elseif ( 'add' === $tab ) : ?>
				<div class="cb-listing-user-dashboard__section cb-listing-user-dashboard__section--add">
					<h3>
						<?php
						if ( $editing_post_id ) {
							esc_html_e( 'Edit listing', 'cb-listing-anything' );
						} else {
							esc_html_e( 'Add new listing', 'cb-listing-anything' );
						}
						?>
					</h3>

					<?php if ( ! empty( $errors ) ) : ?>
						<div class="cb-listing-user-dashboard__notice cb-listing-user-dashboard__notice--error">
							<ul>
								<?php foreach ( $errors as $message ) : ?>
									<li><?php echo esc_html( $message ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php elseif ( $success_message ) : ?>
						<div class="cb-listing-user-dashboard__notice cb-listing-user-dashboard__notice--success">
							<?php echo esc_html( $success_message ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $can_submit ) : ?>
					<?php
					if ( function_exists( 'wp_enqueue_editor' ) ) {
						wp_enqueue_editor();
					}

					if ( function_exists( 'wp_enqueue_media' ) ) {
						wp_enqueue_media();
					}

					$gallery_title  = esc_js( __( 'Select Gallery Images', 'cb-listing-anything' ) );
					$gallery_button = esc_js( __( 'Add to Gallery', 'cb-listing-anything' ) );
					$remove_label   = esc_js( __( 'Remove', 'cb-listing-anything' ) );

					$gallery_js = <<<JS
;(function($) {
	var frame;
	var preview = $('#cb-listing-dashboard-gallery-preview');
	var input = $('#cb-listing-dashboard-gallery');

	function updateInput() {
		var ids = [];
		preview['find']('.cb-listing-user-dashboard__gallery-item')['each'](function() {
			ids.push( $(this)['data']('id') );
		});
		input['val']( ids['join'](',') );
	}

	$('#cb-listing-dashboard-gallery-add')['on']('click', function(e) {
		e['preventDefault']();
		if ( frame ) { frame['open'](); return; }
		frame = wp['media']({
			title: '{$gallery_title}',
			button: { text: '{$gallery_button}' },
			multiple: true,
			library: { type: 'image' }
		});

		frame['on']('select', function() {
			var selection = frame['state']()['get']('selection');
			selection['each'](function(attachment) {
				var data = attachment['toJSON']();
				var thumb = data['sizes'] && data['sizes']['thumbnail'] ? data['sizes']['thumbnail']['url'] : data['url'];
				preview['append'](
					'<div class="cb-listing-user-dashboard__gallery-item" data-id="' + data['id'] + '">' +
						'<img src="' + thumb + '" alt="" />' +
						'<button type="button" class="cb-listing-user-dashboard__gallery-remove" aria-label="{$remove_label}">&times;</button>' +
					'</div>'
				);
			});
			updateInput();
		});
		frame['open']();
	});

	preview['on']('click', '.cb-listing-user-dashboard__gallery-remove', function(e) {
		e['preventDefault']();
		$(this)['closest']('.cb-listing-user-dashboard__gallery-item')['remove']();
		updateInput();
	});
})(jQuery);
JS;

					wp_register_script( 'cb-listing-user-dashboard-gallery', '', array( 'jquery', 'media-views' ), CB_LISTING_ANYTHING_VERSION, true );
					wp_enqueue_script( 'cb-listing-user-dashboard-gallery' );
					wp_add_inline_script( 'cb-listing-user-dashboard-gallery', $gallery_js );
					?>
					<form class="cb-listing-user-dashboard__form" method="post">
						<?php wp_nonce_field( 'cb_listing_add_listing', '_cb_listing_add_nonce' ); ?>
						<input type="hidden" name="cb_listing_user_dashboard_action" value="<?php echo $editing_post_id ? 'edit_listing' : 'add_listing'; ?>" />
						<?php if ( $editing_post_id ) : ?>
							<input type="hidden" name="cb_listing_post_id" value="<?php echo esc_attr( $editing_post_id ); ?>" />
						<?php endif; ?>

						<div class="cb-listing-user-dashboard__field">
							<label for="cb-listing-title">
								<?php esc_html_e( 'Listing title', 'cb-listing-anything' ); ?>
								<span class="cb-listing-user-dashboard__required">*</span>
							</label>
							<input
								type="text"
								id="cb-listing-title"
								name="cb_listing_title"
								value="<?php echo esc_attr( $form_title ); ?>"
								required
							/>
						</div>

						<div class="cb-listing-user-dashboard__field">
							<label for="cb-listing-content">
								<?php esc_html_e( 'Description', 'cb-listing-anything' ); ?>
							</label>
							<?php
							wp_editor(
								$form_content,
								'cb_listing_content',
								array(
									'textarea_name' => 'cb_listing_content',
									'textarea_rows' => 6,
									'media_buttons' => false,
									'teeny'         => true,
									'quicktags'     => true,
								)
							);
							?>
						</div>

						<div class="cb-listing-user-dashboard__field">
							<label for="cb-listing-category">
								<?php esc_html_e( 'Category', 'cb-listing-anything' ); ?>
							</label>
							<select id="cb-listing-category" name="cb_listing_category">
								<option value=""><?php esc_html_e( 'Select category', 'cb-listing-anything' ); ?></option>
								<?php foreach ( $categories as $category ) : ?>
									<option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $form_category_id, $category->term_id ); ?>>
										<?php echo esc_html( $category->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<?php if ( ! empty( $tags ) ) : ?>
							<div class="cb-listing-user-dashboard__field">
								<span class="cb-listing-user-dashboard__label">
									<?php esc_html_e( 'Tags', 'cb-listing-anything' ); ?>
								</span>
								<div class="cb-listing-user-dashboard__tags">
									<?php foreach ( $tags as $tag ) : ?>
										<label class="cb-listing-user-dashboard__tag">
											<input
												type="checkbox"
												name="cb_listing_tags[]"
												value="<?php echo esc_attr( $tag->term_id ); ?>"
												<?php checked( in_array( $tag->term_id, $form_tag_ids, true ), true ); ?>
											/>
											<span><?php echo esc_html( $tag->name ); ?></span>
										</label>
									<?php endforeach; ?>
									<label class="cb-listing-user-dashboard__tag cb-listing-user-dashboard__tag--other">
										<input
											type="checkbox"
											name="cb_listing_tags_other_toggle"
											value="1"
										/>
										<span><?php esc_html_e( 'Other', 'cb-listing-anything' ); ?></span>
										<input
											type="text"
											name="cb_listing_tags_other"
											class="cb-listing-user-dashboard__tags-other-input"
											placeholder="<?php esc_attr_e( 'Custom tags, comma separated', 'cb-listing-anything' ); ?>"
											value="<?php echo esc_attr( $form_tags_other ); ?>"
										/>
									</label>
								</div>
							</div>
						<?php endif; ?>

						<div class="cb-listing-user-dashboard__group">
							<h4 class="cb-listing-user-dashboard__group-title">
								<?php esc_html_e( 'General', 'cb-listing-anything' ); ?>
							</h4>
							<div class="cb-listing-user-dashboard__group-grid">
								<div class="cb-listing-user-dashboard__field cb-listing-user-dashboard__field--inline">
									<label for="cb-listing-price">
										<?php esc_html_e( 'Price', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="text"
										id="cb-listing-price"
										name="listing_price"
										value="<?php echo esc_attr( $form_price ); ?>"
										placeholder="<?php esc_attr_e( 'e.g. 199500', 'cb-listing-anything' ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-location">
										<?php esc_html_e( 'Location', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="text"
										id="cb-listing-location"
										name="listing_location"
										value="<?php echo esc_attr( $form_meta['listing_location'] ); ?>"
										placeholder="<?php esc_attr_e( 'e.g. New York, NY', 'cb-listing-anything' ); ?>"
									/>
								</div>
							</div>
						</div>

						<div class="cb-listing-user-dashboard__group">
							<h4 class="cb-listing-user-dashboard__group-title">
								<?php esc_html_e( 'Address', 'cb-listing-anything' ); ?>
							</h4>
							<div class="cb-listing-user-dashboard__group-grid">
								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-address">
										<?php esc_html_e( 'Street address', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="text"
										id="cb-listing-address"
										name="listing_address"
										value="<?php echo esc_attr( $form_meta['listing_address'] ); ?>"
										placeholder="<?php esc_attr_e( 'Street address', 'cb-listing-anything' ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-city">
										<?php esc_html_e( 'City', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="text"
										id="cb-listing-city"
										name="listing_city"
										value="<?php echo esc_attr( $form_meta['listing_city'] ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-state">
										<?php esc_html_e( 'State / Province', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="text"
										id="cb-listing-state"
										name="listing_state"
										value="<?php echo esc_attr( $form_meta['listing_state'] ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-zip-code">
										<?php esc_html_e( 'ZIP / Postal Code', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="text"
										id="cb-listing-zip-code"
										name="listing_zip_code"
										value="<?php echo esc_attr( $form_meta['listing_zip_code'] ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-country">
										<?php esc_html_e( 'Country', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="text"
										id="cb-listing-country"
										name="listing_country"
										value="<?php echo esc_attr( $form_meta['listing_country'] ); ?>"
									/>
								</div>
							</div>
						</div>

						<div class="cb-listing-user-dashboard__group">
							<h4 class="cb-listing-user-dashboard__group-title">
								<?php esc_html_e( 'Contact', 'cb-listing-anything' ); ?>
							</h4>
							<div class="cb-listing-user-dashboard__group-grid">
								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-contact-email">
										<?php esc_html_e( 'Contact email', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="email"
										id="cb-listing-contact-email"
										name="listing_contact_email"
										value="<?php echo esc_attr( $form_meta['listing_contact_email'] ); ?>"
										placeholder="<?php esc_attr_e( 'contact@example.com', 'cb-listing-anything' ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-contact-phone">
										<?php esc_html_e( 'Contact phone', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="tel"
										id="cb-listing-contact-phone"
										name="listing_contact_phone"
										value="<?php echo esc_attr( $form_meta['listing_contact_phone'] ); ?>"
										placeholder="<?php esc_attr_e( '+1 (555) 123-4567', 'cb-listing-anything' ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-website">
										<?php esc_html_e( 'Website', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="url"
										id="cb-listing-website"
										name="listing_website"
										value="<?php echo esc_attr( $form_meta['listing_website'] ); ?>"
										placeholder="<?php esc_attr_e( 'https://example.com', 'cb-listing-anything' ); ?>"
									/>
								</div>
							</div>
						</div>

						<div class="cb-listing-user-dashboard__group">
							<h4 class="cb-listing-user-dashboard__group-title">
								<?php esc_html_e( 'Social links', 'cb-listing-anything' ); ?>
							</h4>
							<div class="cb-listing-user-dashboard__group-grid">
								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-social-facebook">
										<?php esc_html_e( 'Facebook', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="url"
										id="cb-listing-social-facebook"
										name="listing_social_facebook"
										value="<?php echo esc_attr( $form_meta['listing_social_facebook'] ); ?>"
										placeholder="<?php esc_attr_e( 'https://facebook.com/...', 'cb-listing-anything' ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-social-twitter">
										<?php esc_html_e( 'Twitter / X', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="url"
										id="cb-listing-social-twitter"
										name="listing_social_twitter"
										value="<?php echo esc_attr( $form_meta['listing_social_twitter'] ); ?>"
										placeholder="<?php esc_attr_e( 'https://x.com/...', 'cb-listing-anything' ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-social-instagram">
										<?php esc_html_e( 'Instagram', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="url"
										id="cb-listing-social-instagram"
										name="listing_social_instagram"
										value="<?php echo esc_attr( $form_meta['listing_social_instagram'] ); ?>"
										placeholder="<?php esc_attr_e( 'https://instagram.com/...', 'cb-listing-anything' ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-social-linkedin">
										<?php esc_html_e( 'LinkedIn', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="url"
										id="cb-listing-social-linkedin"
										name="listing_social_linkedin"
										value="<?php echo esc_attr( $form_meta['listing_social_linkedin'] ); ?>"
										placeholder="<?php esc_attr_e( 'https://linkedin.com/...', 'cb-listing-anything' ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-social-youtube">
										<?php esc_html_e( 'YouTube', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="url"
										id="cb-listing-social-youtube"
										name="listing_social_youtube"
										value="<?php echo esc_attr( $form_meta['listing_social_youtube'] ); ?>"
										placeholder="<?php esc_attr_e( 'https://youtube.com/...', 'cb-listing-anything' ); ?>"
									/>
								</div>
							</div>
						</div>

						<div class="cb-listing-user-dashboard__group">
							<h4 class="cb-listing-user-dashboard__group-title">
								<?php esc_html_e( 'Business hours', 'cb-listing-anything' ); ?>
							</h4>
							<div class="cb-listing-user-dashboard__group-grid">
								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-opening-time">
										<?php esc_html_e( 'Opening time', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="time"
										id="cb-listing-opening-time"
										name="listing_opening_time"
										value="<?php echo esc_attr( $form_meta['listing_opening_time'] ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field">
									<label for="cb-listing-closing-time">
										<?php esc_html_e( 'Closing time', 'cb-listing-anything' ); ?>
									</label>
									<input
										type="time"
										id="cb-listing-closing-time"
										name="listing_closing_time"
										value="<?php echo esc_attr( $form_meta['listing_closing_time'] ); ?>"
									/>
								</div>

								<div class="cb-listing-user-dashboard__field cb-listing-user-dashboard__field--full">
									<span class="cb-listing-user-dashboard__label">
										<?php esc_html_e( 'Working days', 'cb-listing-anything' ); ?>
									</span>
									<div class="cb-listing-user-dashboard__tags">
										<?php foreach ( ListingMeta::working_days_options() as $day_key => $day_label ) : ?>
											<label class="cb-listing-user-dashboard__tag">
												<input
													type="checkbox"
													name="listing_working_days[]"
													value="<?php echo esc_attr( $day_key ); ?>"
													<?php
													$selected_days = isset( $form_meta['listing_working_days'] ) && is_array( $form_meta['listing_working_days'] ) ? $form_meta['listing_working_days'] : array();
													checked( in_array( $day_key, $selected_days, true ), true );
													?>
												/>
												<span><?php echo esc_html( $day_label ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						</div>

						<?php
						$dashboard_gallery_ids = array();

						if ( isset( $form_meta['listing_gallery'] ) && '' !== $form_meta['listing_gallery'] ) {
							$dashboard_gallery_ids = array_filter( array_map( 'absint', explode( ',', $form_meta['listing_gallery'] ) ) );
						}
						?>

						<div class="cb-listing-user-dashboard__group">
							<h4 class="cb-listing-user-dashboard__group-title">
								<?php esc_html_e( 'Media', 'cb-listing-anything' ); ?>
							</h4>
							<div class="cb-listing-user-dashboard__group-grid">
								<div class="cb-listing-user-dashboard__field cb-listing-user-dashboard__field--full">
									<span class="cb-listing-user-dashboard__label">
										<?php esc_html_e( 'Gallery', 'cb-listing-anything' ); ?>
									</span>
									<div class="cb-listing-user-dashboard__gallery">
										<div class="cb-listing-user-dashboard__gallery-preview" id="cb-listing-dashboard-gallery-preview">
											<?php foreach ( $dashboard_gallery_ids as $img_id ) :
												$thumb = wp_get_attachment_image_url( $img_id, 'thumbnail' );
												if ( ! $thumb ) {
													continue;
												}
												?>
												<div class="cb-listing-user-dashboard__gallery-item" data-id="<?php echo esc_attr( $img_id ); ?>">
													<img src="<?php echo esc_url( $thumb ); ?>" alt="" />
													<button type="button" class="cb-listing-user-dashboard__gallery-remove" aria-label="<?php esc_attr_e( 'Remove', 'cb-listing-anything' ); ?>">&times;</button>
												</div>
											<?php endforeach; ?>
										</div>
										<input type="hidden" id="cb-listing-dashboard-gallery" name="listing_gallery" value="<?php echo esc_attr( $form_meta['listing_gallery'] ); ?>" />
										<button type="button" class="button cb-listing-user-dashboard__gallery-add" id="cb-listing-dashboard-gallery-add">
											<?php esc_html_e( 'Add Images', 'cb-listing-anything' ); ?>
										</button>
									</div>
								</div>
							</div>
						</div>

						<div class="cb-listing-user-dashboard__actions">
							<button type="submit" class="cb-listing-user-dashboard__submit">
								<?php esc_html_e( 'Submit listing for review', 'cb-listing-anything' ); ?>
							</button>
						</div>
					</form>
					<?php else : ?>
						<p><?php esc_html_e( 'You do not have permission to submit listings from this account.', 'cb-listing-anything' ); ?></p>
					<?php endif; ?>
				</div>
			<?php elseif ( 'listings' === $tab ) : ?>
				<div class="cb-listing-user-dashboard__section cb-listing-user-dashboard__section--listings">
					<h3><?php esc_html_e( 'My listings', 'cb-listing-anything' ); ?></h3>
					<?php
					$listings_notice_type = '';
					$listings_notice_text = '';

					if ( isset( $_GET['cbld_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$msg = sanitize_key( wp_unslash( $_GET['cbld_msg'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

						if ( 'deleted' === $msg ) {
							$listings_notice_type = 'success';
							$listings_notice_text = __( 'Listing moved to trash.', 'cb-listing-anything' );
						} elseif ( 'delete_failed' === $msg ) {
							$listings_notice_type = 'error';
							$listings_notice_text = __( 'Unable to delete the listing. Please try again.', 'cb-listing-anything' );
						}
					}

					if ( $listings_notice_type && $listings_notice_text ) :
						?>
						<div class="cb-listing-user-dashboard__notice cb-listing-user-dashboard__notice--<?php echo esc_attr( $listings_notice_type ); ?>">
							<?php echo esc_html( $listings_notice_text ); ?>
						</div>
					<?php endif; ?>
					<?php
					$paged = isset( $_GET['cbld_page'] ) ? max( 1, absint( $_GET['cbld_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

					$listings_query = new WP_Query(
						array(
							'post_type'      => 'cb_listing',
							'post_status'    => array( 'pending', 'publish', 'draft' ),
							'author'         => get_current_user_id(),
							'posts_per_page' => 10,
							'paged'          => $paged,
							'orderby'        => 'date',
							'order'          => 'DESC',
						)
					);

					if ( $listings_query->have_posts() ) :
						?>
						<table class="cb-listing-user-dashboard__table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Title', 'cb-listing-anything' ); ?></th>
									<th><?php esc_html_e( 'Status', 'cb-listing-anything' ); ?></th>
									<th><?php esc_html_e( 'Date', 'cb-listing-anything' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'cb-listing-anything' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								while ( $listings_query->have_posts() ) :
									$listings_query->the_post();
									$status = get_post_status();
									?>
									<tr>
										<td>
											<?php if ( 'publish' === $status ) : ?>
												<a href="<?php the_permalink(); ?>" target="_blank" rel="noopener noreferrer">
													<?php the_title(); ?>
												</a>
											<?php else : ?>
												<?php the_title(); ?>
											<?php endif; ?>
										</td>
										<td>
											<span class="cb-listing-user-dashboard__status cb-listing-user-dashboard__status--<?php echo esc_attr( $status ); ?>">
												<?php
												if ( 'pending' === $status ) {
													esc_html_e( 'Pending review', 'cb-listing-anything' );
												} elseif ( 'publish' === $status ) {
													esc_html_e( 'Published', 'cb-listing-anything' );
												} elseif ( 'draft' === $status ) {
													esc_html_e( 'Draft', 'cb-listing-anything' );
												} else {
													echo esc_html( ucfirst( $status ) );
												}
												?>
											</span>
										</td>
										<td><?php echo esc_html( get_the_date() ); ?></td>
										<td>
											<div class="cb-listing-user-dashboard__table-actions">
												<a
													href="<?php echo esc_url( add_query_arg( array( 'tab' => 'add', 'edit_listing' => get_the_ID() ), $base_url ) ); ?>"
													class="cb-listing-user-dashboard__table-action cb-listing-user-dashboard__table-action--edit"
												>
													<?php esc_html_e( 'Edit', 'cb-listing-anything' ); ?>
												</a>
												<form
													method="post"
													class="cb-listing-user-dashboard__table-action-form"
													onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this listing?', 'cb-listing-anything' ) ); ?>');"
												>
													<?php wp_nonce_field( 'cb_listing_delete_listing', '_cb_listing_delete_nonce' ); ?>
													<input type="hidden" name="cb_listing_user_dashboard_action" value="delete_listing" />
													<input type="hidden" name="cb_listing_post_id" value="<?php echo esc_attr( get_the_ID() ); ?>" />
													<button type="submit" class="cb-listing-user-dashboard__table-action cb-listing-user-dashboard__table-action--delete">
														<?php esc_html_e( 'Delete', 'cb-listing-anything' ); ?>
													</button>
												</form>
											</div>
										</td>
									</tr>
									<?php
								endwhile;
								wp_reset_postdata();
								?>
							</tbody>
						</table>
						<?php
						$total_pages = (int) $listings_query->max_num_pages;

						if ( $total_pages > 1 ) :
							$current_page = max( 1, $paged );
							$pagination_base = remove_query_arg( 'cbld_page' );

							$links = paginate_links(
								array(
									'base'      => esc_url_raw( add_query_arg( 'cbld_page', '%#%', $pagination_base ) ),
									'format'    => '',
									'current'   => $current_page,
									'total'     => $total_pages,
									'type'      => 'array',
									'prev_text' => __( '« Previous', 'cb-listing-anything' ),
									'next_text' => __( 'Next »', 'cb-listing-anything' ),
								)
							);

							if ( ! empty( $links ) ) :
								?>
								<nav class="cb-listing-user-dashboard__pagination" aria-label="<?php esc_attr_e( 'My listings pagination', 'cb-listing-anything' ); ?>">
									<?php foreach ( $links as $link ) : ?>
										<span class="cb-listing-user-dashboard__page-link"><?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php endforeach; ?>
								</nav>
								<?php
							endif;
						endif;
					else :
						?>
						<p><?php esc_html_e( 'You have not submitted any listings yet.', 'cb-listing-anything' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>
	</div>
</div>

