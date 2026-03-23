/**
 * Thumbnail URL from a WP REST media object (e.g. batch /wp/v2/media).
 *
 * @param {Object|null|undefined} media
 * @returns {string}
 */
export function thumbUrlFromMediaObject( media ) {
	if ( ! media ) {
		return '';
	}
	const sizes = media.media_details?.sizes;
	return (
		sizes?.thumbnail?.source_url ||
		sizes?.medium?.source_url ||
		sizes?.woocommerce_thumbnail?.source_url ||
		media.source_url ||
		''
	);
}
