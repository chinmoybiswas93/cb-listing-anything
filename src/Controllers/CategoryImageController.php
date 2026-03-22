<?php

namespace CBListingAnything\Controllers;

use CBListingAnything\Core\AbstractController;

/**
 * Adds category image (term meta) and admin UI for cb_listing_category.
 */
class CategoryImageController extends AbstractController {

	const META_KEY = 'cb_listing_anything_category_image';

	/**
	 * Unix timestamp when the term was created (React admin table date column).
	 */
	const TERM_CREATED_META = 'cb_listing_term_created';

	public function init() {
		$cat_tax = crocodevs_config( 'taxonomies.category' );
		$tag_tax = crocodevs_config( 'taxonomies.tag' );

		add_action( 'init', array( $this, 'register_term_meta_rest' ), 20 );

		add_action( 'created_' . $cat_tax, array( $this, 'set_term_created_timestamp' ) );
		add_action( 'created_' . $tag_tax, array( $this, 'set_term_created_timestamp' ) );

		add_action( $cat_tax . '_add_form_fields', array( $this, 'add_form_fields' ) );
		add_action( $cat_tax . '_edit_form_fields', array( $this, 'edit_form_fields' ) );
		add_action( 'created_' . $cat_tax, array( $this, 'save_term_image' ) );
		add_action( 'edited_' . $cat_tax, array( $this, 'save_term_image' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_script' ) );
	}

	/**
	 * Expose term meta to the REST API for the React admin UI.
	 *
	 * @return void
	 */
	public function register_term_meta_rest() {
		$cat_tax = crocodevs_config( 'taxonomies.category' );
		$tag_tax = crocodevs_config( 'taxonomies.tag' );

		register_term_meta(
			$cat_tax,
			self::META_KEY,
			array(
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'term_meta_auth' ),
			)
		);

		foreach ( array( $cat_tax, $tag_tax ) as $taxonomy ) {
			register_term_meta(
				$taxonomy,
				self::TERM_CREATED_META,
				array(
					'type'              => 'integer',
					'single'            => true,
					'sanitize_callback' => 'absint',
					'show_in_rest'      => true,
					'auth_callback'     => array( $this, 'term_meta_auth' ),
				)
			);
		}
	}

	/**
	 * @param bool   $allowed Whether the user can add this meta.
	 * @param string $meta_key Meta key.
	 * @param int    $term_id Term ID.
	 * @return bool
	 */
	public function term_meta_auth( $allowed, $meta_key, $term_id ) {
		return current_user_can( 'edit_term', (int) $term_id );
	}

	/**
	 * Store creation time for new terms (used in admin list date column).
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function set_term_created_timestamp( $term_id ) {
		$term_id = (int) $term_id;
		if ( $term_id <= 0 ) {
			return;
		}
		if ( (int) get_term_meta( $term_id, self::TERM_CREATED_META, true ) > 0 ) {
			return;
		}
		update_term_meta( $term_id, self::TERM_CREATED_META, time() );
	}

	/**
	 * Category image field on Add form.
	 */
	public function add_form_fields() {
		$this->render_add_image_field();
	}

	/**
	 * Category image field on Edit form.
	 *
	 * @param \WP_Term $term Current term.
	 */
	public function edit_form_fields( $term ) {
		$image_id  = (int) get_term_meta( $term->term_id, self::META_KEY, true );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
		$name      = 'cb_listing_anything_category_image';
		?>
		<tr class="form-field term-cb-listing-category-image-wrap">
			<th scope="row"><label for="<?php echo esc_attr( $name ); ?>"><?php esc_html_e( 'Category Image', 'cb-listing-anything' ); ?></label></th>
			<td>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $image_id ); ?>" />
				<div class="cb-listing-category-image-preview" style="margin:8px 0;">
					<?php if ( $image_url ) : ?>
						<img src="<?php echo esc_url( $image_url ); ?>" alt="" style="max-width:150px;height:auto;display:block;border:1px solid #ddd;" />
					<?php endif; ?>
				</div>
				<p>
					<button type="button" class="button cb-listing-category-image-select"><?php esc_html_e( 'Select Image', 'cb-listing-anything' ); ?></button>
					<?php if ( $image_id ) : ?>
						<button type="button" class="button cb-listing-category-image-remove"><?php esc_html_e( 'Remove', 'cb-listing-anything' ); ?></button>
					<?php endif; ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Output image field markup on Add form.
	 */
	private function render_add_image_field() {
		$name = 'cb_listing_anything_category_image';
		?>
		<div class="form-field term-cb-listing-category-image-wrap">
			<label for="<?php echo esc_attr( $name ); ?>"><?php esc_html_e( 'Category Image', 'cb-listing-anything' ); ?></label>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" value="" />
			<div class="cb-listing-category-image-preview" style="margin:8px 0;"></div>
			<p>
				<button type="button" class="button cb-listing-category-image-select"><?php esc_html_e( 'Select Image', 'cb-listing-anything' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Save category image from add/edit form.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save_term_image( $term_id ) {
		if ( ! isset( $_POST['cb_listing_anything_category_image'] ) ) {
			return;
		}
		$image_id = absint( $_POST['cb_listing_anything_category_image'] );
		if ( $image_id > 0 && ! wp_attachment_is_image( $image_id ) ) {
			$image_id = 0;
		}
		update_term_meta( $term_id, self::META_KEY, $image_id );
	}

	/**
	 * Enqueue media modal and inline script on listing category add/edit screen.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_media_script( $hook_suffix ) {
		if ( 'term.php' !== $hook_suffix && 'edit-tags.php' !== $hook_suffix ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || $screen->taxonomy !== crocodevs_config( 'taxonomies.category' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_add_inline_script( 'jquery', $this->get_inline_script() );
	}

	private function get_inline_script() {
		return <<<'JS'
(function($){
	$(function(){
		var frame;
		var $wrap = $('.term-cb-listing-category-image-wrap');
		if (!$wrap.length) return;
		var $input = $wrap.find('input[name="cb_listing_anything_category_image"]');
		var $preview = $wrap.find('.cb-listing-category-image-preview');

		$wrap.on('click', '.cb-listing-category-image-select', function(e){
			e.preventDefault();
			if (frame) { frame.open(); return; }
			frame = wp.media({
				title: 'Select category image',
				library: { type: 'image' },
				multiple: false,
				button: { text: 'Use image' }
			});
			frame.on('select', function(){
				var att = frame.state().get('selection').first().toJSON();
				$input.val(att.id);
				$preview.html('<img src="'+(att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url)+'" alt="" style="max-width:150px;height:auto;display:block;border:1px solid #ddd;" />');
				if (!$wrap.find('.cb-listing-category-image-remove').length) {
					$wrap.find('p').first().append(' <button type="button" class="button cb-listing-category-image-remove">Remove</button>');
				}
			});
			frame.open();
		});
		$wrap.on('click', '.cb-listing-category-image-remove', function(e){
			e.preventDefault();
			$input.val('');
			$preview.empty();
			$(this).remove();
		});
	});
})(jQuery);
JS;
	}
}
