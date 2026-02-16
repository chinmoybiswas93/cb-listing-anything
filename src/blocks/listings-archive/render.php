<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attributes = isset( $attributes ) && is_array( $attributes ) ? $attributes : array();
$defaults   = array(
	'showFilterCategory' => true,
	'showFilterTag'       => true,
	'showFilterPrice'     => true,
	'postsPerPage'        => 12,
	'columns'             => 3,
	'orderBy'             => 'date',
	'showCategories'      => true,
	'showOpenStatus'      => true,
	'showPrice'           => true,
	'showTags'            => true,
	'showAddress'         => true,
	'showCallButton'      => true,
);
$attrs      = array_merge( $defaults, is_array( $attributes ) ? $attributes : array() );

$per_page = absint( $attrs['postsPerPage'] );
$columns  = max( 1, min( 4, absint( $attrs['columns'] ) ) );
$paged_get = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;
if ( ! $paged_get && isset( $_GET['page'] ) ) {
	$paged_get = absint( $_GET['page'] );
}
if ( ! $paged_get && get_query_var( 'paged' ) ) {
	$paged_get = absint( get_query_var( 'paged' ) );
}
$paged = max( 1, $paged_get );

// Check if we're on a taxonomy archive page and get the current term
$current_term = null;
$is_category_archive = false;
$is_tag_archive = false;

if ( is_tax( 'cb_listing_category' ) ) {
	$current_term = get_queried_object();
	$is_category_archive = true;
} elseif ( is_tax( 'cb_listing_tag' ) ) {
	$current_term = get_queried_object();
	$is_tag_archive = true;
}

// Get filter values from URL, or use current term if on taxonomy archive
$filter_cat = isset( $_GET['listing_category'] ) ? $_GET['listing_category'] : array();
if ( ! is_array( $filter_cat ) ) {
	$filter_cat = $filter_cat ? array( absint( $filter_cat ) ) : array();
} else {
	$filter_cat = array_map( 'absint', array_filter( $filter_cat ) );
}

// If on category archive and no explicit filter, use current category
if ( $is_category_archive && $current_term && empty( $filter_cat ) ) {
	$filter_cat = array( $current_term->term_id );
}

$filter_tag = isset( $_GET['listing_tag'] ) ? $_GET['listing_tag'] : array();
if ( ! is_array( $filter_tag ) ) {
	$filter_tag = $filter_tag ? array( absint( $filter_tag ) ) : array();
} else {
	$filter_tag = array_map( 'absint', array_filter( $filter_tag ) );
}

// If on tag archive and no explicit filter, use current tag
if ( $is_tag_archive && $current_term && empty( $filter_tag ) ) {
	$filter_tag = array( $current_term->term_id );
}
$price_min = isset( $_GET['price_min'] ) ? absint( preg_replace( '/[^0-9]/', '', wp_unslash( $_GET['price_min'] ) ) ) : 0;
$price_max = isset( $_GET['price_max'] ) ? absint( preg_replace( '/[^0-9]/', '', wp_unslash( $_GET['price_max'] ) ) ) : 0;
$orderby   = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : $attrs['orderBy'];
$allowed   = array( 'date', 'title', 'price_asc', 'price_desc' );
if ( ! in_array( $orderby, $allowed, true ) ) {
	$orderby = 'date';
}

$query_args = array(
	'post_type'      => 'cb_listing',
	'post_status'    => 'publish',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
);

$tax_clauses = array();
if ( ! empty( $filter_cat ) ) {
	$tax_clauses[] = array(
		'taxonomy' => 'cb_listing_category',
		'field'    => 'term_id',
		'terms'    => $filter_cat,
	);
}
if ( ! empty( $filter_tag ) ) {
	$tax_clauses[] = array(
		'taxonomy' => 'cb_listing_tag',
		'field'    => 'term_id',
		'terms'    => $filter_tag,
	);
}
if ( count( $tax_clauses ) > 1 ) {
	$query_args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_clauses );
} elseif ( ! empty( $tax_clauses ) ) {
	$query_args['tax_query'] = $tax_clauses;
}

$meta_clauses = array();
if ( $price_min > 0 || $price_max > 0 ) {
	if ( $price_min > 0 && $price_max > 0 ) {
		$meta_clauses[] = array(
			'key'     => '_listing_price',
			'value'   => array( $price_min, $price_max ),
			'type'    => 'NUMERIC',
			'compare' => 'BETWEEN',
		);
	} elseif ( $price_min > 0 ) {
		$meta_clauses[] = array(
			'key'     => '_listing_price',
			'value'   => $price_min,
			'type'    => 'NUMERIC',
			'compare' => '>=',
		);
	} else {
		$meta_clauses[] = array(
			'key'     => '_listing_price',
			'value'   => $price_max,
			'type'    => 'NUMERIC',
			'compare' => '<=',
		);
	}
}
if ( ! empty( $meta_clauses ) ) {
	$query_args['meta_query'] = $meta_clauses;
}

