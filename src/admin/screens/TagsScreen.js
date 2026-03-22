import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import TermEditModal from '../components/TermEditModal';
import { useToast } from '../context/ToastContext';
import { useConfirmDialog } from '../context/ConfirmDialogContext';
import {
	AdminListToolbarRow,
	AdminBulkBar,
	AdminDataTable,
} from '../components/admin-list';

const PER_PAGE = 20;
const SKELETON_ROW_COUNT = 10;

export default function TagsScreen() {
	const { tagRestBase } = window.cbListingAdmin;
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
	const [ formName, setFormName ] = useState( '' );
	const [ formSlug, setFormSlug ] = useState( '' );
	const [ formDesc, setFormDesc ] = useState( '' );
	const [ formBusy, setFormBusy ] = useState( false );
	const [ editTermId, setEditTermId ] = useState( null );
	const selectAllRef = useRef( null );

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
		return `wp/v2/${ tagRestBase }?${ params.toString() }`;
	}, [ tagRestBase, page, activeSearch ] );

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
				e.message || __( 'Could not load tags.', 'cb-listing-anything' ),
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
			title: __( 'Delete tags?', 'cb-listing-anything' ),
			message: sprintf(
				_n(
					'You are about to delete %d tag permanently. This cannot be undone.',
					'You are about to delete %d tags permanently. This cannot be undone.',
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
						path: `wp/v2/${ tagRestBase }/${ id }?force=true`,
						method: 'DELETE',
					} )
				)
			);
			setSelectedIds( new Set() );
			setBulkAction( '' );
			await load();
			showToast(
				sprintf(
					_n(
						'%d tag deleted.',
						'%d tags deleted.',
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
			};
			if ( formSlug.trim() ) {
				data.slug = formSlug.trim();
			}
			await apiFetch( {
				path: `wp/v2/${ tagRestBase }`,
				method: 'POST',
				data,
			} );
			setFormName( '' );
			setFormSlug( '' );
			setFormDesc( '' );
			showToast( __( 'Tag added.', 'cb-listing-anything' ), 'success' );
			await load();
		} catch ( e ) {
			showToast(
				e.message || __( 'Could not add tag.', 'cb-listing-anything' ),
				'error'
			);
		} finally {
			setFormBusy( false );
		}
	};

	const bulkActions = [
		{ value: '', label: __( 'Bulk actions', 'cb-listing-anything' ) },
		{ value: 'delete', label: __( 'Delete', 'cb-listing-anything' ) },
	];

	const openEditModal = useCallback( ( termId ) => {
		setEditTermId( termId );
	}, [] );

	const deleteTag = async ( term ) => {
		const ok = await showConfirm( {
			title: __( 'Delete tag?', 'cb-listing-anything' ),
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
				path: `wp/v2/${ tagRestBase }/${ term.id }?force=true`,
				method: 'DELETE',
			} );
			showToast(
				sprintf(
					/* translators: %s: tag name */
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
		} catch ( e ) {
			showToast(
				e.message || __( 'Could not delete tag.', 'cb-listing-anything' ),
				'error'
			);
		}
	};

	return (
		<div className="cb-admin-list cb-admin-tax-layout">
			<div className="cb-admin-tax-layout__topbar">
				<h1 className="cb-admin-list__title cb-admin-tax-layout__page-title">
					{ __( 'Tags', 'cb-listing-anything' ) }
				</h1>
				<form className="cb-admin-list__search" onSubmit={ onSearchSubmit }>
					<input
						type="search"
						className="cb-admin-list__search-input"
						placeholder={ __( 'Search tags…', 'cb-listing-anything' ) }
						value={ searchInput }
						onChange={ ( e ) => setSearchInput( e.target.value ) }
					/>
					<button type="submit" className="cb-admin-app__btn cb-admin-app__btn--ghost">
						{ __( 'Search tags', 'cb-listing-anything' ) }
					</button>
				</form>
			</div>

			<div className="cb-admin-tax-layout__split">
				<div className="cb-admin-tax-layout__form">
					<h2 className="cb-admin-tax-layout__panel-title">
						{ __( 'Add tag', 'cb-listing-anything' ) }
					</h2>
					<form className="cb-admin-tax-form" onSubmit={ onAddSubmit }>
						<p className="cb-admin-tax-form__field">
							<label htmlFor="cb-tag-name">{ __( 'Name', 'cb-listing-anything' ) }</label>
							<input
								id="cb-tag-name"
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
							<label htmlFor="cb-tag-slug">{ __( 'Slug', 'cb-listing-anything' ) }</label>
							<input
								id="cb-tag-slug"
								type="text"
								className="cb-admin-tax-form__input"
								value={ formSlug }
								onChange={ ( e ) => setFormSlug( e.target.value ) }
							/>
							<span className="cb-admin-tax-form__help">
								{ __(
									'The “slug” is the URL-friendly version of the name.',
									'cb-listing-anything'
								) }
							</span>
						</p>
						<p className="cb-admin-tax-form__field">
							<label htmlFor="cb-tag-desc">{ __( 'Description', 'cb-listing-anything' ) }</label>
							<textarea
								id="cb-tag-desc"
								className="cb-admin-tax-form__textarea"
								rows={ 4 }
								value={ formDesc }
								onChange={ ( e ) => setFormDesc( e.target.value ) }
							/>
						</p>
						<p className="cb-admin-tax-form__submit">
							<button
								type="submit"
								className="cb-admin-app__btn cb-admin-app__btn--primary"
								disabled={ formBusy }
							>
								{ __( 'Add tag', 'cb-listing-anything' ) }
							</button>
						</p>
					</form>
				</div>

				<div className="cb-admin-tax-layout__column">
					<div className="cb-admin-list__toolbar cb-admin-tax-layout__toolbar">
						<AdminListToolbarRow
							start={
								<AdminBulkBar
									selectId="cb-tags-bulk"
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
										/* translators: %d: number of tags */
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
							<col style={ { width: '34%' } } />
							<col style={ { width: '28%' } } />
							<col style={ { width: '12%' } } />
							<col style={ { width: '22%' } } />
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
										key={ `cb-tag-sk-${ i }` }
										className="cb-admin-table__row--skeleton"
										aria-hidden="true"
									>
										<td><span className="cb-admin-table__skeleton-box cb-admin-table__skeleton-box--check" /></td>
										<td><span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--title" /></td>
										<td><span className="cb-admin-table__skeleton-line" /></td>
										<td><span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--num" /></td>
										<td><span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--actions" /></td>
									</tr>
								) ) }
							{ ! loading && rows.length === 0 && (
								<tr>
									<td colSpan={ 5 } className="cb-admin-table__empty">
										{ __( 'No tags found.', 'cb-listing-anything' ) }
									</td>
								</tr>
							) }
							{ rows.map( ( term ) => (
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
													onClick={ () => deleteTag( term ) }
												>
													{ __( 'Delete', 'cb-listing-anything' ) }
												</button>
											</span>
										</td>
									</tr>
								) ) }
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
				onSaved={ load }
				restBase={ tagRestBase }
				variant="tag"
			/>
		</div>
	);
}
