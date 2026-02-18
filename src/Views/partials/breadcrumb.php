<?php
/**
 * Breadcrumb partial for listing pages.
 *
 * @package CBListingAnything
 * @var int $post_id Post ID for the listing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if we're on archive or category pages
$is_archive = is_post_type_archive( 'cb_listing' );
$is_category = is_tax( 'cb_listing_category' );
$is_tag = is_tax( 'cb_listing_tag' );

// Get post_id from passed variable or current post (only for single pages)
$breadcrumb_post_id = isset( $post_id ) ? absint( $post_id ) : ( is_singular( 'cb_listing' ) ? get_the_ID() : 0 );

// For archive/category pages, don't use post ID
if ( $is_archive || $is_category || $is_tag ) {
	$breadcrumb_post_id = 0;
}

// Get current term for category/tag archives
$current_term = null;
if ( $is_category || $is_tag ) {
	$current_term = get_queried_object();
}

// Get styles from parent scope if set
$breadcrumb_style = isset( $breadcrumb_style ) ? $breadcrumb_style : '';
$link_style = isset( $link_style ) ? $link_style : '';
$current_style = isset( $current_style ) ? $current_style : '';
?>
<nav class="cb-listing-breadcrumb"<?php echo $breadcrumb_style; ?> aria-label="<?php esc_attr_e( 'Breadcrumb', 'cb-listing-anything' ); ?>">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo $link_style; ?>><?php esc_html_e( 'Home', 'cb-listing-anything' ); ?></a>
	<span class="cb-listing-breadcrumb__sep">/</span>
	<a href="<?php echo esc_url( get_post_type_archive_link( 'cb_listing' ) ); ?>"<?php echo $link_style; ?>><?php esc_html_e( 'Listings', 'cb-listing-anything' ); ?></a>
	<?php if ( $is_category && $current_term ) : ?>
		<span class="cb-listing-breadcrumb__sep">/</span>
		<span class="cb-listing-breadcrumb__current"<?php echo $current_style; ?>><?php echo esc_html( $current_term->name ); ?></span>
	<?php elseif ( $is_tag && $current_term ) : ?>
		<span class="cb-listing-breadcrumb__sep">/</span>
		<span class="cb-listing-breadcrumb__current"<?php echo $current_style; ?>><?php echo esc_html( $current_term->name ); ?></span>
	<?php elseif ( ! $is_archive && $breadcrumb_post_id && 'cb_listing' === get_post_type( $breadcrumb_post_id ) ) : ?>
		<?php
		// Single listing page: show category and post title
		$categories = get_the_terms( $breadcrumb_post_id, 'cb_listing_category' );
		if ( $categories && ! is_wp_error( $categories ) ) :
			?>
			<span class="cb-listing-breadcrumb__sep">/</span>
			<a href="<?php echo esc_url( get_term_link( $categories[0] ) ); ?>"<?php echo $link_style; ?>><?php echo esc_html( $categories[0]->name ); ?></a>
		<?php endif; ?>
		<span class="cb-listing-breadcrumb__sep">/</span>
		<span class="cb-listing-breadcrumb__current"<?php echo $current_style; ?>><?php echo esc_html( get_the_title( $breadcrumb_post_id ) ); ?></span>
	<?php endif; ?>
</nav>