switch ( $orderby ) {
	case 'title':
		$query_args['orderby'] = 'title';
		$query_args['order']   = 'ASC';
		break;
	case 'price_asc':
		$query_args['orderby']  = 'meta_value_num';
		$query_args['meta_key'] = '_listing_price';
		$query_args['order']    = 'ASC';
		break;
	case 'price_desc':
		$query_args['orderby']  = 'meta_value_num';
		$query_args['meta_key'] = '_listing_price';
		$query_args['order']    = 'DESC';
		break;
	default:
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'DESC';
		break;
}

$query = new WP_Query( $query_args );

// Determine the base URL for forms and pagination
if ( is_post_type_archive( 'cb_listing' ) ) {
	$archive_url = get_post_type_archive_link( 'cb_listing' );
	global $wp;
	$current_path = untrailingslashit( $wp->request );
	if ( $current_path ) {
		$form_action = home_url( '/' . $current_path . '/' );
	} else {
		$form_action = $archive_url;
	}
	$form_action = remove_query_arg( array( 'paged', 'page', 'listing_category', 'listing_tag', 'price_min', 'price_max', 'orderby' ), $form_action );
} elseif ( $is_category_archive || $is_tag_archive ) {
	// On taxonomy archive, use the term archive URL
	$form_action = get_term_link( $current_term );
	if ( is_wp_error( $form_action ) ) {
		$form_action = get_post_type_archive_link( 'cb_listing' );
	}
	$form_action = remove_query_arg( array( 'paged', 'page', 'listing_category', 'listing_tag', 'price_min', 'price_max', 'orderby' ), $form_action );
} else {
	$archive_url = get_post_type_archive_link( 'cb_listing' );
	$form_action = $archive_url;
}

$base_args = array();
if ( ! empty( $filter_cat ) ) {
	$base_args['listing_category'] = $filter_cat;
}
if ( ! empty( $filter_tag ) ) {
	$base_args['listing_tag'] = $filter_tag;
}
if ( $price_min > 0 ) {
	$base_args['price_min'] = $price_min;
}
if ( $price_max > 0 ) {
	$base_args['price_max'] = $price_max;
}
if ( $orderby !== 'date' ) {
	$base_args['orderby'] = $orderby;
}

// Build pagination base URL using query params only
$pagination_base = $form_action;
$pagination_base = remove_query_arg( array( 'paged', 'page' ), $pagination_base );

// Add current filter params to pagination base first
if ( ! empty( $base_args ) ) {
	foreach ( $base_args as $key => $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $val ) {
				$pagination_base = add_query_arg( array( $key => $val ), $pagination_base );
			}
		} else {
			$pagination_base = add_query_arg( array( $key => $value ), $pagination_base );
		}
	}
}

// Add paged parameter format
$sep = strpos( $pagination_base, '?' ) !== false ? '&' : '?';
$pagination_base .= $sep . 'paged=%#%';

$show_categories  = ! empty( $attrs['showCategories'] );
$show_open_status = ! empty( $attrs['showOpenStatus'] );
$show_price       = ! empty( $attrs['showPrice'] );
$show_tags        = ! empty( $attrs['showTags'] );
$show_address     = ! empty( $attrs['showAddress'] );
$show_call_button = ! empty( $attrs['showCallButton'] );

