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

$is_archive = is_post_type_archive( crocodevs_config('post_type.slug') );
$is_category = is_tax( crocodevs_config('taxonomies.category') );
$is_tag = is_tax( crocodevs_config('taxonomies.tag') );

$breadcrumb_post_id = isset( $post_id ) ? absint( $post_id ) : ( is_singular( crocodevs_config('post_type.slug') ) ? get_the_ID() : 0 );

if ( $is_archive || $is_category || $is_tag ) {
	$breadcrumb_post_id = 0;
}

$current_term = null;
if ( $is_category || $is_tag ) {
	$current_term = get_queried_object();
}

$breadcrumb_style = isset( $breadcrumb_style ) ? $breadcrumb_style : '';
$link_style = isset( $link_style ) ? $link_style : '';
$current_style = isset( $current_style ) ? $current_style : '';

$is_listing_context = $is_archive || $is_category || $is_tag || ( $breadcrumb_post_id && crocodevs_config('post_type.slug') === get_post_type( $breadcrumb_post_id ) );
?>
<nav class="cb-listing-breadcrumb"<?php echo $breadcrumb_style; ?> aria-label="<?php esc_attr_e( 'Breadcrumb', 'cb-listing-anything' ); ?>">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo $link_style; ?> class="cb-listing-breadcrumb__home">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
			<path d="M3 11.5 12 3l9 8.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M5 10.5V21h5v-5h4v5h5V10.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
		</svg>
		<span><?php esc_html_e( 'Home', 'cb-listing-anything' ); ?></span>
	</a>
	<?php if ( $is_listing_context ) : ?>
		<span class="cb-listing-breadcrumb__sep">/</span>
		<a href="<?php echo esc_url( get_post_type_archive_link( crocodevs_config('post_type.slug') ) ); ?>"<?php echo $link_style; ?>><?php esc_html_e( 'Listings', 'cb-listing-anything' ); ?></a>
		<?php if ( $is_category && $current_term ) : ?>
			<span class="cb-listing-breadcrumb__sep">/</span>
			<span class="cb-listing-breadcrumb__current"<?php echo $current_style; ?>><?php echo esc_html( $current_term->name ); ?></span>
		<?php elseif ( $is_tag && $current_term ) : ?>
			<span class="cb-listing-breadcrumb__sep">/</span>
			<span class="cb-listing-breadcrumb__current"<?php echo $current_style; ?>><?php echo esc_html( $current_term->name ); ?></span>
		<?php elseif ( ! $is_archive && $breadcrumb_post_id && crocodevs_config('post_type.slug') === get_post_type( $breadcrumb_post_id ) ) : ?>
			<?php
			// Single listing page: show category and post title
			$categories = get_the_terms( $breadcrumb_post_id, crocodevs_config('taxonomies.category') );
			if ( $categories && ! is_wp_error( $categories ) ) :
				?>
				<span class="cb-listing-breadcrumb__sep">/</span>
				<a href="<?php echo esc_url( get_term_link( $categories[0] ) ); ?>"<?php echo $link_style; ?>><?php echo esc_html( $categories[0]->name ); ?></a>
			<?php endif; ?>
			<span class="cb-listing-breadcrumb__sep">/</span>
			<span class="cb-listing-breadcrumb__current"<?php echo $current_style; ?>><?php echo esc_html( get_the_title( $breadcrumb_post_id ) ); ?></span>
		<?php endif; ?>
	<?php else : ?>
		<?php
		// Non-listing context: simple "Home / Page Title" breadcrumb for singular pages.
		if ( ! is_front_page() && is_singular() ) :
			?>
			<span class="cb-listing-breadcrumb__sep">/</span>
			<span class="cb-listing-breadcrumb__current"<?php echo $current_style; ?>><?php echo esc_html( get_the_title() ); ?></span>
		<?php endif; ?>
	<?php endif; ?>
</nav>
