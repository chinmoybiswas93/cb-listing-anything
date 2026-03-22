/**
 * Shared table chrome: wrap + table element (children = colgroup, thead, tbody).
 *
 * @param {Object} props
 * @param {boolean} [props.ariaBusy]
 * @param {string} [props.className] Extra class on the outer wrap.
 * @param {import('react').ReactNode} props.children
 */
export default function AdminDataTable( { ariaBusy, className = '', children } ) {
	return (
		<div
			className={ `cb-admin-table-wrap${ className ? ` ${ className }` : '' }` }
			aria-busy={ ariaBusy }
		>
			<table className="cb-admin-table">{ children }</table>
		</div>
	);
}
