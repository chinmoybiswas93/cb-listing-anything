import {
	useState,
	useEffect,
	useCallback,
	useRef,
	Fragment,
	useMemo,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ListingThumb from '../components/ListingThumb';
import ListingsToolbarExtras from '../components/ListingsToolbarExtras';
import { useToast } from '../context/ToastContext';
import {
	AdminListPageHeader,
	AdminListToolbarRow,
	AdminBulkBar,
	AdminDataTable,
	AdminTablePagination,
	useAdminTablePerPage,
} from '../components/admin-list';
import {
	buildListingColgroup,
	loadVisibleColumns,
	saveVisibleColumns,
	loadSortPrefs,
	saveSortPrefs,
	countVisibleDataColumns,
} from '../features/listings/listingTableConfig';
import { thumbUrlFromMediaObject } from '../shared/media/thumbFromMedia';
import { stripTags } from '../shared/html/stripTags';
import { categoryTermArchiveUrl } from '../features/listings/categoryTermLinks';
import { getListingCategories } from '../features/listings/getListingCategories';

/** Shimmer rows shown while the first list request is in flight (no circle spinner). */
const SKELETON_ROW_COUNT = 10;

const STATUS_TABS = [
	{ id: 'all', label: __( 'All', 'cb-listing-anything' ), param: null },
	{ id: 'publish', label: __( 'Published', 'cb-listing-anything' ), param: 'publish' },
	{ id: 'draft', label: __( 'Draft', 'cb-listing-anything' ), param: 'draft' },
	{ id: 'pending', label: __( 'Pending', 'cb-listing-anything' ), param: 'pending' },
	{ id: 'private', label: __( 'Private', 'cb-listing-anything' ), param: 'private' },
	{ id: 'trash', label: __( 'Trash', 'cb-listing-anything' ), param: 'trash' },
];

export default function ListingsScreen() {
	const { restBase, adminUrl, categoryTaxonomy, categoryRestBase, allItemsLabel } =
		window.cbListingAdmin;
	const listPageHeading =
		typeof allItemsLabel === 'string' && allItemsLabel.trim() !== ''
			? allItemsLabel
			: __( 'All Listings', 'cb-listing-anything' );
	const { showToast } = useToast();
	const [ statusFilter, setStatusFilter ] = useState( 'all' );
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
	const [ featuredThumbUrls, setFeaturedThumbUrls ] = useState( null );
	const [ userNamesById, setUserNamesById ] = useState( {} );
	/** Term id → term object when `_embed` omits `wp:term` but post has taxonomy IDs. */
	const [ categoryTermsById, setCategoryTermsById ] = useState( {} );
	const selectAllRef = useRef( null );
	const [ perPage, setPerPage ] = useAdminTablePerPage( 'listings', 20 );
	const [ orderby, setOrderby ] = useState( () => loadSortPrefs().orderby );
	const [ order, setOrder ] = useState( () => loadSortPrefs().order );
	const [ visibleColumns, setVisibleColumns ] = useState( () =>
		loadVisibleColumns()
	);

	const colgroupWidths = useMemo(
		() => buildListingColgroup( visibleColumns ),
		[ visibleColumns ]
	);
	const dataColumnCount = useMemo(
		() => countVisibleDataColumns( visibleColumns ),
		[ visibleColumns ]
	);
	const emptyColSpan = 2 + dataColumnCount;

	const pathForFetch = useCallback( () => {
		const params = new URLSearchParams();
		params.set( 'context', 'edit' );
		params.set( 'per_page', String( perPage ) );
		params.set( 'page', String( page ) );
		let ob = orderby;
		if ( ob === 'relevance' && ! activeSearch.trim() ) {
			ob = 'date';
		}
		params.set( 'orderby', ob );
		params.set( 'order', order );
		if ( activeSearch.trim() ) {
			params.set( 'search', activeSearch.trim() );
		}
		const tab = STATUS_TABS.find( ( t ) => t.id === statusFilter );
		if ( tab && tab.param ) {
			params.set( 'status', tab.param );
		} else {
			params.set( 'status', 'any' );
		}
		params.set( '_embed', '1' );
		return `wp/v2/${ restBase }?${ params.toString() }`;
	}, [
		restBase,
		page,
		perPage,
		orderby,
		order,
		activeSearch,
		statusFilter,
	] );

	useEffect( () => {
		if ( ! activeSearch.trim() && orderby === 'relevance' ) {
			setOrderby( 'date' );
		}
	}, [ activeSearch, orderby ] );

	const handleSortApply = useCallback( ( ob, ord ) => {
		setOrderby( ob );
		setOrder( ord );
		saveSortPrefs( ob, ord );
		setPage( 1 );
	}, [] );

	const handleColumnsApply = useCallback( ( cols ) => {
		setVisibleColumns( cols );
		saveVisibleColumns( cols );
	}, [] );

	const load = useCallback( async () => {
		setLoading( true );
		try {
			const response = await apiFetch( {
				path: pathForFetch(),
				parse: false,
			} );
			if ( ! response.ok ) {
				const errBody = await response.json().catch( () => ( {} ) );
				throw new Error( errBody.message || response.statusText || __( 'Request failed.', 'cb-listing-anything' ) );
			}
			const data = await response.json();
			const total = response.headers.get( 'X-WP-Total' );
			const pages = response.headers.get( 'X-WP-TotalPages' );
			setRows( Array.isArray( data ) ? data : [] );
			setTotalItems( total ? parseInt( total, 10 ) : 0 );
			setTotalPages( pages ? parseInt( pages, 10 ) : 1 );
		} catch ( e ) {
			showToast(
				e.message || __( 'Could not load listings.', 'cb-listing-anything' ),
				'error'
			);
			setRows( [] );
		} finally {
			setLoading( false );
		}
	}, [ pathForFetch, showToast ] );

	useEffect( () => {
		load();
	}, [ load ] );

	/** Load category term objects when REST embed omits `wp:term` but post lists term IDs. */
	useEffect( () => {
		if ( ! rows.length || ! categoryRestBase || ! categoryTaxonomy ) {
			setCategoryTermsById( {} );
			return;
		}

		const needIds = new Set();
		for ( const post of rows ) {
			const embedded = post._embedded?.[ 'wp:term' ];
			let hasEmbed = false;
			if ( Array.isArray( embedded ) ) {
				const flat = embedded.flat().filter( Boolean );
				hasEmbed = flat.some( ( t ) => t.taxonomy === categoryTaxonomy );
			}
			if ( hasEmbed ) {
				continue;
			}
			let raw = post[ categoryTaxonomy ];
			if ( raw == null && categoryRestBase && post[ categoryRestBase ] != null ) {
				raw = post[ categoryRestBase ];
			}
			if ( Array.isArray( raw ) ) {
				raw.forEach( ( id ) => {
					const n = Number( id );
					if ( n > 0 ) {
						needIds.add( n );
					}
				} );
			}
		}

		if ( needIds.size === 0 ) {
			setCategoryTermsById( {} );
			return;
		}

		let cancelled = false;
		const include = [ ...needIds ].join( ',' );

		apiFetch( {
			path: `wp/v2/${ categoryRestBase }?include=${ include }&per_page=100&context=edit`,
		} )
			.then( ( terms ) => {
				if ( cancelled ) {
					return;
				}
				const map = {};
				for ( const t of Array.isArray( terms ) ? terms : [] ) {
					map[ t.id ] = t;
				}
				setCategoryTermsById( map );
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setCategoryTermsById( {} );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ rows, categoryRestBase, categoryTaxonomy ] );

	/** Batch featured image URLs + author names (no _embed on list — smaller JSON). */
	useEffect( () => {
		if ( ! rows.length ) {
			setFeaturedThumbUrls( {} );
			setUserNamesById( {} );
			return;
		}

		const mediaIds = [
			...new Set(
				rows.map( ( p ) => p.featured_media ).filter( ( id ) => id > 0 )
			),
		];
		const authorIds = [
			...new Set( rows.map( ( p ) => p.author ).filter( ( id ) => id > 0 ) ),
		];

		if ( mediaIds.length ) {
			setFeaturedThumbUrls( null );
		} else {
			setFeaturedThumbUrls( {} );
		}

		let cancelled = false;

		const run = async () => {
			const promises = [];

			if ( mediaIds.length ) {
				promises.push(
					apiFetch( {
						path: `wp/v2/media?include=${ mediaIds.join(
							','
						) }&per_page=100&_fields=id,source_url,media_details`,
					} )
						.then( ( items ) => {
							if ( cancelled ) {
								return;
							}
							const map = {};
							for ( const m of Array.isArray( items ) ? items : [] ) {
								map[ m.id ] = thumbUrlFromMediaObject( m );
							}
							setFeaturedThumbUrls( map );
						} )
						.catch( () => {
							if ( ! cancelled ) {
								setFeaturedThumbUrls( {} );
							}
						} )
				);
			}

			if ( authorIds.length ) {
				promises.push(
					apiFetch( {
						path: `wp/v2/users?include=${ authorIds.join(
							','
						) }&per_page=100&_fields=id,name`,
					} )
						.then( ( users ) => {
							if ( cancelled ) {
								return;
							}
							const map = {};
							for ( const u of Array.isArray( users ) ? users : [] ) {
								map[ u.id ] = u.name;
							}
							setUserNamesById( map );
						} )
						.catch( () => {
							if ( ! cancelled ) {
								setUserNamesById( {} );
							}
						} )
				);
			} else {
				setUserNamesById( {} );
			}

			await Promise.allSettled( promises );
		};

		run();

		return () => {
			cancelled = true;
		};
	}, [ rows ] );

	useEffect( () => {
		setSelectedIds( new Set() );
		setBulkAction( '' );
	}, [ page, perPage, statusFilter, activeSearch ] );

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

	const toggleSelectAll = () => {
		if ( rows.length === 0 ) {
			return;
		}
		if ( selectedIds.size === rows.length ) {
			setSelectedIds( new Set() );
		} else {
			setSelectedIds( new Set( rows.map( ( r ) => r.id ) ) );
		}
	};

	const toggleRowSelected = ( id ) => {
		setSelectedIds( ( prev ) => {
			const next = new Set( prev );
			if ( next.has( id ) ) {
				next.delete( id );
			} else {
				next.add( id );
			}
			return next;
		} );
	};

	const bulkApply = async () => {
		if ( selectedIds.size === 0 || ! bulkAction ) {
			return;
		}
		const ids = [ ...selectedIds ];
		const inTrash = statusFilter === 'trash';

		if ( bulkAction === 'trash' ) {
			if ( inTrash ) {
				if (
					! window.confirm(
						__( 'Delete selected listings permanently?', 'cb-listing-anything' )
					)
				) {
					return;
				}
			} else if (
				! window.confirm(
					__( 'Move selected listings to trash?', 'cb-listing-anything' )
				)
			) {
				return;
			}
		}

		setBulkBusy( true );
		try {
			if ( bulkAction === 'trash' ) {
				await Promise.all(
					ids.map( ( id ) =>
						apiFetch( {
							path: `wp/v2/${ restBase }/${ id }${ inTrash ? '?force=true' : '' }`,
							method: 'DELETE',
						} )
					)
				);
			} else if ( bulkAction === 'publish' || bulkAction === 'draft' ) {
				const status = bulkAction === 'publish' ? 'publish' : 'draft';
				await Promise.all(
					ids.map( ( id ) =>
						apiFetch( {
							path: `wp/v2/${ restBase }/${ id }`,
							method: 'POST',
							data: { status },
						} )
					)
				);
			}
			setSelectedIds( new Set() );
			setBulkAction( '' );
			await load();
		} catch ( e ) {
			showToast(
				e.message || __( 'Bulk action failed.', 'cb-listing-anything' ),
				'error'
			);
		} finally {
			setBulkBusy( false );
		}
	};

	const onSearchSubmit = ( ev ) => {
		ev.preventDefault();
		setActiveSearch( searchInput );
		setPage( 1 );
	};

	const trashPost = async ( id ) => {
		if ( ! window.confirm( __( 'Move this listing to trash?', 'cb-listing-anything' ) ) ) {
			return;
		}
		try {
			await apiFetch( {
				path: `wp/v2/${ restBase }/${ id }`,
				method: 'DELETE',
			} );
			load();
		} catch ( e ) {
			showToast(
				e.message || __( 'Could not trash listing.', 'cb-listing-anything' ),
				'error'
			);
		}
	};

	const restorePost = async ( id ) => {
		try {
			await apiFetch( {
				path: `wp/v2/${ restBase }/${ id }`,
				method: 'POST',
				data: { status: 'draft' },
			} );
			load();
		} catch ( e ) {
			showToast(
				e.message || __( 'Could not restore listing.', 'cb-listing-anything' ),
				'error'
			);
		}
	};

	const deletePermanent = async ( id ) => {
		if ( ! window.confirm( __( 'Delete permanently?', 'cb-listing-anything' ) ) ) {
			return;
		}
		try {
			await apiFetch( {
				path: `wp/v2/${ restBase }/${ id }?force=true`,
				method: 'DELETE',
			} );
			load();
		} catch ( e ) {
			showToast(
				e.message || __( 'Could not delete listing.', 'cb-listing-anything' ),
				'error'
			);
		}
	};

	const editUrl = ( id ) => {
		const base = adminUrl.endsWith( '/' ) ? adminUrl : `${ adminUrl }/`;
		return `${ base }post.php?post=${ id }&action=edit`;
	};

	const authorName = ( post ) => {
		const id = post.author;
		if ( id === 0 || id === '0' || id == null || id === '' ) {
			return __( 'None', 'cb-listing-anything' );
		}
		const name = userNamesById[ id ];
		if ( name ) {
			return name;
		}
		return `#${ id }`;
	};

	const thumbSrcForRow = ( post ) => {
		const fid = post.featured_media;
		if ( ! fid ) {
			return '';
		}
		if ( featuredThumbUrls === null ) {
			return null;
		}
		return featuredThumbUrls[ fid ] ?? '';
	};

	const fmtDate = ( iso ) => {
		if ( ! iso ) {
			return '—';
		}
		try {
			return new Date( iso ).toLocaleString();
		} catch {
			return iso;
		}
	};

	const bulkActions = [
		{ value: '', label: __( 'Bulk actions', 'cb-listing-anything' ) },
		{
			value: 'trash',
			label:
				statusFilter === 'trash'
					? __( 'Delete permanently', 'cb-listing-anything' )
					: __( 'Trash', 'cb-listing-anything' ),
		},
		{ value: 'publish', label: __( 'Publish', 'cb-listing-anything' ) },
		{ value: 'draft', label: __( 'Draft', 'cb-listing-anything' ) },
	];

	return (
		<div className="cb-admin-list">
			<div className="cb-admin-list__toolbar">
				<AdminListPageHeader
					title={ listPageHeading }
					itemCount={ totalItems }
				/>
				<AdminListToolbarRow
					start={
						<>
							<div className="cb-admin-list__tabs" role="tablist">
								{ STATUS_TABS.map( ( t ) => (
									<button
										key={ t.id }
										type="button"
										className={ `cb-admin-list__tab${ statusFilter === t.id ? ' is-active' : '' }` }
										onClick={ () => {
											setStatusFilter( t.id );
											setPage( 1 );
										} }
									>
										{ t.label }
									</button>
								) ) }
							</div>
							<AdminBulkBar
								selectId="cb-listing-bulk-action"
								bulkAction={ bulkAction }
								onBulkActionChange={ setBulkAction }
								onApply={ bulkApply }
								bulkBusy={ bulkBusy }
								selectedCount={ selectedIds.size }
								disableSelect={ selectedIds.size === 0 }
								disableApply={
									selectedIds.size === 0 || ! bulkAction
								}
								actions={ bulkActions }
							/>
						</>
					}
					end={
						<div className="cb-admin-list__toolbar-end">
							<form className="cb-admin-list__search" onSubmit={ onSearchSubmit }>
								<input
									type="search"
									className="cb-admin-list__search-input"
									placeholder={ __( 'Search…', 'cb-listing-anything' ) }
									value={ searchInput }
									onChange={ ( e ) => setSearchInput( e.target.value ) }
								/>
								<button type="submit" className="cb-admin-app__btn cb-admin-app__btn--ghost">
									{ __( 'Search', 'cb-listing-anything' ) }
								</button>
							</form>
							<ListingsToolbarExtras
								orderby={ orderby }
								order={ order }
								onSortApply={ handleSortApply }
								hasSearch={ Boolean( activeSearch.trim() ) }
								visibleColumns={ visibleColumns }
								onColumnsApply={ handleColumnsApply }
							/>
						</div>
					}
				/>
			</div>

			<AdminDataTable ariaBusy={ loading }>
						<colgroup>
							{ colgroupWidths.map( ( c, i ) => (
								<col key={ `cb-col-${ i }` } style={ { width: c.width } } />
							) ) }
						</colgroup>
						<thead>
							<tr>
								<th className="cb-admin-table__col-check" scope="col">
									<input
										ref={ selectAllRef }
										type="checkbox"
										checked={
											rows.length > 0 && selectedIds.size === rows.length
										}
										onChange={ toggleSelectAll }
										disabled={ rows.length === 0 }
										aria-label={ __( 'Select all', 'cb-listing-anything' ) }
									/>
								</th>
								{ visibleColumns.num && (
									<th className="cb-admin-table__col-num" scope="col">
										{ __( '#', 'cb-listing-anything' ) }
									</th>
								) }
								{ visibleColumns.thumb && (
									<th
										className="cb-admin-table__col-thumb"
										scope="col"
										title={ __( 'Featured image', 'cb-listing-anything' ) }
									>
										<span className="cb-admin-table__col-thumb-label">
											{ __( 'Image', 'cb-listing-anything' ) }
										</span>
									</th>
								) }
								{ visibleColumns.title && (
									<th className="cb-admin-table__col-title" scope="col">
										{ __( 'Title', 'cb-listing-anything' ) }
									</th>
								) }
								{ visibleColumns.status && (
									<th className="cb-admin-table__col-status" scope="col">
										{ __( 'Status', 'cb-listing-anything' ) }
									</th>
								) }
								{ visibleColumns.categories && (
									<th className="cb-admin-table__col-categories" scope="col">
										{ __( 'Categories', 'cb-listing-anything' ) }
									</th>
								) }
								{ visibleColumns.author && (
									<th className="cb-admin-table__col-author" scope="col">
										{ __( 'Author', 'cb-listing-anything' ) }
									</th>
								) }
								{ visibleColumns.date && (
									<th className="cb-admin-table__col-date" scope="col">
										{ __( 'Date', 'cb-listing-anything' ) }
									</th>
								) }
								<th className="cb-admin-table__col-actions" scope="col">
									{ __( 'Actions', 'cb-listing-anything' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ loading && rows.length === 0 &&
								Array.from( { length: SKELETON_ROW_COUNT } ).map(
									( _, i ) => (
										<tr
											key={ `cb-list-skeleton-${ i }` }
											className="cb-admin-table__row--skeleton"
											aria-hidden="true"
										>
											<td className="cb-admin-table__col-check">
												<span className="cb-admin-table__skeleton-box cb-admin-table__skeleton-box--check" />
											</td>
											{ visibleColumns.num && (
												<td className="cb-admin-table__col-num">
													<span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--num" />
												</td>
											) }
											{ visibleColumns.thumb && (
												<td className="cb-admin-table__col-thumb">
													<span className="cb-admin-table__thumb-skeleton" />
												</td>
											) }
											{ visibleColumns.title && (
												<td className="cb-admin-table__col-title">
													<span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--title" />
												</td>
											) }
											{ visibleColumns.status && (
												<td className="cb-admin-table__col-status">
													<span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--badge" />
												</td>
											) }
											{ visibleColumns.categories && (
												<td className="cb-admin-table__col-categories">
													<span className="cb-admin-table__skeleton-line" />
												</td>
											) }
											{ visibleColumns.author && (
												<td className="cb-admin-table__col-author">
													<span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--author" />
												</td>
											) }
											{ visibleColumns.date && (
												<td className="cb-admin-table__col-date">
													<span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--date" />
												</td>
											) }
											<td className="cb-admin-table__col-actions">
												<span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--actions" />
											</td>
										</tr>
									)
								) }
							{ ! loading && rows.length === 0 && (
								<tr>
									<td colSpan={ emptyColSpan } className="cb-admin-table__empty">
										{ __( 'No listings found.', 'cb-listing-anything' ) }
									</td>
								</tr>
							) }
							{ rows.map( ( post, index ) => {
								const categories = getListingCategories(
									post,
									categoryTaxonomy,
									categoryTermsById,
									categoryRestBase
								);
								const thumbSrc = thumbSrcForRow( post );
								const thumbAlt = post.title?.rendered
									? sprintf(
											/* translators: %s: listing title */
											__( 'Featured image for %s', 'cb-listing-anything' ),
											stripTags( post.title.rendered )
									  )
									: __( 'Featured image', 'cb-listing-anything' );
								return (
								<tr key={ post.id }>
									<td className="cb-admin-table__col-check">
										<input
											type="checkbox"
											checked={ selectedIds.has( post.id ) }
											onChange={ () => toggleRowSelected( post.id ) }
											aria-label={ sprintf(
												/* translators: %s: listing title */
												__( 'Select “%s”', 'cb-listing-anything' ),
												post.title?.rendered
													? stripTags( post.title.rendered )
													: `#${ post.id }`
											) }
										/>
									</td>
									{ visibleColumns.num && (
										<td className="cb-admin-table__col-num">
											{ ( page - 1 ) * perPage + index + 1 }
										</td>
									) }
									{ visibleColumns.thumb && (
										<td className="cb-admin-table__col-thumb">
											<ListingThumb
												src={ thumbSrc }
												alt={ thumbAlt }
												fetchPriority="low"
											/>
										</td>
									) }
									{ visibleColumns.title && (
										<td className="cb-admin-table__col-title">
											<strong>
												{ post.title?.rendered
													? stripTags( post.title.rendered )
													: __( '(no title)', 'cb-listing-anything' ) }
											</strong>
										</td>
									) }
									{ visibleColumns.status && (
										<td className="cb-admin-table__col-status">
											<span className={ `cb-admin-badge cb-admin-badge--${ post.status }` }>{ post.status }</span>
										</td>
									) }
									{ visibleColumns.categories && (
										<td className="cb-admin-table__col-categories">
											{ categories.length === 0 ? (
												<span className="cb-admin-table__categories-empty">—</span>
											) : (
												<span className="cb-admin-table__categories-list">
													{ categories.map( ( term, ci ) => (
														<Fragment key={ term.id }>
															{ ci > 0 ? (
																<span className="cb-admin-table__categories-sep" aria-hidden="true">
																	{ ', ' }
																</span>
															) : null }
															<a
																className="cb-admin-table__category-link"
																href={ categoryTermArchiveUrl( term ) }
																target="_blank"
																rel="noopener noreferrer"
															>
																{ stripTags( term.name ) }
															</a>
														</Fragment>
													) ) }
												</span>
											) }
										</td>
									) }
									{ visibleColumns.author && (
										<td className="cb-admin-table__col-author">{ authorName( post ) }</td>
									) }
									{ visibleColumns.date && (
										<td className="cb-admin-table__col-date">{ fmtDate( post.date ) }</td>
									) }
									<td className="cb-admin-table__col-actions">
										<div className="cb-admin-table__actions-inner">
											{ post.status !== 'trash' ? (
												<>
													<a href={ editUrl( post.id ) }>{ __( 'Edit', 'cb-listing-anything' ) }</a>
													<span className="cb-admin-table__action-sep" aria-hidden="true">
														|
													</span>
													{ post.link && (
														<>
															<a href={ post.link } target="_blank" rel="noreferrer">
																{ __( 'View', 'cb-listing-anything' ) }
															</a>
															<span className="cb-admin-table__action-sep" aria-hidden="true">
																|
															</span>
														</>
													) }
													<button type="button" className="cb-admin-link-btn" onClick={ () => trashPost( post.id ) }>
														{ __( 'Trash', 'cb-listing-anything' ) }
													</button>
												</>
											) : (
												<>
													<button type="button" className="cb-admin-link-btn" onClick={ () => restorePost( post.id ) }>
														{ __( 'Restore', 'cb-listing-anything' ) }
													</button>
													<span className="cb-admin-table__action-sep" aria-hidden="true">
														|
													</span>
													<button type="button" className="cb-admin-link-btn" onClick={ () => deletePermanent( post.id ) }>
														{ __( 'Delete permanently', 'cb-listing-anything' ) }
													</button>
												</>
											) }
										</div>
									</td>
								</tr>
								);
							} ) }
						</tbody>
			</AdminDataTable>

			<AdminTablePagination
				page={ page }
				totalPages={ totalPages }
				perPage={ perPage }
				onPerPageChange={ ( n ) => {
					setPerPage( n );
					setPage( 1 );
				} }
				onPrev={ () => setPage( ( p ) => Math.max( 1, p - 1 ) ) }
				onNext={ () =>
					setPage( ( p ) => Math.min( Math.max( 1, totalPages || 1 ), p + 1 ) )
				}
			/>
		</div>
	);
}
