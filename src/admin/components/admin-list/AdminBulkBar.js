import { __, sprintf } from '@wordpress/i18n';

/**
 * Bulk actions select + Apply + optional “N selected” text.
 *
 * @param {Object} props
 * @param {string} props.selectId HTML id for the select (unique per screen).
 * @param {string} props.bulkAction Current value.
 * @param {function(string): void} props.onBulkActionChange
 * @param {function(): void} props.onApply
 * @param {boolean} props.bulkBusy
 * @param {number} props.selectedCount
 * @param {boolean} props.disableSelect When no selection or busy.
 * @param {boolean} props.disableApply When no selection, no action, or busy.
 * @param {{ value: string, label: string }[]} props.actions Options (include empty first option).
 */
export default function AdminBulkBar( {
	selectId,
	bulkAction,
	onBulkActionChange,
	onApply,
	bulkBusy,
	selectedCount,
	disableSelect,
	disableApply,
	actions,
} ) {
	return (
		<div className="cb-admin-list__bulk-actions">
			<label htmlFor={ selectId } className="screen-reader-text">
				{ __( 'Bulk actions', 'cb-listing-anything' ) }
			</label>
			<select
				id={ selectId }
				className="cb-admin-list__bulk-select"
				value={ bulkAction }
				disabled={ bulkBusy || disableSelect }
				onChange={ ( e ) => onBulkActionChange( e.target.value ) }
			>
				{ actions.map( ( a, i ) => (
					<option key={ `${ selectId }-opt-${ i }` } value={ a.value }>
						{ a.label }
					</option>
				) ) }
			</select>
			<button
				type="button"
				className="cb-admin-app__btn cb-admin-app__btn--ghost"
				disabled={ bulkBusy || disableApply }
				onClick={ onApply }
			>
				{ __( 'Apply', 'cb-listing-anything' ) }
			</button>
			{ selectedCount > 0 && (
				<span className="cb-admin-list__toolbar-selected" aria-live="polite">
					{ sprintf(
						/* translators: %d: number of selected rows */
						__( '%d selected', 'cb-listing-anything' ),
						selectedCount
					) }
				</span>
			) }
		</div>
	);
}
