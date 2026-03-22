/**
 * WordPress admin React shell (listings table + settings).
 */
import { createRoot } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import App from './App';
import { ToastProvider } from './context/ToastContext';
import { ConfirmDialogProvider } from './context/ConfirmDialogContext';
import './style.scss';

function boot() {
	const el = document.getElementById( 'cb-listing-admin-root' );
	if ( ! el || typeof window.cbListingAdmin === 'undefined' ) {
		return;
	}

	const { nonce, restUrl } = window.cbListingAdmin;
	const rootUrl = restUrl.endsWith( '/' ) ? restUrl : `${ restUrl }/`;
	apiFetch.use( apiFetch.createNonceMiddleware( nonce ) );
	apiFetch.use( apiFetch.createRootURLMiddleware( rootUrl ) );

	const root = createRoot( el );
	root.render(
		<ToastProvider>
			<ConfirmDialogProvider>
				<App
					screen={ el.dataset.screen || 'list' }
					initialTab={ el.dataset.tab || 'general' }
				/>
			</ConfirmDialogProvider>
		</ToastProvider>
	);
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', boot );
} else {
	boot();
}
