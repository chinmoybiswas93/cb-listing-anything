<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items_to_show     = isset( $attributes['itemsToShow'] ) ? absint( $attributes['itemsToShow'] ) : 6;
$height            = isset( $attributes['height'] ) ? absint( $attributes['height'] ) : 280;
$show_name         = isset( $attributes['showName'] ) ? $attributes['showName'] : true;
$show_count        = isset( $attributes['showCount'] ) ? $attributes['showCount'] : true;
$button_position        = isset( $attributes['buttonPosition'] ) && $attributes['buttonPosition'] === 'inside' ? 'inside' : 'outside';
$button_outside_offset  = isset( $attributes['buttonOutsideOffset'] ) ? (int) $attributes['buttonOutsideOffset'] : 0;
$selected_category_ids  = isset( $attributes['selectedCategoryIds'] ) && is_array( $attributes['selectedCategoryIds'] ) ? array_map( 'absint', $attributes['selectedCategoryIds'] ) : array();
$selected_category_ids  = array_filter( $selected_category_ids );

$get_terms_args = array(
	'taxonomy'   => 'cb_listing_category',
	'hide_empty' => true,
	'orderby'    => 'name',
	'order'      => 'ASC',
	'number'     => 20,
);
if ( ! empty( $selected_category_ids ) ) {
	$get_terms_args['include'] = $selected_category_ids;
	$get_terms_args['number']  = 100;
}
$terms = get_terms( $get_terms_args );
if ( ! empty( $selected_category_ids ) && ! is_wp_error( $terms ) && ! empty( $terms ) ) {
	$by_id = array();
	foreach ( $terms as $t ) {
		$by_id[ $t->term_id ] = $t;
	}
	$terms = array_filter( array_map( function( $id ) use ( $by_id ) {
		return isset( $by_id[ $id ] ) ? $by_id[ $id ] : null;
	}, $selected_category_ids ) );
}

if ( is_wp_error( $terms ) || empty( $terms ) ) {
	return;
}

$n          = min( $items_to_show, count( $terms ) );
$gap        = 16;
$total_gap  = ( $n - 1 ) * $gap;
$item_width = 'calc((100% - ' . $total_gap . 'px) / ' . $n . ')';
$wrapper_styles = '--cb-cat-slider-height: ' . $height . 'px; --cb-cat-slider-item-width: ' . $item_width . ';';
if ( $button_position === 'outside' ) {
	$wrapper_styles .= ' --cb-cat-slider-btn-offset: ' . $button_outside_offset . 'px;';
}
$wrapper = get_block_wrapper_attributes( array(
	'class' => 'cb-categories-slider cb-categories-slider--buttons-' . $button_position,
	'style' => $wrapper_styles,
) );
?>
<div <?php echo $wrapper; ?>>
	<button type="button" class="cb-categories-slider__arrow cb-categories-slider__arrow--prev" aria-label="<?php esc_attr_e( 'Previous', 'cb-listing-anything' ); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
	</button>
	<div class="cb-categories-slider__track-wrap">
		<div class="cb-categories-slider__track">
			<?php
			foreach ( $terms as $term ) {
				$term_link = get_term_link( $term );
				if ( is_wp_error( $term_link ) ) {
					continue;
				}
				$img_url = '';
				$q       = new WP_Query( array(
					'post_type'      => 'cb_listing',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'tax_query'      => array( array( 'taxonomy' => 'cb_listing_category', 'field' => 'term_id', 'terms' => $term->term_id ) ),
					'fields'         => 'ids',
				) );
				if ( $q->have_posts() ) {
					$pid = $q->posts[0];
					$img_url = get_the_post_thumbnail_url( $pid, 'medium_large' );
					wp_reset_postdata();
				}
				?>
				<a href="<?php echo esc_url( $term_link ); ?>" class="cb-categories-slider__item">
					<span class="cb-categories-slider__image"<?php echo $img_url ? ' style="background-image:url(' . esc_url( $img_url ) . ');"' : ''; ?>>
						<?php if ( ! $img_url ) : ?>
							<span class="cb-categories-slider__placeholder"></span>
						<?php endif; ?>
					</span>
					<span class="cb-categories-slider__overlay">
						<?php if ( $show_name ) : ?>
							<span class="cb-categories-slider__name"><?php echo esc_html( $term->name ); ?></span>
						<?php endif; ?>
						<?php if ( $show_count ) : ?>
							<span class="cb-categories-slider__count"><?php echo esc_html( sprintf( _n( '%s listing', '%s listings', $term->count, 'cb-listing-anything' ), number_format_i18n( $term->count ) ) ); ?></span>
						<?php endif; ?>
					</span>
				</a>
			<?php } ?>
		</div>
	</div>
	<button type="button" class="cb-categories-slider__arrow cb-categories-slider__arrow--next" aria-label="<?php esc_attr_e( 'Next', 'cb-listing-anything' ); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
	</button>
</div>
