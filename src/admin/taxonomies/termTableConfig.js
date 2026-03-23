/**
 * Categories / Tags admin tables: REST orderby + column visibility.
 *
 * WP REST terms: orderby id, include, name, slug, term_group, description, count, parent
 */

export const TERM_SORT_VALUES = [
	'name',
	'slug',
	'count',
	'id',
	'description',
	'parent',
];

/** Category list columns (excluding checkbox + actions). */
export const CATEGORY_COLUMN_KEYS = [
	'thumb',
	'name',
	'description',
	'slug',
	'count',
];

/** Tag list columns. */
export const TAG_COLUMN_KEYS = [ 'name', 'description', 'slug', 'count' ];

const CATEGORY_UNITS = {
	thumb: 8,
	name: 30,
	slug: 18,
	count: 10,
	description: 20,
};

const TAG_UNITS = {
	name: 28,
	slug: 22,
	count: 10,
	description: 20,
};

const CHECK_PCT = 4;

const ACTIONS_PCT = {
	categories: 18,
	tags: 22,
};

/**
 * @param {'categories'|'tags'} variant
 * @returns {Record<string, boolean>}
 */
export function defaultTermVisibleColumns( variant ) {
	const keys =
		variant === 'categories' ? CATEGORY_COLUMN_KEYS : TAG_COLUMN_KEYS;
	const o = {};
	for ( const k of keys ) {
		o[ k ] = true;
	}
	return o;
}

/**
 * @param {'categories'|'tags'} variant
 * @returns {Record<string, boolean>}
 */
export function loadTermVisibleColumns( variant ) {
	const key = `cb-listing-admin-term-columns-${ variant }`;
	try {
		const raw = localStorage.getItem( key );
		if ( ! raw ) {
			return defaultTermVisibleColumns( variant );
		}
		const parsed = JSON.parse( raw );
		if ( typeof parsed !== 'object' || parsed === null ) {
			return defaultTermVisibleColumns( variant );
		}
		const base = defaultTermVisibleColumns( variant );
		const keys =
			variant === 'categories' ? CATEGORY_COLUMN_KEYS : TAG_COLUMN_KEYS;
		for ( const k of keys ) {
			if ( typeof parsed[ k ] === 'boolean' ) {
				base[ k ] = parsed[ k ];
			}
		}
		return base;
	} catch {
		return defaultTermVisibleColumns( variant );
	}
}

/**
 * @param {'categories'|'tags'}     variant
 * @param {Record<string, boolean>} cols
 */
export function saveTermVisibleColumns( variant, cols ) {
	const key = `cb-listing-admin-term-columns-${ variant }`;
	try {
		localStorage.setItem( key, JSON.stringify( cols ) );
	} catch {
		// ignore
	}
}

/**
 * @param {'categories'|'tags'} variant
 * @returns {{ orderby: string, order: string }}
 */
export function loadTermSortPrefs( variant ) {
	const key = `cb-listing-admin-term-sort-${ variant }`;
	const defaults =
		variant === 'categories'
			? { orderby: 'name', order: 'asc' }
			: { orderby: 'name', order: 'asc' };
	try {
		const raw = localStorage.getItem( key );
		if ( ! raw ) {
			return defaults;
		}
		const parsed = JSON.parse( raw );
		let orderby =
			typeof parsed.orderby === 'string' ? parsed.orderby : defaults.orderby;
		const order =
			parsed.order === 'asc' || parsed.order === 'desc'
				? parsed.order
				: defaults.order;
		const allowed =
			variant === 'categories'
				? TERM_SORT_VALUES
				: TERM_SORT_VALUES.filter( ( v ) => v !== 'parent' );
		if ( ! allowed.includes( orderby ) ) {
			orderby = defaults.orderby;
		}
		if ( orderby === 'parent' && variant !== 'categories' ) {
			orderby = 'name';
		}
		return { orderby, order };
	} catch {
		return defaults;
	}
}

/**
 * @param {'categories'|'tags'} variant
 * @param {string}                orderby
 * @param {string}                order
 */
export function saveTermSortPrefs( variant, orderby, order ) {
	const key = `cb-listing-admin-term-sort-${ variant }`;
	try {
		localStorage.setItem( key, JSON.stringify( { orderby, order } ) );
	} catch {
		// ignore
	}
}

/**
 * @param {'categories'|'tags'}     variant
 * @param {Record<string, boolean>} visible
 * @returns {Array<{ width: string }>}
 */
export function buildTermColgroup( variant, visible ) {
	const units = variant === 'categories' ? CATEGORY_UNITS : TAG_UNITS;
	const keys =
		variant === 'categories' ? CATEGORY_COLUMN_KEYS : TAG_COLUMN_KEYS;
	const actionsPct = ACTIONS_PCT[ variant ];
	const middleTarget = 100 - CHECK_PCT - actionsPct;
	let sum = 0;
	for ( const k of keys ) {
		if ( visible[ k ] ) {
			sum += units[ k ];
		}
	}
	const scale = sum > 0 ? middleTarget / sum : 1;
	const cols = [ { width: `${ CHECK_PCT }%` } ];
	for ( const k of keys ) {
		if ( visible[ k ] ) {
			const pct = ( units[ k ] * scale ).toFixed( 2 );
			cols.push( { width: `${ pct }%` } );
		}
	}
	cols.push( { width: `${ actionsPct }%` } );
	return cols;
}

/**
 * @param {'categories'|'tags'}     variant
 * @param {Record<string, boolean>} visible
 * @returns {number}
 */
export function countTermDataColumns( variant, visible ) {
	const keys =
		variant === 'categories' ? CATEGORY_COLUMN_KEYS : TAG_COLUMN_KEYS;
	let n = 0;
	for ( const k of keys ) {
		if ( visible[ k ] ) {
			n++;
		}
	}
	return n;
}
