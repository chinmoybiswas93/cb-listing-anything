<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_the_ID();

if ( ! $post_id || 'cb_listing' !== get_post_type( $post_id ) ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$preview = get_posts( array(
			'post_type'      => 'cb_listing',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		) );

		if ( empty( $preview ) ) {
			echo '<p>' . esc_html__( 'No related listings found.', 'cb-listing-anything' ) . '</p>';
			return;
		}

		$post_id = $preview[0]->ID;
	} else {
		return;
	}
}

$per_page         = isset( $attributes['postsPerPage'] ) ? absint( $attributes['postsPerPage'] ) : 3;
$columns          = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3;
$show_categories  = isset( $attributes['showCategories'] ) ? $attributes['showCategories'] : true;
$show_open_status = isset( $attributes['showOpenStatus'] ) ? $attributes['showOpenStatus'] : true;
$show_price       = isset( $attributes['showPrice'] ) ? $attributes['showPrice'] : true;
$show_tags        = isset( $attributes['showTags'] ) ? $attributes['showTags'] : true;
$show_address     = isset( $attributes['showAddress'] ) ? $attributes['showAddress'] : true;
$show_call_button = isset( $attributes['showCallButton'] ) ? $attributes['showCallButton'] : true;

$query        = null;
$category_ids = array();
$tag_ids      = array();

$post_categories = get_the_terms( $post_id, 'cb_listing_category' );
if ( $post_categories && ! is_wp_error( $post_categories ) ) {
	$category_ids = wp_list_pluck( $post_categories, 'term_id' );
}

if ( ! empty( $category_ids ) ) {
	$query = new WP_Query( array(
		'post_type'      => 'cb_listing',
		'posts_per_page' => $per_page,
		'post_status'    => 'publish',
		'post__not_in'   => array( $post_id ),
		'tax_query'      => array( array(
			'taxonomy' => 'cb_listing_category',
			'field'    => 'term_id',
			'terms'    => $category_ids,
		) ),
	) );
}

if ( ! $query || ! $query->have_posts() ) {
	$post_tags = get_the_terms( $post_id, 'cb_listing_tag' );
	if ( $post_tags && ! is_wp_error( $post_tags ) ) {
		$tag_ids = wp_list_pluck( $post_tags, 'term_id' );
	}

	if ( ! empty( $tag_ids ) ) {
		$query = new WP_Query( array(
			'post_type'      => 'cb_listing',
			'posts_per_page' => $per_page,
			'post_status'    => 'publish',
			'post__not_in'   => array( $post_id ),
			'tax_query'      => array( array(
				'taxonomy' => 'cb_listing_tag',
				'field'    => 'term_id',
				'terms'    => $tag_ids,
			) ),
		) );
	}
}

if ( ! $query || ! $query->have_posts() ) {
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
