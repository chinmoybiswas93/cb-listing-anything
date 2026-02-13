<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$use_current_query = isset( $attributes['useCurrentQuery'] ) ? $attributes['useCurrentQuery'] : false;
$per_page          = isset( $attributes['postsPerPage'] ) ? absint( $attributes['postsPerPage'] ) : 6;
$columns           = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3;
$category_filter   = isset( $attributes['category'] ) ? absint( $attributes['category'] ) : 0;
$show_categories   = isset( $attributes['showCategories'] ) ? $attributes['showCategories'] : true;
$show_open_status  = isset( $attributes['showOpenStatus'] ) ? $attributes['showOpenStatus'] : true;
$show_price        = isset( $attributes['showPrice'] ) ? $attributes['showPrice'] : true;
$show_tags         = isset( $attributes['showTags'] ) ? $attributes['showTags'] : true;
$show_address      = isset( $attributes['showAddress'] ) ? $attributes['showAddress'] : true;
$show_call_button  = isset( $attributes['showCallButton'] ) ? $attributes['showCallButton'] : true;

if ( $use_current_query && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
	global $wp_query;
	$args  = array_merge( $wp_query->query_vars, array(
		'post_type'      => 'listing',
		'posts_per_page' => $per_page,
		'post_status'    => 'publish',
	) );
	$query = new WP_Query( $args );
} else {
	$args = array(
		'post_type'      => 'listing',
		'posts_per_page' => $per_page,
		'post_status'    => 'publish',
	);
	if ( ! $use_current_query && $category_filter > 0 ) {
		$args['tax_query'] = array( array(
			'taxonomy' => 'listing_category',
			'field'    => 'term_id',
			'terms'    => $category_filter,
		) );
	}
	$query = new WP_Query( $args );
}

if ( ! $query->have_posts() ) {
	echo '<p>' . esc_html__( 'No listings found.', 'cb-listing-anything' ) . '</p>';
	return;
}

$wrapper = get_block_wrapper_attributes( array(
	'class' => 'cb-listing-cards cb-listing-cols-' . esc_attr( $columns ),
) );
?>
<div <?php echo $wrapper; ?>>
	<?php while ( $query->have_posts() ) : $query->the_post();
		$post_id      = get_the_ID();
		$price        = get_post_meta( $post_id, '_listing_price', true );
		$location     = get_post_meta( $post_id, '_listing_location', true );
		$phone        = get_post_meta( $post_id, '_listing_contact_phone', true );
		$opening_time = get_post_meta( $post_id, '_listing_opening_time', true );
		$closing_time = get_post_meta( $post_id, '_listing_closing_time', true );
		$working_days = get_post_meta( $post_id, '_listing_working_days', true );
		$categories   = get_the_terms( $post_id, 'listing_category' );
		$tags         = get_the_terms( $post_id, 'listing_tag' );

		$is_open = false;
		if ( $opening_time && $closing_time && is_array( $working_days ) ) {
			$current_day  = strtolower( wp_date( 'l' ) );
			$current_time = wp_date( 'H:i' );
			if ( in_array( $current_day, $working_days, true ) && $current_time >= $opening_time && $current_time <= $closing_time ) {
				$is_open = true;
			}
		}
	?>
	<article class="cb-listing-card">
		<div class="cb-listing-card__image">
			<a href="<?php the_permalink(); ?>">
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'medium_large' ); ?>
				<?php else : ?>
					<span class="cb-listing-card__placeholder"></span>
				<?php endif; ?>
			</a>
		</div>

		<div class="cb-listing-card__body">
			<?php if ( $show_categories || $show_open_status || ( $show_price && $price ) ) : ?>
			<div class="cb-listing-card__meta-top">
				<?php if ( $show_categories && $categories && ! is_wp_error( $categories ) ) : ?>
				<div class="cb-listing-card__categories">
					<?php foreach ( $categories as $cat ) : ?>
					<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="cb-listing-card__category"><?php echo esc_html( $cat->name ); ?></a>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<div class="cb-listing-card__meta-right">
					<?php if ( $show_open_status && $opening_time && $closing_time ) : ?>
					<span class="cb-listing-card__status cb-listing-card__status--<?php echo $is_open ? 'open' : 'closed'; ?>">
						<?php $is_open ? esc_html_e( 'Open Now', 'cb-listing-anything' ) : esc_html_e( 'Closed', 'cb-listing-anything' ); ?>
					</span>
					<?php endif; ?>

					<?php if ( $show_price && $price ) : ?>
					<span class="cb-listing-card__price"><?php echo esc_html( \CBListingAnything\Controllers\SettingsController::currency_symbol() . $price ); ?></span>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>

			<h3 class="cb-listing-card__title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h3>

			<?php if ( $show_tags && $tags && ! is_wp_error( $tags ) ) : ?>
			<div class="cb-listing-card__tags">
				<?php foreach ( $tags as $tag ) : ?>
				<a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" class="cb-listing-card__tag"><?php echo esc_html( $tag->name ); ?></a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php if ( $show_address && $location ) : ?>
			<div class="cb-listing-card__address">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
				<?php echo esc_html( $location ); ?>
			</div>
			<?php endif; ?>

			<div class="cb-listing-card__actions">
				<?php if ( $show_call_button && $phone ) : ?>
				<a href="tel:<?php echo esc_attr( $phone ); ?>" class="cb-listing-card__btn cb-listing-card__btn--call">
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
					<?php esc_html_e( 'Call', 'cb-listing-anything' ); ?>
				</a>
				<?php endif; ?>
				<a href="<?php the_permalink(); ?>" class="cb-listing-card__btn cb-listing-card__btn--details">
					<?php esc_html_e( 'View Details', 'cb-listing-anything' ); ?>
				</a>
			</div>
		</div>
	</article>
	<?php endwhile; wp_reset_postdata(); ?>
</div>
