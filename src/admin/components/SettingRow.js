/**
 * Two-column setting row: title + description (left), control (right).
 */
export default function SettingRow( { title, description, children } ) {
	return (
		<div className="cb-admin-setting-row">
			<div className="cb-admin-setting-row__main">
				<div className="cb-admin-setting-row__title">{ title }</div>
				{ description ? (
					<p className="cb-admin-setting-row__desc">{ description }</p>
				) : null }
			</div>
			<div className="cb-admin-setting-row__control">{ children }</div>
		</div>
	);
}
