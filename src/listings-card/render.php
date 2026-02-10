<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$per_page      = isset( $attributes['postsPerPage'] ) ? absint( $attributes['postsPerPage'] ) : 6;
$columns       = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3;
$category      = isset( $attributes['category'] ) ? absint( $attributes['category'] ) : 0;
$show_excerpt  = isset( $attributes['showExcerpt'] ) ? $attributes['showExcerpt'] : true;
$show_price    = isset( $attributes['showPrice'] ) ? $attributes['showPrice'] : true;
$show_location = isset( $attributes['showLocation'] ) ? $attributes['showLocation'] : true;

$args = array(
	'post_type'      => 'listing',
	'posts_per_page' => $per_page,
	'post_status'    => 'publish',
);

if ( $category > 0 ) {
	$args['tax_query'] = array(
		array(
			'taxonomy' => 'listing_category',
			'field'    => 'term_id',
			'terms'    => $category,
		),
	);
}

$query = new WP_Query( $args );

if ( ! $query->have_posts() ) {
	echo '<p>' . esc_html__( 'No listings found.', 'listing-items' ) . '</p>';
	return;
}

$wrapper = get_block_wrapper_attributes( array(
	'class' => 'listing-items-cards listing-items-cols-' . esc_attr( $columns ),
) );
?>
<div <?php echo $wrapper; ?>>
	<?php while ( $query->have_posts() ) : $query->the_post();
		$price    = get_post_meta( get_the_ID(), '_listing_price', true );
		$location = get_post_meta( get_the_ID(), '_listing_location', true );
	?>
	<article class="listing-item-card">
		<?php if ( has_post_thumbnail() ) : ?>
		<div class="listing-item-card__image">
			<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium_large' ); ?></a>
		</div>
		<?php endif; ?>
		<div class="listing-item-card__content">
			<h3 class="listing-item-card__title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h3>
			<?php if ( $show_price && $price ) : ?>
			<div class="listing-item-card__price"><?php echo esc_html( $price ); ?></div>
			<?php endif; ?>
			<?php if ( $show_location && $location ) : ?>
			<div class="listing-item-card__location">
				<span class="dashicons dashicons-location"></span>
				<?php echo esc_html( $location ); ?>
			</div>
			<?php endif; ?>
			<?php if ( $show_excerpt ) : ?>
			<div class="listing-item-card__excerpt"><?php the_excerpt(); ?></div>
			<?php endif; ?>
			<div class="listing-item-card__footer">
				<a href="<?php the_permalink(); ?>" class="listing-item-card__link">
					<?php esc_html_e( 'View Details', 'listing-items' ); ?>
				</a>
			</div>
		</div>
	</article>
	<?php endwhile; wp_reset_postdata(); ?>
</div>
