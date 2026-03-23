/**
 * Outline icons for settings sidebar (24×24 viewBox).
 */

const svgProps = {
	xmlns: 'http://www.w3.org/2000/svg',
	width: 22,
	height: 22,
	viewBox: '0 0 24 24',
	fill: 'none',
	'aria-hidden': 'true',
};

/** Panel toggle: narrow column + main area (collapse control). */
export function SettingsSidebarToggleIcon() {
	return (
		<svg { ...svgProps } className="cb-admin-settings__svg-icon">
			<rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" strokeWidth="1.5" />
			<path d="M9 4v16" stroke="currentColor" strokeWidth="1.5" />
		</svg>
	);
}

/** General — gear */
function IconGeneral() {
	return (
		<svg { ...svgProps } className="cb-admin-settings__svg-icon">
			<circle cx="12" cy="12" r="3" stroke="currentColor" strokeWidth="1.5" />
			<path
				d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"
				stroke="currentColor"
				strokeWidth="1.5"
				strokeLinecap="round"
			/>
		</svg>
	);
}

/** Fields — simple list lines (form fields) */
function IconFields() {
	return (
		<svg { ...svgProps } className="cb-admin-settings__svg-icon">
			<path
				d="M5 8h14M5 12h14M5 16h14"
				stroke="currentColor"
				strokeWidth="1.5"
				strokeLinecap="round"
			/>
		</svg>
	);
}

/** Display — monitor */
function IconDisplay() {
	return (
		<svg { ...svgProps } className="cb-admin-settings__svg-icon">
			<rect x="3" y="5" width="18" height="12" rx="2" stroke="currentColor" strokeWidth="1.5" />
			<path d="M8 21h8M12 17v4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
		</svg>
	);
}

/** Advanced — wrench */
function IconAdvanced() {
	return (
		<svg { ...svgProps } className="cb-admin-settings__svg-icon">
			<path
				d="M14.7 6.3a1 1 0 000 1.4l-7 7a1 1 0 01-1.4 0l-2.1-2.1a1 1 0 010-1.4l7-7a1 1 0 011.4 0l2.1 2.1z"
				stroke="currentColor"
				strokeWidth="1.5"
				strokeLinejoin="round"
			/>
			<path d="M17 11l4 4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
		</svg>
	);
}

/**
 * @param {Object} props
 * @param {'general'|'fields'|'display'|'advanced'} props.navId
 */
export function SettingsNavIcon( { navId } ) {
	switch ( navId ) {
		case 'fields':
			return <IconFields />;
		case 'display':
			return <IconDisplay />;
		case 'advanced':
			return <IconAdvanced />;
		case 'general':
		default:
			return <IconGeneral />;
	}
}
