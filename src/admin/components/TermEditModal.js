import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Modal, Spinner } from '@wordpress/components';
import TermImagePicker from './TermImagePicker';
import { useToast } from '../context/ToastContext';
import {
	termMetaInt,
	flattenTermsTree,
	getDescendantIds,
} from '../utils/termMeta';

/**
 * Shared edit modal for listing category or tag (REST `wp/v2/{restBase}/{id}`).
 *
 * @param {Object} props
 * @param {number|null} props.termId Open when set; closed when null.
 * @param {function(): void} props.onClose
 * @param {function(): void|Promise<void>} props.onSaved After successful save (reload lists).
 * @param {string} props.restBase e.g. `cb_listing_category` or `cb_listing_tag`
 * @param {'category'|'tag'} props.variant
 * @param {string} [props.categoryImageMetaKey] Required when variant is `category`
 * @param {Object[]} [props.parentTerms] Full term list for parent dropdown (categories only)
 */
export default function TermEditModal( {
	termId,
	onClose,
	onSaved,
	restBase,
	variant,
	categoryImageMetaKey = '',
	parentTerms = [],
} ) {
	const isCategory = variant === 'category';
	const { showToast } = useToast();

	const [ editLoading, setEditLoading ] = useState( false );
	const [ editSaving, setEditSaving ] = useState( false );
	const [ editFetchFailed, setEditFetchFailed ] = useState( false );
	const [ editName, setEditName ] = useState( '' );
	const [ editSlug, setEditSlug ] = useState( '' );
	const [ editParent, setEditParent ] = useState( 0 );
	const [ editDesc, setEditDesc ] = useState( '' );
	const [ editImageId, setEditImageId ] = useState( 0 );

	const resetForm = useCallback( () => {
		setEditFetchFailed( false );
		setEditName( '' );
		setEditSlug( '' );
		setEditParent( 0 );
		setEditDesc( '' );
		setEditImageId( 0 );
		setEditLoading( false );
		setEditSaving( false );
	}, [] );

	const closeModal = useCallback( () => {
		resetForm();
		onClose();
	}, [ onClose, resetForm ] );

	useEffect( () => {
		if ( termId == null ) {
			return;
		}
		let cancelled = false;
		( async () => {
			setEditLoading( true );
			setEditFetchFailed( false );
			try {
				const term = await apiFetch( {
					path: `wp/v2/${ restBase }/${ termId }?context=edit`,
				} );
				if ( cancelled ) {
					return;
				}
				setEditName( term.name || '' );
				setEditSlug( term.slug || '' );
				setEditParent( term.parent || 0 );
				setEditDesc( term.description || '' );
				setEditImageId(
					isCategory && categoryImageMetaKey
						? termMetaInt( term, categoryImageMetaKey )
						: 0
				);
			} catch ( e ) {
				if ( ! cancelled ) {
					const msg =
						e.message ||
						( isCategory
							? __( 'Could not load category.', 'cb-listing-anything' )
							: __( 'Could not load tag.', 'cb-listing-anything' ) );
					showToast( msg, 'error' );
					setEditFetchFailed( true );
				}
			} finally {
				if ( ! cancelled ) {
					setEditLoading( false );
				}
			}
		} )();
		return () => {
			cancelled = true;
		};
	}, [ termId, restBase, isCategory, categoryImageMetaKey, showToast ] );

	const editParentOptions = useMemo( () => {
		if ( ! isCategory || termId == null || ! parentTerms.length ) {
			return [];
		}
		const desc = getDescendantIds( parentTerms, termId );
		const exclude = new Set( [ termId, ...desc ] );
		const filtered = parentTerms.filter( ( t ) => ! exclude.has( t.id ) );
		return flattenTermsTree( filtered );
	}, [ isCategory, parentTerms, termId ] );

	const saveEdit = async ( ev ) => {
		ev.preventDefault();
		if ( ! termId || ! editName.trim() ) {
			return;
		}
		setEditSaving( true );
		try {
			const data = {
				name: editName.trim(),
				description: editDesc,
			};
			if ( editSlug.trim() ) {
				data.slug = editSlug.trim();
			}
			if ( isCategory ) {
				data.parent = editParent > 0 ? editParent : 0;
				if ( categoryImageMetaKey ) {
					data.meta = {
						[ categoryImageMetaKey ]: editImageId > 0 ? editImageId : 0,
					};
				}
			}
			await apiFetch( {
				path: `wp/v2/${ restBase }/${ termId }`,
				method: 'POST',
				data,
			} );
			closeModal();
			await Promise.resolve( onSaved() );
		} catch ( e ) {
			showToast(
				e.message ||
					( isCategory
						? __( 'Could not update category.', 'cb-listing-anything' )
						: __( 'Could not update tag.', 'cb-listing-anything' ) ),
				'error'
			);
		} finally {
			setEditSaving( false );
		}
	};

	const idPrefix = isCategory ? 'cb-cat-edit' : 'cb-tag-edit';
	const modalTitle = isCategory
		? __( 'Edit category', 'cb-listing-anything' )
		: __( 'Edit tag', 'cb-listing-anything' );

	if ( termId === null ) {
		return null;
	}

	return (
		<Modal
			className="cb-admin-modal cb-admin-modal--term"
			title={ modalTitle }
			onRequestClose={ () => {
				if ( ! editSaving ) {
					closeModal();
				}
			} }
			isDismissible={ ! editSaving }
		>
			{ editLoading ? (
				<div className="cb-admin-modal__spinner">
					<Spinner />
				</div>
			) : editFetchFailed ? (
				<>
					<div className="cb-admin-modal__actions">
						<button
							type="button"
							className="cb-admin-app__btn cb-admin-app__btn--primary"
							onClick={ closeModal }
						>
							{ __( 'Close', 'cb-listing-anything' ) }
						</button>
					</div>
				</>
			) : (
				<form className="cb-admin-tax-form" onSubmit={ saveEdit }>
					<p className="cb-admin-tax-form__field">
						<label htmlFor={ `${ idPrefix }-name` }>{ __( 'Name', 'cb-listing-anything' ) }</label>
						<input
							id={ `${ idPrefix }-name` }
							type="text"
							className="cb-admin-tax-form__input"
							value={ editName }
							onChange={ ( e ) => setEditName( e.target.value ) }
							required
						/>
					</p>
					<p className="cb-admin-tax-form__field">
						<label htmlFor={ `${ idPrefix }-slug` }>{ __( 'Slug', 'cb-listing-anything' ) }</label>
						<input
							id={ `${ idPrefix }-slug` }
							type="text"
							className="cb-admin-tax-form__input"
							value={ editSlug }
							onChange={ ( e ) => setEditSlug( e.target.value ) }
						/>
					</p>
					{ isCategory && (
						<p className="cb-admin-tax-form__field">
							<label htmlFor={ `${ idPrefix }-parent` }>
								{ __( 'Parent category', 'cb-listing-anything' ) }
							</label>
							<select
								id={ `${ idPrefix }-parent` }
								className="cb-admin-tax-form__input"
								value={ editParent }
								onChange={ ( e ) =>
									setEditParent( parseInt( e.target.value, 10 ) || 0 )
								}
							>
								<option value={ 0 }>{ __( 'None', 'cb-listing-anything' ) }</option>
								{ editParentOptions.map( ( t ) => (
									<option key={ t.id } value={ t.id }>
										{ `${ '— '.repeat( t.depth ) }${ t.name }` }
									</option>
								) ) }
							</select>
						</p>
					) }
					<p className="cb-admin-tax-form__field">
						<label htmlFor={ `${ idPrefix }-desc` }>{ __( 'Description', 'cb-listing-anything' ) }</label>
						<textarea
							id={ `${ idPrefix }-desc` }
							className="cb-admin-tax-form__textarea"
							rows={ 4 }
							value={ editDesc }
							onChange={ ( e ) => setEditDesc( e.target.value ) }
						/>
					</p>
					{ isCategory && categoryImageMetaKey && (
						<div className="cb-admin-tax-form__field">
							<span className="cb-admin-tax-form__label">{ __( 'Category image', 'cb-listing-anything' ) }</span>
							<TermImagePicker imageId={ editImageId } onChange={ setEditImageId } />
						</div>
					) }
					<div className="cb-admin-modal__actions">
						<button
							type="button"
							className="cb-admin-app__btn cb-admin-app__btn--ghost"
							disabled={ editSaving }
							onClick={ closeModal }
						>
							{ __( 'Cancel', 'cb-listing-anything' ) }
						</button>
						<button
							type="submit"
							className="cb-admin-app__btn cb-admin-app__btn--primary"
							disabled={ editSaving }
						>
							{ editSaving
								? __( 'Saving…', 'cb-listing-anything' )
								: __( 'Save', 'cb-listing-anything' ) }
						</button>
					</div>
				</form>
			) }
		</Modal>
	);
}
