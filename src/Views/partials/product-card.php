<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id   = get_the_ID();
$subtitle  = get_post_meta( $post_id, '_listing_subtitle', true );
$permalink = get_the_permalink();
?>
<article class="cb-product-card">
	<div class="cb-product-card__image">
		<a href="<?php echo esc_url( $permalink ); ?>">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'medium_large' ); ?>
			<?php else : ?>
				<span class="cb-product-card__placeholder"></span>
			<?php endif; ?>
		</a>
		<a href="<?php echo esc_url( $permalink ); ?>" class="cb-product-card__btn"><?php esc_html_e( 'View Details', 'cb-listing-anything' ); ?></a>
	</div>
	<?php if ( $subtitle !== '' && $subtitle !== null ) : ?>
		<p class="cb-product-card__subtitle"><?php echo esc_html( $subtitle ); ?></p>
	<?php endif; ?>
	<h3 class="cb-product-card__title">
		<a href="<?php echo esc_url( $permalink ); ?>"><?php the_title(); ?></a>
	</h3>
</article>
