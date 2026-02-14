<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : __( 'Keywords', 'cb-listing-anything' );
$button_text = isset( $attributes['buttonText'] ) ? $attributes['buttonText'] : __( 'Search', 'cb-listing-anything' );
$archive_url = get_post_type_archive_link( 'listing' );
$wrapper     = get_block_wrapper_attributes( array( 'class' => 'cb-listing-search' ) );

$terms = get_terms( array(
	'taxonomy'   => 'listing_category',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
) );

$categories = array();
if ( ! is_wp_error( $terms ) ) {
	$categories = $terms;
}
?>
<div <?php echo $wrapper; ?>
	data-archive-url="<?php echo esc_url( $archive_url ); ?>"
	data-rest-url="<?php echo esc_url( rest_url( 'cb-listing-anything/v1' ) ); ?>">

	<div class="cb-listing-search__form">
		<div class="cb-listing-search__field cb-listing-search__field--keyword">
			<svg class="cb-listing-search__icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			<input
				type="text"
				class="cb-listing-search__input"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				autocomplete="off"
			/>
		</div>

		<div class="cb-listing-search__field cb-listing-search__field--category">
			<svg class="cb-listing-search__icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
			<select class="cb-listing-search__select">
				<option value=""><?php esc_html_e( 'All Categories', 'cb-listing-anything' ); ?></option>
				<?php
				$parent_terms = array_filter( $categories, function( $t ) { return 0 === $t->parent; } );
				foreach ( $parent_terms as $parent ) :
				?>
				<option value="<?php echo esc_attr( $parent->term_id ); ?>"><?php echo esc_html( $parent->name ); ?></option>
				<?php
					$children = array_filter( $categories, function( $t ) use ( $parent ) { return $t->parent === $parent->term_id; } );
					foreach ( $children as $child ) :
				?>
				<option value="<?php echo esc_attr( $child->term_id ); ?>">&mdash; <?php echo esc_html( $child->name ); ?></option>
				<?php endforeach; endforeach; ?>
			</select>
		</div>

		<button type="button" class="cb-listing-search__button">
			<?php echo esc_html( $button_text ); ?>
		</button>

		<div class="cb-listing-search__results" hidden></div>
	</div>
</div>
