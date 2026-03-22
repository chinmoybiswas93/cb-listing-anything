import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Notice, Spinner } from '@wordpress/components';

const PER_PAGE = 20;

const STATUS_TABS = [
	{ id: 'all', label: __( 'All', 'cb-listing-anything' ), param: null },
	{ id: 'publish', label: __( 'Published', 'cb-listing-anything' ), param: 'publish' },
	{ id: 'draft', label: __( 'Draft', 'cb-listing-anything' ), param: 'draft' },
	{ id: 'pending', label: __( 'Pending', 'cb-listing-anything' ), param: 'pending' },
	{ id: 'private', label: __( 'Private', 'cb-listing-anything' ), param: 'private' },
	{ id: 'trash', label: __( 'Trash', 'cb-listing-anything' ), param: 'trash' },
];

function stripTags( html ) {
	if ( ! html ) {
		return '';
	}
	const d = document.createElement( 'div' );
	d.innerHTML = html;
	return d.textContent || d.innerText || '';
}

/** Thumbnail URL from embedded featured media (REST _embed). */
function featuredImageSrc( post ) {
	const media = post._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ];
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

export default function ListingsScreen() {
	const { restBase, adminUrl } = window.cbListingAdmin;
	const [ statusFilter, setStatusFilter ] = useState( 'all' );
	const [ searchInput, setSearchInput ] = useState( '' );
	const [ activeSearch, setActiveSearch ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ rows, setRows ] = useState( [] );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ totalItems, setTotalItems ] = useState( 0 );
	const [ selectedIds, setSelectedIds ] = useState( () => new Set() );
	const [ bulkBusy, setBulkBusy ] = useState( false );
	const [ bulkAction, setBulkAction ] = useState( '' );
	const selectAllRef = useRef( null );

	const pathForFetch = useCallback( () => {
		const params = new URLSearchParams();
		params.set( 'context', 'edit' );
		params.set( 'per_page', String( PER_PAGE ) );
		params.set( 'page', String( page ) );
		params.set( 'orderby', 'date' );
		params.set( 'order', 'desc' );
		params.set( '_embed', '1' );
		if ( activeSearch.trim() ) {
			params.set( 'search', activeSearch.trim() );
		}
		const tab = STATUS_TABS.find( ( t ) => t.id === statusFilter );
		if ( tab && tab.param ) {
			params.set( 'status', tab.param );
		} else {
			params.set( 'status', 'any' );
		}
		return `wp/v2/${ restBase }?${ params.toString() }`;
	}, [ restBase, page, activeSearch, statusFilter ] );

	const load = useCallback( async () => {
		setLoading( true );
		setError( null );
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
			setError( e.message || __( 'Could not load listings.', 'cb-listing-anything' ) );
			setRows( [] );
		} finally {
			setLoading( false );
		}
	}, [ pathForFetch ] );

	useEffect( () => {
		load();
	}, [ load ] );

	useEffect( () => {
		setSelectedIds( new Set() );
		setBulkAction( '' );
	}, [ page, statusFilter, activeSearch ] );

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
		setError( null );
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
			setError( e.message || __( 'Bulk action failed.', 'cb-listing-anything' ) );
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
			setError( e.message || __( 'Could not trash listing.', 'cb-listing-anything' ) );
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
			setError( e.message || __( 'Could not restore listing.', 'cb-listing-anything' ) );
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
			setError( e.message || __( 'Could not delete listing.', 'cb-listing-anything' ) );
		}
	};

	const editUrl = ( id ) => {
		const base = adminUrl.endsWith( '/' ) ? adminUrl : `${ adminUrl }/`;
		return `${ base }post.php?post=${ id }&action=edit`;
	};

	const authorName = ( post ) => {
		const emb = post._embedded && post._embedded.author && post._embedded.author[ 0 ];
		if ( emb && emb.name ) {
			return emb.name;
		}
		return `#${ post.author }`;
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

	return (
		<div className="cb-admin-list">
			<div className="cb-admin-list__toolbar">
				<div className="cb-admin-list__toolbar-head">
					<h1 className="cb-admin-list__title">{ __( 'Listing list', 'cb-listing-anything' ) }</h1>
					<p className="cb-admin-list__count">
						{ sprintf(
							/* translators: %d: number of listings */
							__( '%d items', 'cb-listing-anything' ),
							totalItems
						) }
					</p>
				</div>
				<div className="cb-admin-list__toolbar-row">
					<div className="cb-admin-list__toolbar-row-start">
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
						<div className="cb-admin-list__bulk-actions">
							<label htmlFor="cb-listing-bulk-action" className="screen-reader-text">
								{ __( 'Bulk actions', 'cb-listing-anything' ) }
							</label>
							<select
								id="cb-listing-bulk-action"
								className="cb-admin-list__bulk-select"
								value={ bulkAction }
								disabled={ bulkBusy || selectedIds.size === 0 }
								onChange={ ( e ) => setBulkAction( e.target.value ) }
							>
								<option value="">
									{ __( 'Bulk actions', 'cb-listing-anything' ) }
								</option>
								<option value="trash">
									{ statusFilter === 'trash'
										? __( 'Delete permanently', 'cb-listing-anything' )
										: __( 'Trash', 'cb-listing-anything' ) }
								</option>
								<option value="publish">{ __( 'Publish', 'cb-listing-anything' ) }</option>
								<option value="draft">{ __( 'Draft', 'cb-listing-anything' ) }</option>
							</select>
							<button
								type="button"
								className="cb-admin-app__btn cb-admin-app__btn--ghost"
								disabled={
									bulkBusy || selectedIds.size === 0 || ! bulkAction
								}
								onClick={ bulkApply }
							>
								{ __( 'Apply', 'cb-listing-anything' ) }
							</button>
							{ selectedIds.size > 0 && (
								<span className="cb-admin-list__toolbar-selected" aria-live="polite">
									{ sprintf(
										/* translators: %d: number of selected rows */
										__( '%d selected', 'cb-listing-anything' ),
										selectedIds.size
									) }
								</span>
							) }
						</div>
					</div>
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
				</div>
			</div>

			{ error && (
				<Notice status="error" isDismissible onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }

			<div className="cb-admin-table-wrap">
				{ loading ? (
					<div className="cb-admin-list__spinner">
						<Spinner />
					</div>
				) : (
					<table className="cb-admin-table">
						<colgroup>
							<col style={ { width: '4%' } } />
							<col style={ { width: '3%' } } />
							<col style={ { width: '8%' } } />
							<col style={ { width: '28%' } } />
							<col style={ { width: '11%' } } />
							<col style={ { width: '12%' } } />
							<col style={ { width: '14%' } } />
							<col style={ { width: '20%' } } />
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
								<th className="cb-admin-table__col-num" scope="col">
									{ __( '#', 'cb-listing-anything' ) }
								</th>
								<th
									className="cb-admin-table__col-thumb"
									scope="col"
									title={ __( 'Featured image', 'cb-listing-anything' ) }
								>
									<span className="cb-admin-table__col-thumb-label">
										{ __( 'Image', 'cb-listing-anything' ) }
									</span>
								</th>
								<th className="cb-admin-table__col-title" scope="col">
									{ __( 'Title', 'cb-listing-anything' ) }
								</th>
								<th className="cb-admin-table__col-status" scope="col">
									{ __( 'Status', 'cb-listing-anything' ) }
								</th>
								<th className="cb-admin-table__col-author" scope="col">
									{ __( 'Author', 'cb-listing-anything' ) }
								</th>
								<th className="cb-admin-table__col-date" scope="col">
									{ __( 'Date', 'cb-listing-anything' ) }
								</th>
								<th className="cb-admin-table__col-actions" scope="col">
									{ __( 'Actions', 'cb-listing-anything' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ rows.length === 0 && (
								<tr>
									<td colSpan={ 8 } className="cb-admin-table__empty">
										{ __( 'No listings found.', 'cb-listing-anything' ) }
									</td>
								</tr>
							) }
							{ rows.map( ( post, index ) => {
								const thumbSrc = featuredImageSrc( post );
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
									<td className="cb-admin-table__col-num">
										{ ( page - 1 ) * PER_PAGE + index + 1 }
									</td>
									<td className="cb-admin-table__col-thumb">
										{ thumbSrc ? (
											<img
												className="cb-admin-table__thumb-img"
												src={ thumbSrc }
												alt={
													post.title?.rendered
														? sprintf(
																/* translators: %s: listing title */
																__( 'Featured image for %s', 'cb-listing-anything' ),
																stripTags( post.title.rendered )
														  )
														: __( 'Featured image', 'cb-listing-anything' )
												}
												loading="lazy"
												decoding="async"
												width="48"
												height="48"
											/>
										) : (
											<span
												className="cb-admin-table__thumb-placeholder"
												aria-hidden="true"
											/>
										) }
									</td>
									<td className="cb-admin-table__col-title">
										<strong>
											{ post.title?.rendered
												? stripTags( post.title.rendered )
												: __( '(no title)', 'cb-listing-anything' ) }
										</strong>
									</td>
									<td className="cb-admin-table__col-status">
										<span className={ `cb-admin-badge cb-admin-badge--${ post.status }` }>{ post.status }</span>
									</td>
									<td className="cb-admin-table__col-author">{ authorName( post ) }</td>
									<td className="cb-admin-table__col-date">{ fmtDate( post.date ) }</td>
									<td className="cb-admin-table__actions">
										<div className="cb-admin-table__actions-inner">
											{ post.status !== 'trash' ? (
												<>
													<a href={ editUrl( post.id ) }>{ __( 'Edit', 'cb-listing-anything' ) }</a>
													{ post.link && (
														<a href={ post.link } target="_blank" rel="noreferrer">
															{ __( 'View', 'cb-listing-anything' ) }
														</a>
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
					</table>
				) }
			</div>

			{ totalPages > 1 && (
				<div className="cb-admin-pagination">
					<button
						type="button"
						className="cb-admin-app__btn cb-admin-app__btn--ghost"
						disabled={ page <= 1 }
						onClick={ () => setPage( ( p ) => Math.max( 1, p - 1 ) ) }
					>
						{ __( 'Previous', 'cb-listing-anything' ) }
					</button>
					<span className="cb-admin-pagination__status">
						{ sprintf(
							/* translators: 1: current page 2: total pages */
							__( 'Page %1$d of %2$d', 'cb-listing-anything' ),
							page,
							totalPages
						) }
					</span>
					<button
						type="button"
						className="cb-admin-app__btn cb-admin-app__btn--ghost"
						disabled={ page >= totalPages }
						onClick={ () => setPage( ( p ) => Math.min( totalPages, p + 1 ) ) }
					>
						{ __( 'Next', 'cb-listing-anything' ) }
					</button>
				</div>
			) }
		</div>
	);
}
