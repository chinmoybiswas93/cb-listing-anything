<?php
/**
 * Render the listing breadcrumb block.
 *
 * @package CBListingAnything
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if we're on archive/category pages (these don't need a post_id)
$is_archive = is_post_type_archive( 'cb_listing' );
$is_category = is_tax( 'cb_listing_category' );
$is_tag = is_tax( 'cb_listing_tag' );

// Only get post_id for single listing pages or REST API preview
$post_id = null;
if ( is_singular( 'cb_listing' ) ) {
	$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_the_ID();
} elseif ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
	// For REST API (block editor preview), get a preview post
	$preview = get_posts( array(
		'post_type'      => 'cb_listing',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
	) );

	if ( empty( $preview ) ) {
		echo '<p>' . esc_html__( 'No listings found for preview.', 'cb-listing-anything' ) . '</p>';
		return;
	}

	$post_id = $preview[0]->ID;
}

// Validate post_id only if we have one (not needed for archive pages)
if ( $post_id && 'cb_listing' !== get_post_type( $post_id ) ) {
	return;
}

// Get attributes (passed as first parameter to render callback)
$attributes = isset( $attributes ) && is_array( $attributes ) ? $attributes : array();

// Get styling attributes
$text_align   = isset( $attributes['textAlign'] ) ? sanitize_text_field( $attributes['textAlign'] ) : 'left';
$text_color   = isset( $attributes['textColor'] ) ? sanitize_hex_color( $attributes['textColor'] ) : '';
$hover_color  = isset( $attributes['hoverColor'] ) ? sanitize_hex_color( $attributes['hoverColor'] ) : '#0073aa';
$current_color = isset( $attributes['currentColor'] ) ? sanitize_hex_color( $attributes['currentColor'] ) : '';
$font_size    = isset( $attributes['fontSize'] ) ? sanitize_text_field( $attributes['fontSize'] ) : '';
$font_weight  = isset( $attributes['fontWeight'] ) ? sanitize_text_field( $attributes['fontWeight'] ) : '';
$spacing      = isset( $attributes['spacing'] ) && is_array( $attributes['spacing'] ) ? $attributes['spacing'] : array();
$margin_bottom = isset( $spacing['margin']['bottom'] ) ? sanitize_text_field( $spacing['margin']['bottom'] ) : '1rem';

// Build inline styles
$wrapper_styles = array();
$breadcrumb_styles = array();
$link_styles = array();
$current_styles = array();

// Map text-align to justify-content for flex container
$justify_map = array(
	'left'   => 'flex-start',
	'center' => 'center',
	'right'  => 'flex-end',
);

if ( $text_align ) {
	$wrapper_styles[] = 'text-align: ' . esc_attr( $text_align );
	$justify_value = isset( $justify_map[ $text_align ] ) ? $justify_map[ $text_align ] : 'flex-start';
	$breadcrumb_styles[] = 'justify-content: ' . esc_attr( $justify_value );
}

if ( $font_size ) {
	$breadcrumb_styles[] = 'font-size: ' . esc_attr( $font_size );
}

if ( $font_weight ) {
	$breadcrumb_styles[] = 'font-weight: ' . esc_attr( $font_weight );
}

if ( $margin_bottom ) {
	$breadcrumb_styles[] = 'margin-bottom: ' . esc_attr( $margin_bottom );
}

if ( $text_color ) {
	$link_styles[] = 'color: ' . esc_attr( $text_color );
}

if ( $current_color ) {
	$current_styles[] = 'color: ' . esc_attr( $current_color );
}

$wrapper_style_attr = ! empty( $wrapper_styles ) ? ' style="' . implode( '; ', $wrapper_styles ) . '"' : '';
$breadcrumb_style_attr = ! empty( $breadcrumb_styles ) ? ' style="' . implode( '; ', $breadcrumb_styles ) . '"' : '';
$link_style_attr = ! empty( $link_styles ) ? ' style="' . implode( '; ', $link_styles ) . '"' : '';
$current_style_attr = ! empty( $current_styles ) ? ' style="' . implode( '; ', $current_styles ) . '"' : '';

// Generate CSS custom property for hover color
$hover_color_css = $hover_color ? '--cb-breadcrumb-hover-color: ' . esc_attr( $hover_color ) . ';' : '';

$wrapper = get_block_wrapper_attributes( array(
	'class' => 'cb-listing-breadcrumb-wrapper',
	'style' => $hover_color_css,
) );
?>
<div <?php echo $wrapper . $wrapper_style_attr; ?>>
	<?php
	// Set variables for the partial
	$breadcrumb_style = $breadcrumb_style_attr;
	$link_style = $link_style_attr;
	$current_style = $current_style_attr;
	// Pass post_id to partial (may be null for archive pages)
	$post_id = isset( $post_id ) ? $post_id : null;
	// Include the shared breadcrumb partial
	include CB_LISTING_ANYTHING_PLUGIN_DIR . 'src/Views/partials/breadcrumb.php';
	?>
</div>
