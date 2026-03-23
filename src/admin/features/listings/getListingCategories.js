/**
 * Listing categories from REST `_embedded['wp:term']`, or from `termsById` + post taxonomy IDs.
 *
 * @param {Object} post
 * @param {string} categoryTaxonomy
 * @param {Record<number, { id: number, name: string, taxonomy: string, link?: string }>} [termsById]
 * @param {string} [categoryRestBase] REST base for category taxonomy (fallback field name on post).
 * @returns {Array<{ id: number, name: string, taxonomy: string, link?: string }>}
 */
export function getListingCategories(
	post,
	categoryTaxonomy,
	termsById,
	categoryRestBase
) {
	const embedded = post._embedded?.[ 'wp:term' ];
	if ( Array.isArray( embedded ) ) {
		const flat = embedded.flat().filter( Boolean );
		const fromEmbed = flat
			.filter( ( t ) => t.taxonomy === categoryTaxonomy )
			.sort( ( a, b ) =>
				( a.name || '' ).localeCompare( b.name || '', undefined, {
					sensitivity: 'base',
				} )
			);
		if ( fromEmbed.length ) {
			return fromEmbed;
		}
	}

	let raw = post[ categoryTaxonomy ];
	if ( raw == null && categoryRestBase && post[ categoryRestBase ] != null ) {
		raw = post[ categoryRestBase ];
	}
	const ids = Array.isArray( raw )
		? raw.map( ( id ) => Number( id ) ).filter( ( id ) => id > 0 )
		: [];
	if ( ! ids.length || ! termsById || typeof termsById !== 'object' ) {
		return [];
	}
	return ids
		.map( ( id ) => termsById[ id ] )
		.filter( Boolean )
		.sort( ( a, b ) =>
			( a.name || '' ).localeCompare( b.name || '', undefined, {
				sensitivity: 'base',
			} )
		);
}
