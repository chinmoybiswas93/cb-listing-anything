import { __ } from '@wordpress/i18n';

export default function AdminLayout( { activeScreen, children } ) {
	const {
		pluginName,
		listPageUrl,
		settingsPageUrl,
		newPostUrl,
	} = window.cbListingAdmin;

	const isSettings = activeScreen === 'settings';

	return (
		<div className={ `cb-admin-app${ isSettings ? ' cb-admin-app--settings-shell' : '' }` }>
			<header className="cb-admin-app__header">
				<div className="cb-admin-app__header-inner">
					<div className="cb-admin-app__brand">
						<span className="cb-admin-app__logo" aria-hidden="true" />
						<span className="cb-admin-app__brand-text">{ pluginName }</span>
					</div>
					<nav className="cb-admin-app__nav" aria-label={ __( 'Plugin sections', 'cb-listing-anything' ) }>
						<a
							className={ `cb-admin-app__nav-link${ activeScreen === 'list' ? ' is-active' : '' }` }
							href={ listPageUrl }
						>
							{ __( 'Listings', 'cb-listing-anything' ) }
						</a>
						<a
							className={ `cb-admin-app__nav-link${ activeScreen === 'settings' ? ' is-active' : '' }` }
							href={ settingsPageUrl }
						>
							{ __( 'Settings', 'cb-listing-anything' ) }
						</a>
					</nav>
					<div className="cb-admin-app__header-actions">
						<a className="cb-admin-app__btn cb-admin-app__btn--primary" href={ newPostUrl }>
							{ __( 'Add New', 'cb-listing-anything' ) }
						</a>
					</div>
				</div>
			</header>
			<div
				className={ `cb-admin-app__body${ isSettings ? ' cb-admin-app__body--settings-flush' : '' }` }
			>
				{ children }
			</div>
		</div>
	);
}
