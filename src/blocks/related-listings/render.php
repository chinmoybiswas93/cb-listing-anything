<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_the_ID();

if ( ! $post_id || \CBListingAnything\Config\PostType::POST_TYPE !== get_post_type( $post_id ) ) {
	$post_id = \CBListingAnything\Helpers\ListingHelper::get_preview_post_id();
	if ( ! $post_id ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			echo '<p>' . esc_html__( 'No related listings found.', 'cb-listing-anything' ) . '</p>';
		}
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

$post_categories = get_the_terms( $post_id, \CBListingAnything\Config\Taxonomies::CATEGORY_TAXONOMY );
if ( $post_categories && ! is_wp_error( $post_categories ) ) {
	$category_ids = wp_list_pluck( $post_categories, 'term_id' );
}

if ( ! empty( $category_ids ) ) {
	$query = \CrocoDevs\Database\QueryBuilder::make()
		->postType( \CBListingAnything\Config\PostType::POST_TYPE )
		->perPage( $per_page )
		->status( 'publish' )
		->exclude( $post_id )
		->whenTax( \CBListingAnything\Config\Taxonomies::CATEGORY_TAXONOMY, 'term_id', $category_ids )
		->get();
}

if ( ! $query || ! $query->have_posts() ) {
	$post_tags = get_the_terms( $post_id, \CBListingAnything\Config\Taxonomies::TAG_TAXONOMY );
	if ( $post_tags && ! is_wp_error( $post_tags ) ) {
		$tag_ids = wp_list_pluck( $post_tags, 'term_id' );
	}

	if ( ! empty( $tag_ids ) ) {
		$query = \CrocoDevs\Database\QueryBuilder::make()
			->postType( \CBListingAnything\Config\PostType::POST_TYPE )
			->perPage( $per_page )
			->status( 'publish' )
			->exclude( $post_id )
			->whenTax( \CBListingAnything\Config\Taxonomies::TAG_TAXONOMY, 'term_id', $tag_ids )
			->get();
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
		include crocodevs_view_path( 'partials/listing-card' );
	endwhile; wp_reset_postdata(); ?>
</div>