$wrapper = get_block_wrapper_attributes( array( 'class' => 'cb-listings-archive' ) );
?>
<div <?php echo $wrapper; ?>>
	<div class="cb-listings-archive__inner">
		<?php if ( ! empty( $attrs['showFilterTag'] ) || ! empty( $attrs['showFilterPrice'] ) ) : ?>
		<aside class="cb-listings-archive__filters">
			<div class="cb-listings-archive__filters-header">
				<h3 class="cb-listings-archive__filters-title"><?php esc_html_e( 'Filters', 'cb-listing-anything' ); ?></h3>
				<?php if ( ! empty( $filter_tag ) || $price_min > 0 || $price_max > 0 ) : ?>
					<a href="#" class="cb-listings-archive__clear-filters"><?php esc_html_e( 'Clear', 'cb-listing-anything' ); ?></a>
				<?php else : ?>
					<a href="#" class="cb-listings-archive__clear-filters" style="display: none;"><?php esc_html_e( 'Clear', 'cb-listing-anything' ); ?></a>
				<?php endif; ?>
			</div>
			<form method="get" action="<?php echo esc_url( $form_action ); ?>" class="cb-listings-archive__filters-form">
				<?php if ( ! empty( $attrs['showFilterTag'] ) ) :
					$tag_terms = get_terms( array( 'taxonomy' => 'cb_listing_tag', 'hide_empty' => true ) );
					if ( ! is_wp_error( $tag_terms ) && ! empty( $tag_terms ) ) :
				?>
				<div class="cb-listings-archive__filter-section">
					<h4 class="cb-listings-archive__filter-heading"><?php esc_html_e( 'Tag', 'cb-listing-anything' ); ?></h4>
					<?php foreach ( $tag_terms as $term ) : 
						// Check if this term is in the filter array or is the current archive term
						$is_checked = in_array( $term->term_id, $filter_tag, true );
						if ( ! $is_checked && $is_tag_archive && $current_term && $current_term->term_id === $term->term_id ) {
							$is_checked = true;
						}
					?>
						<label class="cb-listings-archive__filter-label">
							<input type="checkbox" name="listing_tag[]" value="<?php echo esc_attr( $term->term_id ); ?>" <?php checked( $is_checked ); ?> />
							<span><?php echo esc_html( $term->name ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<?php endif; endif; ?>

				<?php if ( ! empty( $attrs['showFilterPrice'] ) ) : ?>
				<div class="cb-listings-archive__filter-section">
					<h4 class="cb-listings-archive__filter-heading"><?php esc_html_e( 'Price Range', 'cb-listing-anything' ); ?></h4>
					<div class="cb-listings-archive__price-inputs">
						<input type="number" name="price_min" value="<?php echo $price_min > 0 ? esc_attr( $price_min ) : ''; ?>" placeholder="0" min="0" step="1" class="cb-listings-archive__price-input" />
						<input type="number" name="price_max" value="<?php echo $price_max > 0 ? esc_attr( $price_max ) : ''; ?>" placeholder="" min="0" step="1" class="cb-listings-archive__price-input" />
					</div>
				</div>
				<?php endif; ?>
			</form>
		</aside>
		<?php endif; ?>

		<div class="cb-listings-archive__main">
			<div class="cb-listings-archive__top-bar">
				<span class="cb-listings-archive__count">
					<?php
					printf(
						/* translators: %d: number of items */
						esc_html__( 'Showing: (%d Items)', 'cb-listing-anything' ),
						(int) $query->found_posts
					);
					?>
				</span>
				<form method="get" action="<?php echo esc_url( $form_action ); ?>" class="cb-listings-archive__sort-form">
					<label for="cb-listings-archive-orderby" class="cb-listings-archive__sort-label"><?php esc_html_e( 'Sort By', 'cb-listing-anything' ); ?></label>
					<select name="orderby" id="cb-listings-archive-orderby" class="cb-listings-archive__sort-select">
						<option value="date" <?php selected( $orderby, 'date' ); ?>><?php esc_html_e( 'Newest', 'cb-listing-anything' ); ?></option>
						<option value="title" <?php selected( $orderby, 'title' ); ?>><?php esc_html_e( 'Title A–Z', 'cb-listing-anything' ); ?></option>
						<option value="price_asc" <?php selected( $orderby, 'price_asc' ); ?>><?php esc_html_e( 'Price low–high', 'cb-listing-anything' ); ?></option>
						<option value="price_desc" <?php selected( $orderby, 'price_desc' ); ?>><?php esc_html_e( 'Price high–low', 'cb-listing-anything' ); ?></option>
					</select>
				</form>
			</div>

			<?php if ( $query->have_posts() ) : ?>
				<div class="cb-listings-archive__grid cb-listing-cards cb-listing-cols-<?php echo esc_attr( (string) $columns ); ?>">
					<?php
					while ( $query->have_posts() ) {
						$query->the_post();
						include CB_LISTING_ANYTHING_PLUGIN_DIR . 'src/Views/partials/listing-card.php';
					}
					wp_reset_postdata();
					?>
				</div>

				<?php if ( $query->max_num_pages > 1 ) : ?>
				<nav class="cb-listings-archive__pagination cb-listings-archive-pagination" aria-label="<?php esc_attr_e( 'Listings pagination', 'cb-listing-anything' ); ?>">
					<?php
					$pagination = paginate_links( array(
						'base'      => $pagination_base,
						'format'    => '',
						'current'   => $paged,
						'total'     => $query->max_num_pages,
						'prev_text' => '← ' . __( 'Previous', 'cb-listing-anything' ),
						'next_text' => __( 'Next', 'cb-listing-anything' ) . ' →',
						'type'      => 'array',
						'add_args'  => false, // Don't add extra args, we've already added them to base
					) );
					if ( ! empty( $pagination ) ) {
						echo '<div class="cb-listings-archive-pagination__links">';
						foreach ( $pagination as $link ) {
							echo wp_kses_post( $link );
						}
						echo '</div>';
					}
					?>
				</nav>
				<?php endif; ?>
			<?php else : ?>
				<p class="cb-listings-archive__empty"><?php esc_html_e( 'No listings found.', 'cb-listing-anything' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
