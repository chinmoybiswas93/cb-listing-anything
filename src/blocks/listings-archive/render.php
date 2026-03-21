<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attributes = isset( $attributes ) && is_array( $attributes ) ? $attributes : array();
$defaults   = array(
	'showFilterSidebar'   => true,
	'showFilterCategory'  => true,
	'showFilterTag'       => true,
	'showFilterPrice'     => true,
	'showProductCount'    => true,
	'showSorting'         => true,
	'postsPerPage'        => 12,
	'columns'             => 3,
	'columnsTablet'       => 2,
	'columnsMobile'       => 1,
	'orderBy'             => 'date',
	'showCategories'      => true,
	'showOpenStatus'      => true,
	'showPrice'           => true,
	'showTags'            => true,
	'showAddress'             => true,
	'showCallButton'          => true,
	'showCategoryTabs'        => false,
	'showSubcategoryButtons'   => false,
	'showEmptyCategories'      => false,
	'cardTemplate'             => 'listing-card',
);
$attrs      = array_merge( $defaults, is_array( $attributes ) ? $attributes : array() );

$per_page = absint( $attrs['postsPerPage'] );
$columns  = max( 1, min( 4, absint( $attrs['columns'] ) ) );
$columns_tablet = max( 1, min( 4, absint( isset( $attrs['columnsTablet'] ) ? $attrs['columnsTablet'] : 2 ) ) );
$columns_mobile = max( 1, min( 4, absint( isset( $attrs['columnsMobile'] ) ? $attrs['columnsMobile'] : 1 ) ) );

$current_term = null;
$is_category_archive = false;
$is_tag_archive = false;

if ( is_tax( crocodevs_config('taxonomies.category') ) ) {
	$current_term = get_queried_object();
	$is_category_archive = true;
} elseif ( is_tax( crocodevs_config('taxonomies.tag') ) ) {
	$current_term = get_queried_object();
	$is_tag_archive = true;
}

// Category tabs / subcategory buttons: load terms and resolve parent/children
$parent_terms       = array();
$current_parent     = null;
$subcategory_terms   = array();
$all_tab_url        = get_post_type_archive_link( crocodevs_config('post_type.slug') );
if ( ! empty( $attrs['showCategoryTabs'] ) ) {
	$hide_empty_cats = empty( $attrs['showEmptyCategories'] );
	$all_cat_terms = get_terms( array(
		'taxonomy'   => crocodevs_config('taxonomies.category'),
		'hide_empty' => $hide_empty_cats,
		'orderby'    => 'term_id',
		'order'      => 'ASC',
	) );
	if ( ! is_wp_error( $all_cat_terms ) && ! empty( $all_cat_terms ) ) {
		$parent_terms = array_filter( $all_cat_terms, function( $t ) { return 0 === (int) $t->parent; } );
	}
	if ( $is_category_archive && $current_term ) {
		if ( 0 === (int) $current_term->parent ) {
			$current_parent = $current_term;
		} else {
			$current_parent = get_term( (int) $current_term->parent, crocodevs_config('taxonomies.category') );
			if ( is_wp_error( $current_parent ) || ! $current_parent ) {
				$current_parent = $current_term;
			}
		}
		if ( $current_parent && ! empty( $attrs['showSubcategoryButtons'] ) ) {
			$subcategory_terms = get_terms( array(
				'taxonomy'   => crocodevs_config('taxonomies.category'),
				'parent'     => $current_parent->term_id,
				'hide_empty' => $hide_empty_cats,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			) );
			if ( is_wp_error( $subcategory_terms ) ) {
				$subcategory_terms = array();
			}
		}
	}
}

$filters    = \CBListingAnything\Helpers\ArchiveHelper::parse_filters( $is_category_archive, $is_tag_archive, $current_term, $attrs['orderBy'] );
$filter_cat = $filters['filter_cat'];
$filter_tag = $filters['filter_tag'];
$price_min  = $filters['price_min'];
$price_max  = $filters['price_max'];
$orderby    = $filters['orderby'];
$paged      = $filters['paged'];
$query      = \CBListingAnything\Helpers\ArchiveHelper::build_query( $filters, $per_page );

