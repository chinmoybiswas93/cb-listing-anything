import { useState, useCallback } from '@wordpress/element';

/** Allowed values for REST `per_page` (matches common WP list screens). */
export const ADMIN_TABLE_PER_PAGE_OPTIONS = [ 10, 20, 50, 100 ];

const STORAGE_PREFIX = 'cb-listing-admin-per-page-';

/**
 * Persisted per-page preference for admin data tables (localStorage).
 *
 * @param {string} storageKey Short id, e.g. 'listings', 'categories', 'tags'.
 * @param {number}            defaultValue Default when nothing stored (must be in ADMIN_TABLE_PER_PAGE_OPTIONS).
 * @returns {[number, (value: number|string) => void]}
 */
export function useAdminTablePerPage( storageKey, defaultValue = 20 ) {
	const lsKey = STORAGE_PREFIX + storageKey;

	const [ perPage, setPerPageState ] = useState( () =>
		readStoredPerPage( lsKey, defaultValue )
	);

	const setPerPage = useCallback(
		( value ) => {
			const n =
				typeof value === 'number' ? value : parseInt( String( value ), 10 );
			if ( ! ADMIN_TABLE_PER_PAGE_OPTIONS.includes( n ) ) {
				return;
			}
			setPerPageState( n );
			try {
				localStorage.setItem( lsKey, String( n ) );
			} catch {
				// ignore private mode / quota
			}
		},
		[ lsKey ]
	);

	return [ perPage, setPerPage ];
}

/**
 * @param {string} lsKey
 * @param {number} defaultValue
 * @returns {number}
 */
function readStoredPerPage( lsKey, defaultValue ) {
	try {
		const raw = localStorage.getItem( lsKey );
		if ( raw === null ) {
			return defaultValue;
		}
		const n = parseInt( raw, 10 );
		if ( ADMIN_TABLE_PER_PAGE_OPTIONS.includes( n ) ) {
			return n;
		}
	} catch {
		// ignore
	}
	return defaultValue;
}
