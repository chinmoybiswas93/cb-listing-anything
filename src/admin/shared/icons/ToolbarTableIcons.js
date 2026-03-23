/**
 * Toolbar icons for admin data tables (sort + columns).
 */

export function IconSort() {
	return (
		<svg
			className="cb-admin-toolbar-icon"
			width="18"
			height="18"
			viewBox="0 0 24 24"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
			aria-hidden="true"
		>
			<path
				d="M8 7l4-4 4 4M8 17l4 4 4-4"
				stroke="currentColor"
				strokeWidth="2"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
		</svg>
	);
}

export function IconColumns() {
	return (
		<svg
			className="cb-admin-toolbar-icon"
			width="18"
			height="18"
			viewBox="0 0 24 24"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
			aria-hidden="true"
		>
			<rect
				x="3"
				y="4"
				width="8"
				height="16"
				rx="1.5"
				stroke="currentColor"
				strokeWidth="2"
			/>
			<rect
				x="13"
				y="4"
				width="8"
				height="10"
				rx="1.5"
				stroke="currentColor"
				strokeWidth="2"
			/>
			<rect
				x="13"
				y="16"
				width="8"
				height="4"
				rx="1"
				stroke="currentColor"
				strokeWidth="2"
			/>
		</svg>
	);
}
