import { __, sprintf } from '@wordpress/i18n';

/**
 * Top row: page title + optional item count (right).
 *
 * @param {Object} props
 * @param {string} props.title Page heading.
 * @param {number|null|undefined} props.itemCount When set, shows “N items”.
 */
export default function AdminListPageHeader( { title, itemCount } ) {
	return (
		<div className="cb-admin-list__toolbar-head">
			<h1 className="cb-admin-list__title">{ title }</h1>
			{ itemCount != null && (
				<p className="cb-admin-list__count">
					{ sprintf(
						/* translators: %d: number of items */
						__( '%d items', 'cb-listing-anything' ),
						itemCount
					) }
				</p>
			) }
		</div>
	);
}
