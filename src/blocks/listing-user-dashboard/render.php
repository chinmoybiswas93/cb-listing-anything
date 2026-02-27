<?php
/**
 * Render the CB Listing User Dashboard block.
 *
 * @package CBListingAnything
 */

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

		$user = wp_signon( $creds, false );

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

if ( ! in_array( $tab, array( 'profile', 'add', 'listings' ), true ) ) {
	$tab = 'profile';
}

$base_url = remove_query_arg( 'tab' );

// Handle Add Listing form submission.
$errors           = array();
$success_message  = '';
$form_title       = '';
$form_content     = '';
$form_category_id = 0;
$form_price       = '';
$form_tag_ids     = array();

if ( 'add' === $tab && 'POST' === $_SERVER['REQUEST_METHOD'] && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_POST['cb_listing_user_dashboard_action'] ) && 'add_listing' === $_POST['cb_listing_user_dashboard_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_POST['_cb_listing_add_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_cb_listing_add_nonce'] ) ), 'cb_listing_add_listing' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$errors[] = __( 'Security check failed. Please try again.', 'cb-listing-anything' );
		} else {
			$form_title   = isset( $_POST['cb_listing_title'] ) ? sanitize_text_field( wp_unslash( $_POST['cb_listing_title'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_content = isset( $_POST['cb_listing_content'] ) ? wp_kses_post( wp_unslash( $_POST['cb_listing_content'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_price   = isset( $_POST['cb_listing_price'] ) ? sanitize_text_field( wp_unslash( $_POST['cb_listing_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_category_id = isset( $_POST['cb_listing_category'] ) ? absint( $_POST['cb_listing_category'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_tag_ids = isset( $_POST['cb_listing_tags'] ) && is_array( $_POST['cb_listing_tags'] ) ? array_map( 'absint', wp_unslash( $_POST['cb_listing_tags'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( '' === $form_title ) {
				$errors[] = __( 'Please enter a title for your listing.', 'cb-listing-anything' );
			}

			if ( empty( $errors ) ) {
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
				} else {
					if ( $form_category_id ) {
						wp_set_object_terms( $post_id, array( $form_category_id ), 'cb_listing_category', false );
					}

					if ( ! empty( $form_tag_ids ) ) {
						wp_set_object_terms( $post_id, $form_tag_ids, 'cb_listing_tag', false );
					}

					if ( '' !== $form_price ) {
						update_post_meta( $post_id, '_listing_price', $form_price );
					}

					$success_message  = __( 'Listing submitted for review.', 'cb-listing-anything' );
					$form_title       = '';
					$form_content     = '';
					$form_category_id = 0;
					$form_price       = '';
					$form_tag_ids     = array();
				}
			}
		}
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
					<h3><?php esc_html_e( 'Add new listing', 'cb-listing-anything' ); ?></h3>

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

					<form class="cb-listing-user-dashboard__form" method="post">
						<?php wp_nonce_field( 'cb_listing_add_listing', '_cb_listing_add_nonce' ); ?>
						<input type="hidden" name="cb_listing_user_dashboard_action" value="add_listing" />

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
							<textarea
								id="cb-listing-content"
								name="cb_listing_content"
								rows="6"
							><?php echo esc_textarea( $form_content ); ?></textarea>
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
								</div>
							</div>
						<?php endif; ?>

						<div class="cb-listing-user-dashboard__field cb-listing-user-dashboard__field--inline">
							<label for="cb-listing-price">
								<?php esc_html_e( 'Price', 'cb-listing-anything' ); ?>
							</label>
							<input
								type="text"
								id="cb-listing-price"
								name="cb_listing_price"
								value="<?php echo esc_attr( $form_price ); ?>"
								placeholder="<?php esc_attr_e( 'e.g. 199500', 'cb-listing-anything' ); ?>"
							/>
						</div>

						<div class="cb-listing-user-dashboard__actions">
							<button type="submit" class="cb-listing-user-dashboard__submit">
								<?php esc_html_e( 'Submit listing for review', 'cb-listing-anything' ); ?>
							</button>
						</div>
					</form>
				</div>
			<?php elseif ( 'listings' === $tab ) : ?>
				<div class="cb-listing-user-dashboard__section cb-listing-user-dashboard__section--listings">
					<h3><?php esc_html_e( 'My listings', 'cb-listing-anything' ); ?></h3>
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
									<th><?php esc_html_e( 'Author', 'cb-listing-anything' ); ?></th>
									<th><?php esc_html_e( 'Status', 'cb-listing-anything' ); ?></th>
									<th><?php esc_html_e( 'Date', 'cb-listing-anything' ); ?></th>
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
										<td><?php echo esc_html( get_the_author() ); ?></td>
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

