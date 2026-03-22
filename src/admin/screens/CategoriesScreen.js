import { useState, useEffect, useCallback, useRef, useMemo } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ListingThumb from '../components/ListingThumb';
import { useToast } from '../context/ToastContext';
import { useConfirmDialog } from '../context/ConfirmDialogContext';
import TermImagePicker from '../components/TermImagePicker';
import TermEditModal from '../components/TermEditModal';
import { termMetaInt, flattenTermsTree } from '../utils/termMeta';
import {
	AdminListToolbarRow,
	AdminBulkBar,
	AdminDataTable,
} from '../components/admin-list';

const PER_PAGE = 20;
const SKELETON_ROW_COUNT = 10;

function thumbUrlFromMediaObject( media ) {
	if ( ! media ) {
		return '';
	}
	const sizes = media.media_details?.sizes;
	return (
		sizes?.thumbnail?.source_url ||
		sizes?.medium?.source_url ||
		media.source_url ||
		''
	);
}

export default function CategoriesScreen() {
	const { categoryRestBase, categoryImageMetaKey } = window.cbListingAdmin;
	const { showToast } = useToast();
	const { showConfirm } = useConfirmDialog();

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
	const [ thumbUrls, setThumbUrls ] = useState( null );
	const [ parentTerms, setParentTerms ] = useState( [] );
	const [ formName, setFormName ] = useState( '' );
	const [ formSlug, setFormSlug ] = useState( '' );
	const [ formParent, setFormParent ] = useState( 0 );
	const [ formDesc, setFormDesc ] = useState( '' );
	const [ formImageId, setFormImageId ] = useState( 0 );
	const [ formBusy, setFormBusy ] = useState( false );
	const selectAllRef = useRef( null );

	const [ editTermId, setEditTermId ] = useState( null );

	const loadParentTerms = useCallback( async () => {
		try {
			const all = [];
			let p = 1;
			for ( ;; ) {
				const batch = await apiFetch( {
					path: `wp/v2/${ categoryRestBase }?context=edit&per_page=100&page=${ p }&orderby=name&order=asc`,
				} );
				if ( ! Array.isArray( batch ) || batch.length === 0 ) {
					break;
				}
				all.push( ...batch );
				if ( batch.length < 100 ) {
					break;
				}
				p++;
			}
			setParentTerms( all );
		} catch {
			setParentTerms( [] );
		}
	}, [ categoryRestBase ] );

	useEffect( () => {
		loadParentTerms();
	}, [ loadParentTerms ] );

	const pathForFetch = useCallback( () => {
		const params = new URLSearchParams();
		params.set( 'context', 'edit' );
		params.set( 'per_page', String( PER_PAGE ) );
		params.set( 'page', String( page ) );
		params.set( 'orderby', 'name' );
		params.set( 'order', 'asc' );
		if ( activeSearch.trim() ) {
			params.set( 'search', activeSearch.trim() );
		}
		return `wp/v2/${ categoryRestBase }?${ params.toString() }`;
	}, [ categoryRestBase, page, activeSearch ] );

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
			showToast(
				e.message || __( 'Could not load categories.', 'cb-listing-anything' ),
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

	useEffect( () => {
		if ( ! rows.length ) {
			setThumbUrls( {} );
			return;
		}
		const mediaIds = [
			...new Set(
				rows
					.map( ( t ) => termMetaInt( t, categoryImageMetaKey ) )
					.filter( ( id ) => id > 0 )
			),
		];
		if ( ! mediaIds.length ) {
			setThumbUrls( {} );
			return;
		}
		setThumbUrls( null );
		let cancelled = false;
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
				setThumbUrls( map );
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setThumbUrls( {} );
				}
			} );
		return () => {
			cancelled = true;
		};
	}, [ rows, categoryImageMetaKey ] );

	useEffect( () => {
		setSelectedIds( new Set() );
		setBulkAction( '' );
	}, [ page, activeSearch ] );

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
		if ( selectedIds.size === 0 || bulkAction !== 'delete' ) {
			return;
		}
		const n = selectedIds.size;
		const ok = await showConfirm( {
			title: __( 'Delete categories?', 'cb-listing-anything' ),
			message: sprintf(
				_n(
					'You are about to delete %d category permanently. This cannot be undone.',
					'You are about to delete %d categories permanently. This cannot be undone.',
					n,
					'cb-listing-anything'
				),
				n
			),
			confirmLabel: __( 'Delete', 'cb-listing-anything' ),
			isDestructive: true,
		} );
		if ( ! ok ) {
			return;
		}
		setBulkBusy( true );
		try {
			const ids = [ ...selectedIds ];
			await Promise.all(
				ids.map( ( id ) =>
					apiFetch( {
						path: `wp/v2/${ categoryRestBase }/${ id }?force=true`,
						method: 'DELETE',
					} )
				)
			);
			setSelectedIds( new Set() );
			setBulkAction( '' );
			await load();
			await loadParentTerms();
			showToast(
				sprintf(
					_n(
						'%d category deleted.',
						'%d categories deleted.',
						n,
						'cb-listing-anything'
					),
					n
				),
				'success'
			);
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

	const onAddSubmit = async ( ev ) => {
		ev.preventDefault();
		if ( ! formName.trim() ) {
			showToast( __( 'Name is required.', 'cb-listing-anything' ), 'error' );
			return;
		}
		setFormBusy( true );
		try {
			const data = {
				name: formName.trim(),
				description: formDesc,
				parent: formParent > 0 ? formParent : 0,
			};
			if ( formSlug.trim() ) {
				data.slug = formSlug.trim();
			}
			const meta = {};
			if ( formImageId > 0 ) {
				meta[ categoryImageMetaKey ] = formImageId;
			}
			if ( Object.keys( meta ).length ) {
				data.meta = meta;
			}
			await apiFetch( {
				path: `wp/v2/${ categoryRestBase }`,
				method: 'POST',
				data,
			} );
			setFormName( '' );
			setFormSlug( '' );
			setFormParent( 0 );
			setFormDesc( '' );
			setFormImageId( 0 );
			showToast( __( 'Category added.', 'cb-listing-anything' ), 'success' );
			await load();
			await loadParentTerms();
		} catch ( e ) {
			showToast(
				e.message || __( 'Could not add category.', 'cb-listing-anything' ),
				'error'
			);
		} finally {
			setFormBusy( false );
		}
	};

	const openEditModal = useCallback( ( termId ) => {
		setEditTermId( termId );
	}, [] );

	const deleteCategory = async ( term ) => {
		const ok = await showConfirm( {
			title: __( 'Delete category?', 'cb-listing-anything' ),
			message: sprintf(
				__(
					'“%s” will be permanently deleted. This cannot be undone.',
					'cb-listing-anything'
				),
				term.name
			),
			confirmLabel: __( 'Delete', 'cb-listing-anything' ),
			isDestructive: true,
		} );
		if ( ! ok ) {
			return;
		}
		try {
			await apiFetch( {
				path: `wp/v2/${ categoryRestBase }/${ term.id }?force=true`,
				method: 'DELETE',
			} );
			showToast(
				sprintf(
					/* translators: %s: category name */
					__( '“%s” deleted.', 'cb-listing-anything' ),
					term.name
				),
				'success'
			);
			setSelectedIds( ( prev ) => {
				const next = new Set( prev );
				next.delete( term.id );
				return next;
			} );
			await load();
			await loadParentTerms();
		} catch ( e ) {
			showToast(
				e.message || __( 'Could not delete category.', 'cb-listing-anything' ),
				'error'
			);
		}
	};

	const handleTermSaved = useCallback( async () => {
		await load();
		await loadParentTerms();
	}, [ load, loadParentTerms ] );

	const thumbForRow = ( term ) => {
		const id = termMetaInt( term, categoryImageMetaKey );
		if ( ! id ) {
			return '';
		}
		if ( thumbUrls === null ) {
			return null;
		}
		return thumbUrls[ id ] ?? '';
	};

	const flatParents = flattenTermsTree( parentTerms );
	const bulkActions = [
		{ value: '', label: __( 'Bulk actions', 'cb-listing-anything' ) },
		{ value: 'delete', label: __( 'Delete', 'cb-listing-anything' ) },
	];

	return (
		<div className="cb-admin-list cb-admin-tax-layout">
			<div className="cb-admin-tax-layout__topbar">
				<h1 className="cb-admin-list__title cb-admin-tax-layout__page-title">
					{ __( 'Categories', 'cb-listing-anything' ) }
				</h1>
				<form className="cb-admin-list__search" onSubmit={ onSearchSubmit }>
					<input
						type="search"
						className="cb-admin-list__search-input"
						placeholder={ __( 'Search categories…', 'cb-listing-anything' ) }
						value={ searchInput }
						onChange={ ( e ) => setSearchInput( e.target.value ) }
					/>
					<button type="submit" className="cb-admin-app__btn cb-admin-app__btn--ghost">
						{ __( 'Search categories', 'cb-listing-anything' ) }
					</button>
				</form>
			</div>

			<div className="cb-admin-tax-layout__split">
				<div className="cb-admin-tax-layout__form">
					<h2 className="cb-admin-tax-layout__panel-title">
						{ __( 'Add category', 'cb-listing-anything' ) }
					</h2>
					<form className="cb-admin-tax-form" onSubmit={ onAddSubmit }>
						<p className="cb-admin-tax-form__field">
							<label htmlFor="cb-cat-name">{ __( 'Name', 'cb-listing-anything' ) }</label>
							<input
								id="cb-cat-name"
								type="text"
								className="cb-admin-tax-form__input"
								value={ formName }
								onChange={ ( e ) => setFormName( e.target.value ) }
								required
							/>
							<span className="cb-admin-tax-form__help">
								{ __( 'The name is how it appears on your site.', 'cb-listing-anything' ) }
							</span>
						</p>
						<p className="cb-admin-tax-form__field">
							<label htmlFor="cb-cat-slug">{ __( 'Slug', 'cb-listing-anything' ) }</label>
							<input
								id="cb-cat-slug"
								type="text"
								className="cb-admin-tax-form__input"
								value={ formSlug }
								onChange={ ( e ) => setFormSlug( e.target.value ) }
							/>
							<span className="cb-admin-tax-form__help">
								{ __(
									'The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.',
									'cb-listing-anything'
								) }
							</span>
						</p>
						<p className="cb-admin-tax-form__field">
							<label htmlFor="cb-cat-parent">{ __( 'Parent category', 'cb-listing-anything' ) }</label>
							<select
								id="cb-cat-parent"
								className="cb-admin-tax-form__input"
								value={ formParent }
								onChange={ ( e ) => setFormParent( parseInt( e.target.value, 10 ) || 0 ) }
							>
								<option value={ 0 }>{ __( 'None', 'cb-listing-anything' ) }</option>
								{ flatParents.map( ( t ) => (
									<option key={ t.id } value={ t.id }>
										{ `${ '— '.repeat( t.depth ) }${ t.name }` }
									</option>
								) ) }
							</select>
							<span className="cb-admin-tax-form__help">
								{ __(
									'Categories, unlike tags, can have a hierarchy. Assign a parent to create nested categories.',
									'cb-listing-anything'
								) }
							</span>
						</p>
						<p className="cb-admin-tax-form__field">
							<label htmlFor="cb-cat-desc">{ __( 'Description', 'cb-listing-anything' ) }</label>
							<textarea
								id="cb-cat-desc"
								className="cb-admin-tax-form__textarea"
								rows={ 4 }
								value={ formDesc }
								onChange={ ( e ) => setFormDesc( e.target.value ) }
							/>
							<span className="cb-admin-tax-form__help">
								{ __( 'The description is not prominent by default; however, some themes may show it.', 'cb-listing-anything' ) }
							</span>
						</p>
						<div className="cb-admin-tax-form__field">
							<span className="cb-admin-tax-form__label">{ __( 'Category image', 'cb-listing-anything' ) }</span>
							<TermImagePicker imageId={ formImageId } onChange={ setFormImageId } />
						</div>
						<p className="cb-admin-tax-form__submit">
							<button
								type="submit"
								className="cb-admin-app__btn cb-admin-app__btn--primary"
								disabled={ formBusy }
							>
								{ __( 'Add category', 'cb-listing-anything' ) }
							</button>
						</p>
					</form>
				</div>

				<div className="cb-admin-tax-layout__column">
					<div className="cb-admin-list__toolbar cb-admin-tax-layout__toolbar">
						<AdminListToolbarRow
							start={
								<AdminBulkBar
									selectId="cb-categories-bulk"
									bulkAction={ bulkAction }
									onBulkActionChange={ setBulkAction }
									onApply={ bulkApply }
									bulkBusy={ bulkBusy }
									selectedCount={ selectedIds.size }
									disableSelect={ selectedIds.size === 0 }
									disableApply={ selectedIds.size === 0 || bulkAction !== 'delete' }
									actions={ bulkActions }
								/>
							}
							end={
								<p className="cb-admin-list__count">
									{ sprintf(
										/* translators: %d: number of categories */
										__( '%d items', 'cb-listing-anything' ),
										totalItems
									) }
								</p>
							}
						/>
					</div>

					<AdminDataTable ariaBusy={ loading }>
						<colgroup>
							<col style={ { width: '4%' } } />
							<col style={ { width: '8%' } } />
							<col style={ { width: '36%' } } />
							<col style={ { width: '22%' } } />
							<col style={ { width: '12%' } } />
							<col style={ { width: '18%' } } />
						</colgroup>
						<thead>
							<tr>
								<th className="cb-admin-table__col-check" scope="col">
									<input
										ref={ selectAllRef }
										type="checkbox"
										checked={ rows.length > 0 && selectedIds.size === rows.length }
										onChange={ toggleSelectAll }
										disabled={ rows.length === 0 }
										aria-label={ __( 'Select all', 'cb-listing-anything' ) }
									/>
								</th>
								<th className="cb-admin-table__col-thumb" scope="col">
									<span className="cb-admin-table__col-thumb-label">
										{ __( 'Image', 'cb-listing-anything' ) }
									</span>
								</th>
								<th scope="col">{ __( 'Name', 'cb-listing-anything' ) }</th>
								<th scope="col">{ __( 'Slug', 'cb-listing-anything' ) }</th>
								<th scope="col">{ __( 'Count', 'cb-listing-anything' ) }</th>
								<th className="cb-admin-table__col-actions" scope="col">
									{ __( 'Actions', 'cb-listing-anything' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ loading &&
								rows.length === 0 &&
								Array.from( { length: SKELETON_ROW_COUNT } ).map( ( _, i ) => (
									<tr
										key={ `cb-cat-sk-${ i }` }
										className="cb-admin-table__row--skeleton"
										aria-hidden="true"
									>
										<td><span className="cb-admin-table__skeleton-box cb-admin-table__skeleton-box--check" /></td>
										<td><span className="cb-admin-table__thumb-skeleton" /></td>
										<td><span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--title" /></td>
										<td><span className="cb-admin-table__skeleton-line" /></td>
										<td><span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--num" /></td>
										<td><span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--actions" /></td>
									</tr>
								) ) }
							{ ! loading && rows.length === 0 && (
								<tr>
									<td colSpan={ 6 } className="cb-admin-table__empty">
										{ __( 'No categories found.', 'cb-listing-anything' ) }
									</td>
								</tr>
							) }
							{ rows.map( ( term ) => {
								const thumbSrc = thumbForRow( term );
								return (
									<tr key={ term.id }>
										<td className="cb-admin-table__col-check">
											<input
												type="checkbox"
												checked={ selectedIds.has( term.id ) }
												onChange={ () => toggleRowSelected( term.id ) }
												aria-label={ sprintf(
													/* translators: %s: term name */
													__( 'Select “%s”', 'cb-listing-anything' ),
													term.name
												) }
											/>
										</td>
										<td className="cb-admin-table__col-thumb">
											<ListingThumb
												src={ thumbSrc }
												alt={ term.name }
												fetchPriority="low"
											/>
										</td>
										<td>
											<strong>
												<button
													type="button"
													className="cb-admin-tax-layout__name-link"
													onClick={ () => openEditModal( term.id ) }
												>
													{ term.name }
												</button>
											</strong>
										</td>
										<td>{ term.slug }</td>
										<td>{ term.count }</td>
										<td className="cb-admin-table__actions">
											<span className="cb-admin-tax-layout__action-row">
												<button
													type="button"
													className="cb-admin-tax-layout__action-edit"
													onClick={ () => openEditModal( term.id ) }
												>
													{ __( 'Edit', 'cb-listing-anything' ) }
												</button>
												<span className="cb-admin-tax-layout__action-sep" aria-hidden="true">
													|
												</span>
												<button
													type="button"
													className="cb-admin-tax-layout__action-delete"
													onClick={ () => deleteCategory( term ) }
												>
													{ __( 'Delete', 'cb-listing-anything' ) }
												</button>
											</span>
										</td>
									</tr>
								);
							} ) }
						</tbody>
					</AdminDataTable>

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
			</div>

			<TermEditModal
				termId={ editTermId }
				onClose={ () => setEditTermId( null ) }
				onSaved={ handleTermSaved }
				restBase={ categoryRestBase }
				variant="category"
				categoryImageMetaKey={ categoryImageMetaKey }
				parentTerms={ parentTerms }
			/>
		</div>
	);
}
