/**
 * Strip HTML tags to plain text (browser DOM).
 *
 * @param {string} html
 * @returns {string}
 */
export function stripTags( html ) {
	if ( ! html ) {
		return '';
	}
	const d = document.createElement( 'div' );
	d.innerHTML = html;
	return d.textContent || d.innerText || '';
}
