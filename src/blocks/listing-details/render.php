<?php
if (! defined('ABSPATH')) {
	exit;
}

$post_id = isset($block->context['postId']) ? absint($block->context['postId']) : get_the_ID();

if (! $post_id || 'listing' !== get_post_type($post_id)) {
	if (defined('REST_REQUEST') && REST_REQUEST) {
		$preview = get_posts(array(
			'post_type'      => 'listing',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		));

		if (empty($preview)) {
			echo '<p>' . esc_html__('No listings found for preview.', 'cb-listing-anything') . '</p>';
			return;
		}

		$post_id = $preview[0]->ID;
	} else {
		return;
	}
}

$post_obj = get_post($post_id);

$show_gallery     = ! empty($attributes['showGallery']);
$show_categories  = ! empty($attributes['showCategories']);
$show_tags        = ! empty($attributes['showTags']);
$show_content     = ! empty($attributes['showContent']);
$show_call_button = ! empty($attributes['showCallButton']);
$show_address     = ! empty($attributes['showAddress']);
$show_contact     = ! empty($attributes['showContact']);
$show_website     = ! empty($attributes['showWebsite']);
$show_socials     = ! empty($attributes['showSocials']);
$show_hours       = ! empty($attributes['showHours']);
$show_price       = ! empty($attributes['showPrice']);

$price        = get_post_meta($post_id, '_listing_price', true);
$address      = get_post_meta($post_id, '_listing_address', true);
$city         = get_post_meta($post_id, '_listing_city', true);
$state        = get_post_meta($post_id, '_listing_state', true);
$zip          = get_post_meta($post_id, '_listing_zip_code', true);
$country      = get_post_meta($post_id, '_listing_country', true);
$email        = get_post_meta($post_id, '_listing_contact_email', true);
$phone        = get_post_meta($post_id, '_listing_contact_phone', true);
$website      = get_post_meta($post_id, '_listing_website', true);
$facebook     = get_post_meta($post_id, '_listing_social_facebook', true);
$twitter      = get_post_meta($post_id, '_listing_social_twitter', true);
$instagram    = get_post_meta($post_id, '_listing_social_instagram', true);
$linkedin     = get_post_meta($post_id, '_listing_social_linkedin', true);
$youtube      = get_post_meta($post_id, '_listing_social_youtube', true);
$opening_time = get_post_meta($post_id, '_listing_opening_time', true);
$closing_time = get_post_meta($post_id, '_listing_closing_time', true);
$working_days = get_post_meta($post_id, '_listing_working_days', true);
$gallery_raw  = get_post_meta($post_id, '_listing_gallery', true);

$gallery_ids = array();
if ($gallery_raw) {
	$gallery_ids = array_filter(array_map('absint', explode(',', $gallery_raw)));
}

$featured_id = get_post_thumbnail_id($post_id);
$all_images  = array();
if ($featured_id) {
	$all_images[] = $featured_id;
}
foreach ($gallery_ids as $gid) {
	if ($gid && $gid !== $featured_id) {
		$all_images[] = $gid;
	}
}

$full_address_parts = array_filter(array($address, $city, $state, $zip, $country));
$full_address       = implode(', ', $full_address_parts);
$maps_url           = $full_address ? 'https://www.google.com/maps/search/' . rawurlencode($full_address) : '';

$is_open = false;
if ($opening_time && $closing_time && is_array($working_days)) {
	$current_day  = strtolower(wp_date('l'));
	$current_time = wp_date('H:i');
	if (in_array($current_day, $working_days, true) && $current_time >= $opening_time && $current_time <= $closing_time) {
		$is_open = true;
	}
}

$categories = get_the_terms($post_id, 'listing_category');
$tags       = get_the_terms($post_id, 'listing_tag');

$has_socials = $facebook || $twitter || $instagram || $linkedin || $youtube;

$currency_symbol = \CBListingAnything\Controllers\SettingsController::currency_symbol();