if ( is_post_type_archive( crocodevs_config('post_type.slug') ) ) {
	$archive_url = get_post_type_archive_link( crocodevs_config('post_type.slug') );
	global $wp;
	$current_path = untrailingslashit( $wp->request );
	if ( $current_path ) {
		$form_action = home_url( '/' . $current_path . '/' );
	} else {
		$form_action = $archive_url;
	}
	$form_action = remove_query_arg( array( 'paged', 'page', 'listing_category', 'listing_tag', 'price_min', 'price_max', 'orderby' ), $form_action );
} elseif ( $is_category_archive || $is_tag_archive ) {
	$form_action = get_term_link( $current_term );
	if ( is_wp_error( $form_action ) ) {
		$form_action = get_post_type_archive_link( crocodevs_config('post_type.slug') );
	}
	$form_action = remove_query_arg( array( 'paged', 'page', 'listing_category', 'listing_tag', 'price_min', 'price_max', 'orderby' ), $form_action );
} else {
	$archive_url = get_post_type_archive_link( crocodevs_config('post_type.slug') );
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

$pagination_base = $form_action;
$pagination_base = remove_query_arg( array( 'paged', 'page' ), $pagination_base );

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

$sep = strpos( $pagination_base, '?' ) !== false ? '&' : '?';
$pagination_base .= $sep . 'paged=%#%';

$show_categories  = ! empty( $attrs['showCategories'] );
$show_open_status = ! empty( $attrs['showOpenStatus'] );
$show_price       = ! empty( $attrs['showPrice'] );
$show_tags        = ! empty( $attrs['showTags'] );
$show_address     = ! empty( $attrs['showAddress'] );
$show_call_button = ! empty( $attrs['showCallButton'] );

$card_template = isset( $attrs['cardTemplate'] ) && $attrs['cardTemplate'] === 'product-card' ? 'product-card' : 'listing-card';

$show_filter_sidebar = ! empty( $attrs['showFilterSidebar'] ) && ( ! empty( $attrs['showFilterCategory'] ) || ! empty( $attrs['showFilterTag'] ) || ! empty( $attrs['showFilterPrice'] ) );
$wrapper_class = 'cb-listings-archive' . ( $show_filter_sidebar ? '' : ' cb-listings-archive--no-sidebar' );
$wrapper = get_block_wrapper_attributes( array( 'class' => $wrapper_class ) );

$show_category_tabs = ! empty( $attrs['showCategoryTabs'] );
$show_subcategory_buttons = ! empty( $attrs['showSubcategoryButtons'] );
$is_all_archive = ! $is_category_archive && ! $is_tag_archive;
?>
<div <?php echo $wrapper; ?>>
	<?php if ( $show_category_tabs ) : ?>
	<div class="cb-listings-archive__category-nav">
		<nav class="cb-listings-archive__category-tabs" aria-label="<?php esc_attr_e( 'Listing categories', 'cb-listing-anything' ); ?>">
			<ul class="cb-listings-archive__category-tabs-list">
				<li class="cb-listings-archive__category-tab-item">
					<a href="<?php echo esc_url( $all_tab_url ); ?>" class="cb-listings-archive__category-tab <?php echo $is_all_archive ? ' cb-listings-archive__category-tab--active' : ''; ?>"><?php esc_html_e( 'All', 'cb-listing-anything' ); ?></a>
				</li>
				<?php foreach ( $parent_terms as $parent_term ) :
					$term_link = get_term_link( $parent_term );
					if ( is_wp_error( $term_link ) ) {
						continue;
					}
					$is_active = $is_category_archive && $current_parent && (int) $current_parent->term_id === (int) $parent_term->term_id;
				?>
				<li class="cb-listings-archive__category-tab-item">
					<a href="<?php echo esc_url( $term_link ); ?>" class="cb-listings-archive__category-tab<?php echo $is_active ? ' cb-listings-archive__category-tab--active' : ''; ?>"><?php echo esc_html( $parent_term->name ); ?></a>
				</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php if ( $show_subcategory_buttons && $is_category_archive && $current_parent && ! empty( $subcategory_terms ) ) : ?>
		<nav class="cb-listings-archive__subcategory-buttons" aria-label="<?php esc_attr_e( 'Subcategories', 'cb-listing-anything' ); ?>">
			<ul class="cb-listings-archive__subcategory-buttons-list">
				<?php
				$parent_link = get_term_link( $current_parent );
				$is_parent_active = $current_term && (int) $current_term->term_id === (int) $current_parent->term_id;
				if ( ! is_wp_error( $parent_link ) ) :
				?>
				<li class="cb-listings-archive__subcategory-btn-item">
					<a href="<?php echo esc_url( $parent_link ); ?>" class="cb-listings-archive__subcategory-btn<?php echo $is_parent_active ? ' cb-listings-archive__subcategory-btn--active' : ''; ?>"><?php esc_html_e( 'All', 'cb-listing-anything' ); ?></a>
				</li>
				<?php endif; ?>
				<?php foreach ( $subcategory_terms as $child_term ) :
					$child_link = get_term_link( $child_term );
					if ( is_wp_error( $child_link ) ) {
						continue;
					}
					$is_child_active = $current_term && (int) $current_term->term_id === (int) $child_term->term_id;
				?>
				<li class="cb-listings-archive__subcategory-btn-item">
					<a href="<?php echo esc_url( $child_link ); ?>" class="cb-listings-archive__subcategory-btn<?php echo $is_child_active ? ' cb-listings-archive__subcategory-btn--active' : ''; ?>"><?php echo esc_html( $child_term->name ); ?></a>
				</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php endif; ?>
	</div>
	<?php endif; ?>
	<div class="cb-listings-archive__inner">
		<?php if ( $show_filter_sidebar ) :
		?>
		<aside class="cb-listings-archive__filters">
			<div class="cb-listings-archive__filters-header">
				<h3 class="cb-listings-archive__filters-title"><?php esc_html_e( 'Filters', 'cb-listing-anything' ); ?></h3>
				<div class="cb-listings-archive__filters-header-right">
					<?php if ( ! empty( $filter_tag ) || $price_min > 0 || $price_max > 0 ) : ?>
						<a href="#" class="cb-listings-archive__clear-filters"><?php esc_html_e( 'Clear', 'cb-listing-anything' ); ?></a>
					<?php else : ?>
						<a href="#" class="cb-listings-archive__clear-filters" style="display: none;"><?php esc_html_e( 'Clear', 'cb-listing-anything' ); ?></a>
					<?php endif; ?>
					<button type="button" class="cb-listings-archive__filters-toggle" aria-expanded="false" aria-label="<?php esc_attr_e( 'Toggle filters', 'cb-listing-anything' ); ?>">
						<span class="cb-listings-archive__filters-toggle-icon">▼</span>
					</button>
				</div>
			</div>
			<form method="get" action="<?php echo esc_url( $form_action ); ?>" class="cb-listings-archive__filters-form">
				<?php if ( ! empty( $attrs['showFilterTag'] ) ) :
					$tag_terms = get_terms( array( 'taxonomy' => crocodevs_config('taxonomies.tag'), 'hide_empty' => true ) );
					if ( ! is_wp_error( $tag_terms ) && ! empty( $tag_terms ) ) :
				?>
				<div class="cb-listings-archive__filter-section">
					<h4 class="cb-listings-archive__filter-heading"><?php esc_html_e( 'Tag', 'cb-listing-anything' ); ?></h4>
					<?php foreach ( $tag_terms as $term ) : 
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

		<?php
			$show_count   = ! empty( $attrs['showProductCount'] );
			$show_sort    = ! empty( $attrs['showSorting'] );
			$show_top_bar = $show_count || $show_sort;
			$main_class   = 'cb-listings-archive__main' . ( $show_top_bar ? '' : ' cb-listings-archive__main--no-top-bar' );
		?>
		<div class="<?php echo esc_attr( $main_class ); ?>">
			<?php if ( $show_top_bar ) :
			?>
			<div class="cb-listings-archive__top-bar">
				<?php if ( $show_count ) : ?>
				<span class="cb-listings-archive__count">
					<?php
					printf(
						/* translators: %d: number of items */
						esc_html__( 'Showing: (%d Items)', 'cb-listing-anything' ),
						(int) $query->found_posts
					);
					?>
				</span>
				<?php endif; ?>
				<?php if ( $show_sort ) : ?>
				<form method="get" action="<?php echo esc_url( $form_action ); ?>" class="cb-listings-archive__sort-form">
					<label for="cb-listings-archive-orderby" class="cb-listings-archive__sort-label"><?php esc_html_e( 'Sort By', 'cb-listing-anything' ); ?></label>
					<select name="orderby" id="cb-listings-archive-orderby" class="cb-listings-archive__sort-select">
						<option value="date" <?php selected( $orderby, 'date' ); ?>><?php esc_html_e( 'Latest', 'cb-listing-anything' ); ?></option>
						<option value="date_asc" <?php selected( $orderby, 'date_asc' ); ?>><?php esc_html_e( 'Oldest', 'cb-listing-anything' ); ?></option>
						<option value="title" <?php selected( $orderby, 'title' ); ?>><?php esc_html_e( 'Title A–Z', 'cb-listing-anything' ); ?></option>
						<option value="price_asc" <?php selected( $orderby, 'price_asc' ); ?>><?php esc_html_e( 'Price low–high', 'cb-listing-anything' ); ?></option>
						<option value="price_desc" <?php selected( $orderby, 'price_desc' ); ?>><?php esc_html_e( 'Price high–low', 'cb-listing-anything' ); ?></option>
					</select>
				</form>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if ( $query->have_posts() ) : ?>
				<?php
				$grid_class = 'cb-listings-archive__grid cb-listing-cols-d-' . (int) $columns . ' cb-listing-cols-t-' . (int) $columns_tablet . ' cb-listing-cols-m-' . (int) $columns_mobile;
				if ( $card_template === 'product-card' ) {
					$grid_class .= ' cb-listings-archive__grid--product-cards';
				} else {
					$grid_class .= ' cb-listing-cards';
				}
				?>
				<div class="<?php echo $grid_class; ?>">
					<?php
					while ( $query->have_posts() ) {
						$query->the_post();
						if ( $card_template === 'product-card' ) {
							include crocodevs_view_path( 'partials/product-card' );
						} else {
							include crocodevs_view_path( 'partials/listing-card' );
						}
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
