/**
 * @param {Object} term REST term object
 * @param {string} key Meta key
 * @returns {number}
 */
export function termMetaInt( term, key ) {
	const m = term.meta || {};
	const v = m[ key ];
	if ( v === undefined || v === null || v === '' ) {
		return 0;
	}
	return typeof v === 'number' ? v : parseInt( v, 10 ) || 0;
}

/**
 * @param {Object[]} terms
 * @param {number} parent
 * @param {number} depth
 * @returns {Array<Object & { depth: number }>}
 */
export function flattenTermsTree( terms, parent = 0, depth = 0 ) {
	const out = [];
	const children = terms
		.filter( ( t ) => t.parent === parent )
		.sort( ( a, b ) => a.name.localeCompare( b.name ) );
	for ( const t of children ) {
		out.push( { ...t, depth } );
		out.push( ...flattenTermsTree( terms, t.id, depth + 1 ) );
	}
	return out;
}

/**
 * @param {Object[]} terms
 * @param {number} rootId
 * @returns {number[]}
 */
export function getDescendantIds( terms, rootId ) {
	const ids = [];
	const walk = ( id ) => {
		const children = terms.filter( ( t ) => t.parent === id );
		for ( const c of children ) {
			ids.push( c.id );
			walk( c.id );
		}
	};
	walk( rootId );
	return ids;
}
