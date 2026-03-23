/**
 * Admin term edit URL (fallback when REST omits `link`).
 *
 * @param {number} termId
 * @returns {string}
 */
export function categoryTermEditUrl( termId ) {
	const { adminUrl, categoryTaxonomy, postType } = window.cbListingAdmin;
	const base = String( adminUrl || '' ).replace( /\/?$/, '/' );
	let u;
	try {
		u = new URL( 'term.php', base );
	} catch {
		u = new URL( 'term.php', window.location.origin + base );
	}
	u.searchParams.set( 'taxonomy', categoryTaxonomy );
	u.searchParams.set( 'tag_ID', String( termId ) );
	if ( postType ) {
		u.searchParams.set( 'post_type', postType );
	}
	return u.toString();
}

/**
 * Public term archive URL from REST `term.link` (see WP_REST_Terms_Controller).
 *
 * @param {{ id?: number, link?: string }} term
 * @returns {string}
 */
export function categoryTermArchiveUrl( term ) {
	if ( term && typeof term.link === 'string' && term.link.trim() !== '' ) {
		return term.link;
	}
	if ( term?.id ) {
		return categoryTermEditUrl( term.id );
	}
	return '#';
}
