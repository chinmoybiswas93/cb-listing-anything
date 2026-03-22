/**
 * Toolbar row: left cluster (tabs + bulk) and right slot (e.g. search).
 *
 * @param {Object} props
 * @param {import('react').ReactNode} props.start Left column content.
 * @param {import('react').ReactNode} [props.end] Right column content.
 */
export default function AdminListToolbarRow( { start, end } ) {
	return (
		<div className="cb-admin-list__toolbar-row">
			<div className="cb-admin-list__toolbar-row-start">{ start }</div>
			{ end }
		</div>
	);
}
