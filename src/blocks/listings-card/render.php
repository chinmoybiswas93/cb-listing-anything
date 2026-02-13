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
		include CB_LISTING_ANYTHING_PLUGIN_DIR . 'src/Views/partials/listing-card.php';
	endwhile; wp_reset_postdata(); ?>
</div>
