<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Slider attributes.
$items_to_show_legacy   = isset( $attributes['itemsToShow'] ) ? absint( $attributes['itemsToShow'] ) : 4;
$items_to_show_desktop  = isset( $attributes['itemsToShowDesktop'] ) ? absint( $attributes['itemsToShowDesktop'] ) : $items_to_show_legacy;
$items_to_show_tablet   = isset( $attributes['itemsToShowTablet'] ) ? absint( $attributes['itemsToShowTablet'] ) : 2;
$items_to_show_mobile   = isset( $attributes['itemsToShowMobile'] ) ? absint( $attributes['itemsToShowMobile'] ) : 1;
$button_position      = isset( $attributes['buttonPosition'] ) && 'inside' === $attributes['buttonPosition'] ? 'inside' : 'outside';
$button_outside_offset = isset( $attributes['buttonOutsideOffset'] ) ? (int) $attributes['buttonOutsideOffset'] : 0;

// Listing query attributes.
$use_current_query = isset( $attributes['useCurrentQuery'] ) ? (bool) $attributes['useCurrentQuery'] : false;
$per_page          = isset( $attributes['postsPerPage'] ) ? absint( $attributes['postsPerPage'] ) : 8;
$category_filter   = isset( $attributes['category'] ) ? absint( $attributes['category'] ) : 0;

// Card display toggles (passed through to the listing-card partial via global state).
$show_categories  = isset( $attributes['showCategories'] ) ? (bool) $attributes['showCategories'] : true;
$show_open_status = isset( $attributes['showOpenStatus'] ) ? (bool) $attributes['showOpenStatus'] : true;
$show_price       = isset( $attributes['showPrice'] ) ? (bool) $attributes['showPrice'] : true;
$show_tags        = isset( $attributes['showTags'] ) ? (bool) $attributes['showTags'] : true;
$show_address     = isset( $attributes['showAddress'] ) ? (bool) $attributes['showAddress'] : true;
$show_call_button = isset( $attributes['showCallButton'] ) ? (bool) $attributes['showCallButton'] : true;

// Build the query similar to listings-card block.
if ( $use_current_query && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
	global $wp_query;

	$args = array_merge(
		$wp_query->query_vars,
		array(
			'post_type'      => 'cb_listing',
			'posts_per_page' => $per_page,
			'post_status'    => 'publish',
		)
	);
	$query = new WP_Query( $args );
} else {
	$args = array(
		'post_type'      => 'cb_listing',
		'posts_per_page' => $per_page,
		'post_status'    => 'publish',
	);

	if ( ! $use_current_query && $category_filter > 0 ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'cb_listing_category',
				'field'    => 'term_id',
				'terms'    => $category_filter,
			),
		);
	}

	$query = new WP_Query( $args );
}

if ( ! $query->have_posts() ) {
	echo '<p>' . esc_html__( 'No listings found.', 'cb-listing-anything' ) . '</p>';
	return;
}

// Compute slider layout CSS variables (height handled by cards).
$gap = 16;

// Compute width expressions for each breakpoint.
$items_desktop = max( 1, (int) $items_to_show_desktop );
$items_tablet  = max( 1, (int) $items_to_show_tablet );
$items_mobile  = max( 1, (int) $items_to_show_mobile );

$total_gap_desktop = ( $items_desktop - 1 ) * $gap;
$total_gap_tablet  = ( $items_tablet - 1 ) * $gap;
$total_gap_mobile  = ( $items_mobile - 1 ) * $gap;

$item_width_desktop = 'calc((100% - ' . $total_gap_desktop . 'px) / ' . $items_desktop . ')';
$item_width_tablet  = 'calc((100% - ' . $total_gap_tablet . 'px) / ' . $items_tablet . ')';
$item_width_mobile  = 'calc((100% - ' . $total_gap_mobile . 'px) / ' . $items_mobile . ')';

// Base var kept for compatibility with shared slider styles; desktop is default.
$wrapper_styles  = '--cb-cat-slider-item-width: ' . $item_width_desktop . ';';
$wrapper_styles .= ' --cb-listing-slider-item-width-desktop: ' . $item_width_desktop . ';';
$wrapper_styles .= ' --cb-listing-slider-item-width-tablet: ' . $item_width_tablet . ';';
$wrapper_styles .= ' --cb-listing-slider-item-width-mobile: ' . $item_width_mobile . ';';
if ( 'outside' === $button_position ) {
	$wrapper_styles .= ' --cb-cat-slider-btn-offset: ' . $button_outside_offset . 'px;';
}

// Arrow style variables.
$arrow_bg   = isset( $attributes['arrowBackgroundColor'] ) ? sanitize_hex_color( $attributes['arrowBackgroundColor'] ) : '';
$arrow_icon = isset( $attributes['arrowIconColor'] ) ? sanitize_hex_color( $attributes['arrowIconColor'] ) : '';
$radius     = isset( $attributes['arrowBorderRadius'] ) ? (int) $attributes['arrowBorderRadius'] : 50;
$padding    = isset( $attributes['arrowPadding'] ) ? (int) $attributes['arrowPadding'] : 0;

if ( $arrow_bg ) {
	$wrapper_styles .= ' --cb-cat-slider-arrow-bg: ' . $arrow_bg . ';';
}
if ( $arrow_icon ) {
	$wrapper_styles .= ' --cb-cat-slider-arrow-color: ' . $arrow_icon . ';';
}

// Border radius as percentage; clamp 0–50.
$radius = max( 0, min( 50, $radius ) );
$wrapper_styles .= ' --cb-cat-slider-arrow-radius: ' . $radius . '%;';

// Padding in px.
$padding = max( 0, $padding );
$wrapper_styles .= ' --cb-cat-slider-arrow-padding: ' . $padding . 'px;';

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'cb-categories-slider cb-categories-slider--buttons-' . $button_position . ' cb-categories-slider--listings',
		'style' => $wrapper_styles,
	)
);
?>
<div <?php echo $wrapper; ?>>
	<button type="button" class="cb-categories-slider__arrow cb-categories-slider__arrow--prev" aria-label="<?php esc_attr_e( 'Previous', 'cb-listing-anything' ); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
	</button>
	<div class="cb-categories-slider__track-wrap">
		<div class="cb-categories-slider__track">
			<?php
			// Expose card display toggles to the listing-card partial via globals.
			$GLOBALS['cb_listing_anything_card_settings'] = array(
				'show_categories'  => $show_categories,
				'show_open_status' => $show_open_status,
				'show_price'       => $show_price,
				'show_tags'        => $show_tags,
				'show_address'     => $show_address,
				'show_call_button' => $show_call_button,
			);

			while ( $query->have_posts() ) :
				$query->the_post();
				?>
				<div class="cb-categories-slider__item">
					<?php include CB_LISTING_ANYTHING_PLUGIN_DIR . 'src/Views/partials/listing-card.php'; ?>
				</div>
			<?php endwhile; wp_reset_postdata(); ?>
			<?php unset( $GLOBALS['cb_listing_anything_card_settings'] ); ?>
		</div>
	</div>
	<button type="button" class="cb-categories-slider__arrow cb-categories-slider__arrow--next" aria-label="<?php esc_attr_e( 'Next', 'cb-listing-anything' ); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
	</button>
</div>

