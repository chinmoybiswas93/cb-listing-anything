import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useClickOutside } from '../../hooks/useClickOutside';
import { IconColumns, IconSort } from '../icons/ToolbarTableIcons';

/**
 * Sort + column visibility toolbar (shared by listings + taxonomy tables).
 *
 * @param {Object} props
 * @param {string} props.sortRadioGroupName        `name` on sort radios (unique per table).
 * @param {Array<{ value: string, label: string, disabled?: boolean }>} props.sortOptions
 * @param {string} props.orderby
 * @param {string} props.order
 * @param {(orderby: string, order: string) => void} props.onSortApply
 * @param {(draftOrderby: string, draftOrder: string) => [string, string]} [props.normalizeSortBeforeApply] Optional transform before calling onSortApply.
 * @param {string[]} props.columnKeys
 * @param {(key: string) => string} props.columnLabel
 * @param {Record<string, boolean>} props.visibleColumns
 * @param {(cols: Record<string, boolean>) => void} props.onColumnsApply
 * @param {() => Record<string, boolean>} props.getDefaultColumns For Reset.
 */
export default function TableToolbarExtras( {
	sortRadioGroupName,
	sortOptions,
	orderby,
	order,
	onSortApply,
	normalizeSortBeforeApply,
	columnKeys,
	columnLabel,
	visibleColumns,
	onColumnsApply,
	getDefaultColumns,
} ) {
	const [ sortOpen, setSortOpen ] = useState( false );
	const [ colOpen, setColOpen ] = useState( false );
	const sortWrapRef = useRef( null );
	const colWrapRef = useRef( null );

	const [ draftOrderby, setDraftOrderby ] = useState( orderby );
	const [ draftOrder, setDraftOrder ] = useState( order );
	const [ draftCols, setDraftCols ] = useState( () => ( { ...visibleColumns } ) );

	useEffect( () => {
		if ( sortOpen ) {
			setDraftOrderby( orderby );
			setDraftOrder( order );
		}
	}, [ sortOpen, orderby, order ] );

	useEffect( () => {
		if ( colOpen ) {
			setDraftCols( { ...visibleColumns } );
		}
	}, [ colOpen, visibleColumns ] );

	useClickOutside( sortWrapRef, sortOpen, () => setSortOpen( false ) );
	useClickOutside( colWrapRef, colOpen, () => setColOpen( false ) );

	useEffect( () => {
		if ( ! sortOpen && ! colOpen ) {
			return;
		}
		function onKey( e ) {
			if ( e.key === 'Escape' ) {
				setSortOpen( false );
				setColOpen( false );
			}
		}
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ sortOpen, colOpen ] );

	const openSort = useCallback( () => {
		setColOpen( false );
		setSortOpen( ( v ) => ! v );
	}, [] );

	const openCols = useCallback( () => {
		setSortOpen( false );
		setColOpen( ( v ) => ! v );
	}, [] );

	const applySort = () => {
		let ob = draftOrderby;
		let ord = draftOrder;
		if ( typeof normalizeSortBeforeApply === 'function' ) {
			[ ob, ord ] = normalizeSortBeforeApply( ob, ord );
		}
		onSortApply( ob, ord );
		setSortOpen( false );
	};

	const applyCols = () => {
		const count = columnKeys.filter( ( k ) => draftCols[ k ] ).length;
		if ( count === 0 ) {
			return;
		}
		onColumnsApply( { ...draftCols } );
		setColOpen( false );
	};

	const resetCols = () => {
		setDraftCols( getDefaultColumns() );
	};

	const toggleCol = ( key ) => {
		setDraftCols( ( prev ) => ( { ...prev, [ key ]: ! prev[ key ] } ) );
	};

	return (
		<div className="cb-admin-list__toolbar-tools">
			<div className="cb-admin-toolbar-tool" ref={ sortWrapRef }>
				<button
					type="button"
					className={ `cb-admin-toolbar-icon-btn${ sortOpen ? ' is-active' : '' }` }
					onClick={ openSort }
					aria-expanded={ sortOpen }
					aria-haspopup="dialog"
					title={ __( 'Sort', 'cb-listing-anything' ) }
				>
					<IconSort />
				</button>
				{ sortOpen && (
					<div
						className="cb-admin-toolbar-popover"
						role="dialog"
						aria-label={ __( 'Sort by', 'cb-listing-anything' ) }
					>
						<div className="cb-admin-toolbar-popover__title">
							{ __( 'Sort by', 'cb-listing-anything' ) }
						</div>
						<ul className="cb-admin-toolbar-popover__radios">
							{ sortOptions.map( ( opt ) => (
								<li key={ opt.value }>
									<label
										className={
											opt.disabled
												? 'cb-admin-toolbar-popover__radio is-disabled'
												: 'cb-admin-toolbar-popover__radio'
										}
									>
										<input
											type="radio"
											name={ sortRadioGroupName }
											value={ opt.value }
											checked={ draftOrderby === opt.value }
											disabled={ !! opt.disabled }
											onChange={ () => setDraftOrderby( opt.value ) }
										/>
										<span>{ opt.label }</span>
									</label>
								</li>
							) ) }
						</ul>
						<div className="cb-admin-toolbar-popover__order">
							<span className="cb-admin-toolbar-popover__order-label">
								{ __( 'Order', 'cb-listing-anything' ) }
							</span>
							<div className="cb-admin-toolbar-popover__order-btns">
								<button
									type="button"
									className={
										draftOrder === 'asc'
											? 'cb-admin-toolbar-popover__order-btn is-active'
											: 'cb-admin-toolbar-popover__order-btn'
									}
									onClick={ () => setDraftOrder( 'asc' ) }
								>
									{ __( 'Ascending', 'cb-listing-anything' ) }
								</button>
								<button
									type="button"
									className={
										draftOrder === 'desc'
											? 'cb-admin-toolbar-popover__order-btn is-active'
											: 'cb-admin-toolbar-popover__order-btn'
									}
									onClick={ () => setDraftOrder( 'desc' ) }
								>
									{ __( 'Descending', 'cb-listing-anything' ) }
								</button>
							</div>
						</div>
						<button
							type="button"
							className="cb-admin-toolbar-popover__apply cb-admin-app__btn cb-admin-app__btn--primary"
							onClick={ applySort }
						>
							{ __( 'Apply', 'cb-listing-anything' ) }
						</button>
					</div>
				) }
			</div>

			<div className="cb-admin-toolbar-tool" ref={ colWrapRef }>
				<button
					type="button"
					className={ `cb-admin-toolbar-icon-btn${ colOpen ? ' is-active' : '' }` }
					onClick={ openCols }
					aria-expanded={ colOpen }
					aria-haspopup="dialog"
					title={ __( 'Columns', 'cb-listing-anything' ) }
				>
					<IconColumns />
				</button>
				{ colOpen && (
					<div
						className="cb-admin-toolbar-popover"
						role="dialog"
						aria-label={ __( 'Columns', 'cb-listing-anything' ) }
					>
						<div className="cb-admin-toolbar-popover__title">
							{ __( 'Columns', 'cb-listing-anything' ) }
						</div>
						<ul className="cb-admin-toolbar-popover__checks">
							{ columnKeys.map( ( key ) => (
								<li key={ key }>
									<label className="cb-admin-toolbar-popover__check">
										<input
											type="checkbox"
											checked={ !! draftCols[ key ] }
											onChange={ () => toggleCol( key ) }
										/>
										<span>{ columnLabel( key ) }</span>
									</label>
								</li>
							) ) }
						</ul>
						<button
							type="button"
							className="cb-admin-toolbar-popover__reset"
							onClick={ resetCols }
						>
							{ __( 'Reset', 'cb-listing-anything' ) }
						</button>
						<button
							type="button"
							className="cb-admin-toolbar-popover__apply cb-admin-app__btn cb-admin-app__btn--primary"
							onClick={ applyCols }
							disabled={
								columnKeys.filter( ( k ) => draftCols[ k ] ).length === 0
							}
						>
							{ __( 'Apply', 'cb-listing-anything' ) }
						</button>
					</div>
				) }
			</div>
		</div>
	);
}
