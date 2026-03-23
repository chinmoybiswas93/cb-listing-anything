import { __, sprintf } from '@wordpress/i18n';
import { ADMIN_TABLE_PER_PAGE_OPTIONS } from './useAdminTablePerPage';

/**
 * Bottom bar: left = rows per page; right = prev / page status / next.
 *
 * @param {Object}   props
 * @param {number}   props.page          Current page (1-based).
 * @param {number}   props.totalPages    Total pages (at least 1 for display).
 * @param {number}   props.perPage       Items per page.
 * @param {(n: number) => void} props.onPerPageChange Called with new per_page; parent should reset page to 1.
 * @param {() => void} props.onPrev
 * @param {() => void} props.onNext
 * @param {number[]} [props.perPageOptions] Override option values (default: 10,20,50,100).
 */
export default function AdminTablePagination( {
	page,
	totalPages,
	perPage,
	onPerPageChange,
	onPrev,
	onNext,
	perPageOptions = ADMIN_TABLE_PER_PAGE_OPTIONS,
} ) {
	const pages = Math.max( 1, totalPages || 1 );
	const disabledPrev = page <= 1 || pages <= 1;
	const disabledNext = page >= pages || pages <= 1;

	return (
		<div className="cb-admin-pagination">
			<div className="cb-admin-pagination__left">
				<label className="cb-admin-pagination__per-page">
					<span className="cb-admin-pagination__per-page-label">
						{ __( 'Rows per page', 'cb-listing-anything' ) }
					</span>
					<select
						className="cb-admin-pagination__per-page-select"
						value={ String( perPage ) }
						onChange={ ( e ) => {
							onPerPageChange( parseInt( e.target.value, 10 ) );
						} }
						aria-label={ __( 'Rows per page', 'cb-listing-anything' ) }
					>
						{ perPageOptions.map( ( n ) => (
							<option key={ n } value={ String( n ) }>
								{ n }
							</option>
						) ) }
					</select>
				</label>
			</div>
			<div className="cb-admin-pagination__right">
				<button
					type="button"
					className="cb-admin-app__btn cb-admin-app__btn--ghost"
					disabled={ disabledPrev }
					onClick={ onPrev }
				>
					{ __( 'Previous', 'cb-listing-anything' ) }
				</button>
				<span className="cb-admin-pagination__status">
					{ sprintf(
						/* translators: 1: current page 2: total pages */
						__( 'Page %1$d of %2$d', 'cb-listing-anything' ),
						page,
						pages
					) }
				</span>
				<button
					type="button"
					className="cb-admin-app__btn cb-admin-app__btn--ghost"
					disabled={ disabledNext }
					onClick={ onNext }
				>
					{ __( 'Next', 'cb-listing-anything' ) }
				</button>
			</div>
		</div>
	);
}
