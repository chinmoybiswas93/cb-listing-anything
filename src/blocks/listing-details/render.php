<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_the_ID();

if ( ! $post_id || 'listing' !== get_post_type( $post_id ) ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$preview = get_posts( array(
			'post_type'      => 'listing',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		) );

		if ( empty( $preview ) ) {
			echo '<p>' . esc_html__( 'No listings found for preview.', 'cb-listing-anything' ) . '</p>';
			return;
		}

		$post_id = $preview[0]->ID;
	} else {
		return;
	}
}

$show_price    = isset( $attributes['showPrice'] ) ? $attributes['showPrice'] : true;
$show_location = isset( $attributes['showLocation'] ) ? $attributes['showLocation'] : true;
$show_address  = isset( $attributes['showAddress'] ) ? $attributes['showAddress'] : true;
$show_contact  = isset( $attributes['showContact'] ) ? $attributes['showContact'] : true;
$show_website  = isset( $attributes['showWebsite'] ) ? $attributes['showWebsite'] : true;

$price    = get_post_meta( $post_id, '_listing_price', true );
$location = get_post_meta( $post_id, '_listing_location', true );
$address  = get_post_meta( $post_id, '_listing_address', true );
$city     = get_post_meta( $post_id, '_listing_city', true );
$state    = get_post_meta( $post_id, '_listing_state', true );
$zip      = get_post_meta( $post_id, '_listing_zip_code', true );
$country  = get_post_meta( $post_id, '_listing_country', true );
$email    = get_post_meta( $post_id, '_listing_contact_email', true );
$phone    = get_post_meta( $post_id, '_listing_contact_phone', true );
$website  = get_post_meta( $post_id, '_listing_website', true );

$has_content = false;

if ( $show_price && $price ) { $has_content = true; }
if ( $show_location && $location ) { $has_content = true; }
if ( $show_address && ( $address || $city || $state || $zip || $country ) ) { $has_content = true; }
if ( $show_contact && ( $email || $phone ) ) { $has_content = true; }
if ( $show_website && $website ) { $has_content = true; }

if ( ! $has_content ) {
	return;
}

$wrapper = get_block_wrapper_attributes( array(
	'class' => 'cb-listing-details',
) );
?>
<div <?php echo $wrapper; ?>>
	<div class="cb-listing-details__grid">

		<?php if ( $show_price && $price ) : ?>
		<div class="cb-listing-details__item">
			<span class="cb-listing-details__label"><?php esc_html_e( 'Price', 'cb-listing-anything' ); ?></span>
			<span class="cb-listing-details__value cb-listing-details__value--price"><?php echo esc_html( $price ); ?></span>
		</div>
		<?php endif; ?>

		<?php if ( $show_location && $location ) : ?>
		<div class="cb-listing-details__item">
			<span class="cb-listing-details__label"><?php esc_html_e( 'Location', 'cb-listing-anything' ); ?></span>
			<span class="cb-listing-details__value"><?php echo esc_html( $location ); ?></span>
		</div>
		<?php endif; ?>

		<?php if ( $show_address && $address ) : ?>
		<div class="cb-listing-details__item">
			<span class="cb-listing-details__label"><?php esc_html_e( 'Address', 'cb-listing-anything' ); ?></span>
			<span class="cb-listing-details__value"><?php echo esc_html( $address ); ?></span>
		</div>
		<?php endif; ?>

		<?php if ( $show_address && $city ) : ?>
		<div class="cb-listing-details__item">
			<span class="cb-listing-details__label"><?php esc_html_e( 'City', 'cb-listing-anything' ); ?></span>
			<span class="cb-listing-details__value"><?php echo esc_html( $city ); ?></span>
		</div>
		<?php endif; ?>

		<?php if ( $show_address && $state ) : ?>
		<div class="cb-listing-details__item">
			<span class="cb-listing-details__label"><?php esc_html_e( 'State / Province', 'cb-listing-anything' ); ?></span>
			<span class="cb-listing-details__value"><?php echo esc_html( $state ); ?></span>
		</div>
		<?php endif; ?>

		<?php if ( $show_address && $zip ) : ?>
		<div class="cb-listing-details__item">
			<span class="cb-listing-details__label"><?php esc_html_e( 'ZIP / Postal Code', 'cb-listing-anything' ); ?></span>
			<span class="cb-listing-details__value"><?php echo esc_html( $zip ); ?></span>
		</div>
		<?php endif; ?>

		<?php if ( $show_address && $country ) : ?>
		<div class="cb-listing-details__item">
			<span class="cb-listing-details__label"><?php esc_html_e( 'Country', 'cb-listing-anything' ); ?></span>
			<span class="cb-listing-details__value"><?php echo esc_html( $country ); ?></span>
		</div>
		<?php endif; ?>

		<?php if ( $show_contact && $email ) : ?>
		<div class="cb-listing-details__item">
			<span class="cb-listing-details__label"><?php esc_html_e( 'Email', 'cb-listing-anything' ); ?></span>
			<span class="cb-listing-details__value">
				<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
			</span>
		</div>
		<?php endif; ?>

		<?php if ( $show_contact && $phone ) : ?>
		<div class="cb-listing-details__item">
			<span class="cb-listing-details__label"><?php esc_html_e( 'Phone', 'cb-listing-anything' ); ?></span>
			<span class="cb-listing-details__value">
				<a href="tel:<?php echo esc_attr( $phone ); ?>"><?php echo esc_html( $phone ); ?></a>
			</span>
		</div>
		<?php endif; ?>

		<?php if ( $show_website && $website ) : ?>
		<div class="cb-listing-details__item">
			<span class="cb-listing-details__label"><?php esc_html_e( 'Website', 'cb-listing-anything' ); ?></span>
			<span class="cb-listing-details__value">
				<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $website ); ?></a>
			</span>
		</div>
		<?php endif; ?>

	</div>
</div>
