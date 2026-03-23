import { useState, useCallback, RawHTML } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import TermEditModal from '../components/TermEditModal';
import { useToast } from '../context/ToastContext';
import { useConfirmDialog } from '../context/ConfirmDialogContext';
import {
	AdminListToolbarRow,
	AdminBulkBar,
	AdminDataTable,
	AdminTablePagination,
} from '../components/admin-list';
import TaxonomyTableToolbarExtras from '../components/TaxonomyTableToolbarExtras';
import { useWpTermCollection } from '../taxonomies/useWpTermCollection';

const SKELETON_ROW_COUNT = 10;

export default function TagsScreen() {
	const { tagRestBase } = window.cbListingAdmin;
	const { showToast } = useToast();
	const { showConfirm } = useConfirmDialog();

	const {
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
		colgroupWidths,
		emptyColSpan,
		load,
		handleSortApply,
		handleColumnsApply,
		toggleSelectAll,
		toggleRowSelected,
		onSearchSubmit,
	} = useWpTermCollection( {
		restBase: tagRestBase,
		variant: 'tags',
		loadErrorMessage: __( 'Could not load tags.', 'cb-listing-anything' ),
	} );

	const [ formName, setFormName ] = useState( '' );
	const [ formSlug, setFormSlug ] = useState( '' );
	const [ formDesc, setFormDesc ] = useState( '' );
	const [ formBusy, setFormBusy ] = useState( false );
	const [ editTermId, setEditTermId ] = useState( null );

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
								<div className="cb-admin-list__toolbar-end">
									<p className="cb-admin-list__count">
										{ sprintf(
											/* translators: %d: number of tags */
											__( '%d items', 'cb-listing-anything' ),
											totalItems
										) }
									</p>
									<TaxonomyTableToolbarExtras
										variant="tags"
										orderby={ orderby }
										order={ order }
										onSortApply={ handleSortApply }
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
								<col key={ i } style={ { width: c.width } } />
							) ) }
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
								{ visibleColumns.name && (
									<th scope="col">{ __( 'Name', 'cb-listing-anything' ) }</th>
								) }
								{ visibleColumns.description && (
									<th className="cb-admin-table__col-description" scope="col">
										{ __( 'Description', 'cb-listing-anything' ) }
									</th>
								) }
								{ visibleColumns.slug && (
									<th scope="col">{ __( 'Slug', 'cb-listing-anything' ) }</th>
								) }
								{ visibleColumns.count && (
									<th scope="col">{ __( 'Count', 'cb-listing-anything' ) }</th>
								) }
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
										{ visibleColumns.name && (
											<td><span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--title" /></td>
										) }
										{ visibleColumns.description && (
											<td>
												<span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--desc" />
											</td>
										) }
										{ visibleColumns.slug && (
											<td><span className="cb-admin-table__skeleton-line" /></td>
										) }
										{ visibleColumns.count && (
											<td><span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--num" /></td>
										) }
										<td><span className="cb-admin-table__skeleton-line cb-admin-table__skeleton-line--actions" /></td>
									</tr>
								) ) }
							{ ! loading && rows.length === 0 && (
								<tr>
									<td colSpan={ emptyColSpan } className="cb-admin-table__empty">
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
										{ visibleColumns.name && (
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
										) }
										{ visibleColumns.description && (
											<td className="cb-admin-table__col-description">
												{ term.description ? (
													<div className="cb-admin-table__term-description">
														<RawHTML>{ term.description }</RawHTML>
													</div>
												) : (
													<span className="cb-admin-table__empty-cell" aria-hidden="true">
														—
													</span>
												) }
											</td>
										) }
										{ visibleColumns.slug && <td>{ term.slug }</td> }
										{ visibleColumns.count && <td>{ term.count }</td> }
										<td className="cb-admin-table__col-actions">
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
							setPage( ( p ) =>
								Math.min( Math.max( 1, totalPages || 1 ), p + 1 )
							)
						}
					/>
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