$wrapper = get_block_wrapper_attributes(array(
	'class' => 'cb-listing-single',
));
?>
<div <?php echo $wrapper; ?>>


	<div class="cb-listing-single__layout">

		<div class="cb-listing-single__left">
			<nav class="cb-listing-single__breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'cb-listing-anything'); ?>">
				<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'cb-listing-anything'); ?></a>
				<span class="cb-listing-single__breadcrumb-sep">/</span>
				<a href="<?php echo esc_url(get_post_type_archive_link('listing')); ?>"><?php esc_html_e('Listings', 'cb-listing-anything'); ?></a>
				<?php if ($categories && ! is_wp_error($categories)) : ?>
					<span class="cb-listing-single__breadcrumb-sep">/</span>
					<a href="<?php echo esc_url(get_term_link($categories[0])); ?>"><?php echo esc_html($categories[0]->name); ?></a>
				<?php endif; ?>
				<span class="cb-listing-single__breadcrumb-sep">/</span>
				<span class="cb-listing-single__breadcrumb-current"><?php echo esc_html(get_the_title($post_id)); ?></span>
			</nav>

			<?php if ($show_gallery && ! empty($all_images)) : ?>
				<div class="cb-listing-single__gallery">
					<div class="cb-listing-single__gallery-track">
						<?php foreach ($all_images as $img_id) :
							$img_url = wp_get_attachment_image_url($img_id, 'medium_large');
							$img_alt = get_post_meta($img_id, '_wp_attachment_image_alt', true);
							if (! $img_url) {
								continue;
							}
						?>
							<div class="cb-listing-single__gallery-slide">
								<img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>" loading="lazy" />
							</div>
						<?php endforeach; ?>
					</div>
					<?php if (count($all_images) > 3) : ?>
						<button type="button" class="cb-listing-single__gallery-arrow cb-listing-single__gallery-arrow--prev" aria-label="<?php esc_attr_e('Previous', 'cb-listing-anything'); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
								<polyline points="15 18 9 12 15 6" />
							</svg>
						</button>
						<button type="button" class="cb-listing-single__gallery-arrow cb-listing-single__gallery-arrow--next" aria-label="<?php esc_attr_e('Next', 'cb-listing-anything'); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
								<polyline points="9 18 15 12 9 6" />
							</svg>
						</button>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<h1 class="cb-listing-single__title"><?php echo esc_html(get_the_title($post_id)); ?></h1>

			<?php if (($show_categories && $categories && ! is_wp_error($categories)) || ($show_tags && $tags && ! is_wp_error($tags))) : ?>
				<div class="cb-listing-single__taxonomies">
					<?php if ($show_categories && $categories && ! is_wp_error($categories)) : ?>
						<div class="cb-listing-single__cats">
							<?php foreach ($categories as $cat) : ?>
								<a href="<?php echo esc_url(get_term_link($cat)); ?>" class="cb-listing-single__cat"><?php echo esc_html($cat->name); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ($show_tags && $tags && ! is_wp_error($tags)) : ?>
						<div class="cb-listing-single__tags">
							<?php foreach ($tags as $tag) : ?>
								<a href="<?php echo esc_url(get_term_link($tag)); ?>" class="cb-listing-single__tag"><?php echo esc_html($tag->name); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ($show_content && $post_obj->post_content) : ?>
				<div class="cb-listing-single__content">
					<?php echo apply_filters('the_content', $post_obj->post_content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
					?>
				</div>
			<?php endif; ?>

		</div>

		<aside class="cb-listing-single__right">

			<?php if ($show_call_button && $phone) : ?>
				<a href="tel:<?php echo esc_attr($phone); ?>" class="cb-listing-single__cta">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
					</svg>
					<?php esc_html_e('Call Now', 'cb-listing-anything'); ?>
				</a>
			<?php endif; ?>

			<div class="cb-listing-single__sidebar-card">

				<?php if ($show_address && $full_address) : ?>
					<div class="cb-listing-single__sidebar-item">
						<div class="cb-listing-single__sidebar-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
								<circle cx="12" cy="10" r="3" />
							</svg>
						</div>
						<div class="cb-listing-single__sidebar-info">
							<span class="cb-listing-single__sidebar-value"><?php echo esc_html($full_address); ?></span>
							<?php if ($maps_url) : ?>
								<a href="<?php echo esc_url($maps_url); ?>" target="_blank" rel="noopener noreferrer" class="cb-listing-single__directions"><?php esc_html_e('Get Directions', 'cb-listing-anything'); ?></a>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ($show_contact && $phone) : ?>
					<div class="cb-listing-single__sidebar-item">
						<div class="cb-listing-single__sidebar-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
							</svg>
						</div>
						<div class="cb-listing-single__sidebar-info">
							<a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
						</div>
					</div>
				<?php endif; ?>

				<?php if ($show_contact && $email) : ?>
					<div class="cb-listing-single__sidebar-item">
						<div class="cb-listing-single__sidebar-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
								<polyline points="22,6 12,13 2,6" />
							</svg>
						</div>
						<div class="cb-listing-single__sidebar-info">
							<a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
						</div>
					</div>
				<?php endif; ?>

				<?php if ($show_website && $website) : ?>
					<div class="cb-listing-single__sidebar-item">
						<div class="cb-listing-single__sidebar-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<circle cx="12" cy="12" r="10" />
								<line x1="2" y1="12" x2="22" y2="12" />
								<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
							</svg>
						</div>
						<div class="cb-listing-single__sidebar-info">
							<a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(wp_parse_url($website, PHP_URL_HOST)); ?></a>
						</div>
					</div>
				<?php endif; ?>

				<?php if ($show_socials && $has_socials) : ?>
					<div class="cb-listing-single__sidebar-item cb-listing-single__sidebar-item--socials">
						<div class="cb-listing-single__socials">
							<?php if ($facebook) : ?>
								<a href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="cb-listing-single__social">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
										<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
									</svg>
								</a>
							<?php endif; ?>
							<?php if ($twitter) : ?>
								<a href="<?php echo esc_url($twitter); ?>" target="_blank" rel="noopener noreferrer" aria-label="Twitter" class="cb-listing-single__social">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
										<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
									</svg>
								</a>
							<?php endif; ?>
							<?php if ($youtube) : ?>
								<a href="<?php echo esc_url($youtube); ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="cb-listing-single__social">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
										<path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12z" />
									</svg>
								</a>
							<?php endif; ?>
							<?php if ($instagram) : ?>
								<a href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="cb-listing-single__social">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
										<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z" />
									</svg>
								</a>
							<?php endif; ?>
							<?php if ($linkedin) : ?>
								<a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" class="cb-listing-single__social">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
										<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0z" />
									</svg>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ($show_hours && $opening_time && $closing_time) :
					$all_days    = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
					$today_key   = strtolower(wp_date('l'));
					$time_format = get_option('time_format');
					$time_range  = date_i18n($time_format, strtotime($opening_time)) . ' - ' . date_i18n($time_format, strtotime($closing_time));
				?>
					<div class="cb-listing-single__hours" data-hours-toggle>
						<button type="button" class="cb-listing-single__hours-header" aria-expanded="false">
							<div class="cb-listing-single__sidebar-icon">
								<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<circle cx="12" cy="12" r="10" />
									<polyline points="12 6 12 12 16 14" />
								</svg>
							</div>
							<span class="cb-listing-single__hours-today"><?php esc_html_e('Today', 'cb-listing-anything'); ?></span>
							<span class="cb-listing-single__hours-status cb-listing-single__hours-status--<?php echo $is_open ? 'open' : 'closed'; ?>">
								<?php $is_open ? esc_html_e('Open Now', 'cb-listing-anything') : esc_html_e('Closed', 'cb-listing-anything'); ?>
							</span>
							<span class="cb-listing-single__hours-expand">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<polyline points="6 9 12 15 18 9" />
								</svg>
							</span>
						</button>
						<div class="cb-listing-single__hours-list" hidden>
							<?php foreach ($all_days as $day) :
								$is_working = is_array($working_days) && in_array($day, $working_days, true);
								$is_today   = ($day === $today_key);
							?>
								<div class="cb-listing-single__hours-row<?php echo $is_today ? ' cb-listing-single__hours-row--today' : ''; ?>">
									<span class="cb-listing-single__hours-day"><?php echo esc_html(ucfirst($day)); ?></span>
									<span class="cb-listing-single__hours-time"><?php echo $is_working ? esc_html($time_range) : esc_html__('Closed', 'cb-listing-anything'); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ($show_price && $price) : ?>
					<div class="cb-listing-single__sidebar-item cb-listing-single__sidebar-item--price">
						<div class="cb-listing-single__sidebar-info">
							<span class="cb-listing-single__price-line">
								<span class="cb-listing-single__sidebar-label"><?php esc_html_e('Price', 'cb-listing-anything'); ?></span>
								<span class="cb-listing-single__price-value"><?php echo esc_html($currency_symbol . $price); ?></span>
							</span>
						</div>
					</div>
				<?php endif; ?>

			</div>

		</aside>

	</div>
</div>