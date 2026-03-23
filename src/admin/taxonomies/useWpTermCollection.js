import {
	useState,
	useEffect,
	useCallback,
	useRef,
	useMemo,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useToast } from '../context/ToastContext';
import { useAdminTablePerPage } from '../components/admin-list';
import {
	buildTermColgroup,
	countTermDataColumns,
	loadTermSortPrefs,
	loadTermVisibleColumns,
	saveTermSortPrefs,
	saveTermVisibleColumns,
} from './termTableConfig';

/**
 * Shared list/table state for WP REST terms (categories or tags).
 *
 * @param {Object}   options
 * @param {string}   options.restBase REST route segment (e.g. from cbListingAdmin.categoryRestBase).
 * @param {'categories'|'tags'} options.variant Storage key for prefs + colgroup.
 * @param {string}   options.loadErrorMessage Toast when list request fails (i18n string).
 */
export function useWpTermCollection( { restBase, variant, loadErrorMessage } ) {
	const { showToast } = useToast();

	const [ searchInput, setSearchInput ] = useState( '' );
	const [ activeSearch, setActiveSearch ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ loading, setLoading ] = useState( true );
	const [ rows, setRows ] = useState( [] );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ totalItems, setTotalItems ] = useState( 0 );
	const [ selectedIds, setSelectedIds ] = useState( () => new Set() );
	const [ bulkBusy, setBulkBusy ] = useState( false );
	const [ bulkAction, setBulkAction ] = useState( '' );
	const selectAllRef = useRef( null );

	const [ perPage, setPerPage ] = useAdminTablePerPage( variant, 20 );
	const [ sortState, setSortState ] = useState( () =>
		loadTermSortPrefs( variant )
	);
	const [ visibleColumns, setVisibleColumns ] = useState( () =>
		loadTermVisibleColumns( variant )
	);
	const { orderby, order } = sortState;

	const colgroupWidths = useMemo(
		() => buildTermColgroup( variant, visibleColumns ),
		[ variant, visibleColumns ]
	);
	const emptyColSpan = useMemo(
		() => 2 + countTermDataColumns( variant, visibleColumns ),
		[ variant, visibleColumns ]
	);

	const pathForFetch = useCallback( () => {
		const params = new URLSearchParams();
		params.set( 'context', 'edit' );
		params.set( 'per_page', String( perPage ) );
		params.set( 'page', String( page ) );
		params.set( 'orderby', orderby );
		params.set( 'order', order );
		if ( activeSearch.trim() ) {
			params.set( 'search', activeSearch.trim() );
		}
		return `wp/v2/${ restBase }?${ params.toString() }`;
	}, [ restBase, page, perPage, activeSearch, orderby, order ] );

	const load = useCallback( async () => {
		setLoading( true );
		try {
			const response = await apiFetch( {
				path: pathForFetch(),
				parse: false,
			} );
			if ( ! response.ok ) {
				const errBody = await response.json().catch( () => ( {} ) );
				throw new Error(
					errBody.message ||
						response.statusText ||
						__( 'Request failed.', 'cb-listing-anything' )
				);
			}
			const data = await response.json();
			const total = response.headers.get( 'X-WP-Total' );
			const pages = response.headers.get( 'X-WP-TotalPages' );
			setRows( Array.isArray( data ) ? data : [] );
			setTotalItems( total ? parseInt( total, 10 ) : 0 );
			setTotalPages( pages ? parseInt( pages, 10 ) : 1 );
		} catch ( e ) {
			showToast( e.message || loadErrorMessage, 'error' );
			setRows( [] );
		} finally {
			setLoading( false );
		}
	}, [ pathForFetch, showToast, loadErrorMessage ] );

	useEffect( () => {
		load();
	}, [ load ] );

	const handleSortApply = useCallback(
		( ob, ord ) => {
			saveTermSortPrefs( variant, ob, ord );
			setSortState( { orderby: ob, order: ord } );
			setPage( 1 );
		},
		[ variant ]
	);

	const handleColumnsApply = useCallback(
		( cols ) => {
			saveTermVisibleColumns( variant, cols );
			setVisibleColumns( cols );
		},
		[ variant ]
	);

	useEffect( () => {
		setSelectedIds( new Set() );
		setBulkAction( '' );
	}, [ page, perPage, activeSearch, orderby, order ] );

	useEffect( () => {
		const el = selectAllRef.current;
		if ( ! el ) {
			return;
		}
		if ( rows.length === 0 ) {
			el.indeterminate = false;
			return;
		}
		el.indeterminate =
			selectedIds.size > 0 && selectedIds.size < rows.length;
	}, [ selectedIds, rows ] );

	const toggleSelectAll = useCallback( () => {
		if ( rows.length === 0 ) {
			return;
		}
		setSelectedIds( ( prev ) => {
			if ( prev.size === rows.length ) {
				return new Set();
			}
			return new Set( rows.map( ( r ) => r.id ) );
		} );
	}, [ rows ] );

	const toggleRowSelected = useCallback( ( id ) => {
		setSelectedIds( ( prev ) => {
			const next = new Set( prev );
			if ( next.has( id ) ) {
				next.delete( id );
			} else {
				next.add( id );
			}
			return next;
		} );
	}, [] );

	const onSearchSubmit = useCallback(
		( ev ) => {
			ev.preventDefault();
			setActiveSearch( searchInput );
			setPage( 1 );
		},
		[ searchInput ]
	);

	return {
		searchInput,
		setSearchInput,
		page,
		setPage,
		loading,
		rows,
		totalPages,
		totalItems,
		selectedIds,
		setSelectedIds,
		bulkBusy,
		setBulkBusy,
		bulkAction,
		setBulkAction,
		selectAllRef,
		perPage,
		setPerPage,
		orderby,
		order,
		visibleColumns,
		setVisibleColumns,
		colgroupWidths,
		emptyColSpan,
		load,
		handleSortApply,
		handleColumnsApply,
		toggleSelectAll,
		toggleRowSelected,
		onSearchSubmit,
	};
}
