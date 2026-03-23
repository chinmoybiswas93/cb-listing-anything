/**
 * Listings table: sort fields (WP REST posts orderby) and column visibility.
 */

/** Valid WP REST `orderby` values for listings. */
export const LISTING_SORT_VALUES = [
	'date',
	'modified',
	'title',
	'author',
	'id',
	'relevance',
];

/**
 * Relative units for flexible columns only (title, status, …). `num` / `thumb` use fixed % in colgroup.
 */
export const LISTING_COLUMN_WIDTH_UNITS = {
	num: 0,
	thumb: 0,
	title: 26,
	status: 9,
	categories: 14,
	author: 10,
	date: 11,
};

export const LISTING_COLUMN_KEYS = Object.keys( LISTING_COLUMN_WIDTH_UNITS );

/** Default: all data columns visible (checkbox + actions are always on). */
export function defaultVisibleColumns() {
	return {
		num: true,
		thumb: true,
		title: true,
		status: true,
		categories: true,
		author: true,
		date: true,
	};
}

const STORAGE_COLUMNS = 'cb-listing-admin-columns';
const STORAGE_SORT = 'cb-listing-admin-sort';

/**
 * @returns {Record<string, boolean>}
 */
export function loadVisibleColumns() {
	try {
		const raw = localStorage.getItem( STORAGE_COLUMNS );
		if ( ! raw ) {
			return defaultVisibleColumns();
		}
		const parsed = JSON.parse( raw );
		if ( typeof parsed !== 'object' || parsed === null ) {
			return defaultVisibleColumns();
		}
		const base = defaultVisibleColumns();
		for ( const key of LISTING_COLUMN_KEYS ) {
			if ( typeof parsed[ key ] === 'boolean' ) {
				base[ key ] = parsed[ key ];
			}
		}
		return base;
	} catch {
		return defaultVisibleColumns();
	}
}

/**
 * @param {Record<string, boolean>} cols
 */
export function saveVisibleColumns( cols ) {
	try {
		localStorage.setItem( STORAGE_COLUMNS, JSON.stringify( cols ) );
	} catch {
		// ignore
	}
}

/**
 * @returns {{ orderby: string, order: string }}
 */
export function loadSortPrefs() {
	try {
		const raw = localStorage.getItem( STORAGE_SORT );
		if ( ! raw ) {
			return { orderby: 'date', order: 'desc' };
		}
		const parsed = JSON.parse( raw );
		const orderby =
			typeof parsed.orderby === 'string' ? parsed.orderby : 'date';
		const order =
			parsed.order === 'asc' || parsed.order === 'desc'
				? parsed.order
				: 'desc';
		return {
			orderby: LISTING_SORT_VALUES.includes( orderby ) ? orderby : 'date',
			order,
		};
	} catch {
		return { orderby: 'date', order: 'desc' };
	}
}

/**
 * @param {string} orderby
 * @param {string} order
 */
export function saveSortPrefs( orderby, order ) {
	try {
		localStorage.setItem(
			STORAGE_SORT,
			JSON.stringify( { orderby, order } )
		);
	} catch {
		// ignore
	}
}

/** Fixed % of total table width for # and Image (not scaled; avoids 1-unit column ≈ 1% fighting the layout). */
const LISTING_LEAD_COL_PCT = {
	num: 2.5,
	thumb: 5.5,
};

const CHECK_COL_PCT = 3.5;
const ACTIONS_COL_PCT = 18;

/**
 * Colgroup: checkbox + fixed lead (# / image) + scaled flex columns + actions.
 *
 * @param {Record<string, boolean>} visible
 * @returns {Array<{ width: string }>}
 */
export function buildListingColgroup( visible ) {
	const cols = [ { width: `${ CHECK_COL_PCT }%` } ];

	let flexSum = 0;
	for ( const key of LISTING_COLUMN_KEYS ) {
		if ( visible[ key ] && key !== 'num' && key !== 'thumb' ) {
			flexSum += LISTING_COLUMN_WIDTH_UNITS[ key ];
		}
	}

	const fixedLead =
		( visible.num ? LISTING_LEAD_COL_PCT.num : 0 ) +
		( visible.thumb ? LISTING_LEAD_COL_PCT.thumb : 0 );

	const flexTarget =
		100 - CHECK_COL_PCT - ACTIONS_COL_PCT - fixedLead;
	const scale = flexSum > 0 ? flexTarget / flexSum : 1;

	for ( const key of LISTING_COLUMN_KEYS ) {
		if ( ! visible[ key ] ) {
			continue;
		}
		if ( key === 'num' ) {
			cols.push( { width: `${ LISTING_LEAD_COL_PCT.num }%` } );
		} else if ( key === 'thumb' ) {
			cols.push( { width: `${ LISTING_LEAD_COL_PCT.thumb }%` } );
		} else {
			const pct = ( LISTING_COLUMN_WIDTH_UNITS[ key ] * scale ).toFixed( 2 );
			cols.push( { width: `${ pct }%` } );
		}
	}
	cols.push( { width: `${ ACTIONS_COL_PCT }%` } );
	return cols;
}

/**
 * @param {Record<string, boolean>} visible
 * @returns {number}
 */
export function countVisibleDataColumns( visible ) {
	let n = 0;
	for ( const key of LISTING_COLUMN_KEYS ) {
		if ( visible[ key ] ) {
			n++;
		}
	}
	return n;
}
