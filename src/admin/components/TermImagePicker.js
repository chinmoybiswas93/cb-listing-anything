import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

function thumbUrlFromMediaObject( media ) {
	if ( ! media ) {
		return '';
	}
	const sizes = media.media_details?.sizes;
	return (
		sizes?.thumbnail?.source_url ||
		sizes?.medium?.source_url ||
		media.source_url ||
		''
	);
}

/**
 * Attachment ID picker using wp.media (enqueue_media on PHP).
 *
 * @param {Object} props
 * @param {number} props.imageId Attachment ID or 0.
 * @param {function(number): void} props.onChange
 */
export default function TermImagePicker( { imageId, onChange } ) {
	const [ preview, setPreview ] = useState( '' );

	useEffect( () => {
		if ( ! imageId ) {
			setPreview( '' );
			return;
		}
		let cancelled = false;
		apiFetch( {
			path: `wp/v2/media/${ imageId }?_fields=id,source_url,media_details`,
		} )
			.then( ( m ) => {
				if ( ! cancelled ) {
					setPreview( thumbUrlFromMediaObject( m ) );
				}
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setPreview( '' );
				}
			} );
		return () => {
			cancelled = true;
		};
	}, [ imageId ] );

	const openMedia = useCallback( () => {
		if ( typeof window.wp === 'undefined' || ! window.wp.media ) {
			return;
		}
		const frame = window.wp.media( {
			title: __( 'Select category image', 'cb-listing-anything' ),
			library: { type: 'image' },
			multiple: false,
			button: { text: __( 'Use image', 'cb-listing-anything' ) },
		} );
		frame.on( 'select', () => {
			const att = frame.state().get( 'selection' ).first().toJSON();
			onChange( att.id ? parseInt( att.id, 10 ) : 0 );
		} );
		frame.open();
	}, [ onChange ] );

	return (
		<div className="cb-admin-term-image-picker">
			{ preview ? (
				<div className="cb-admin-term-image-picker__preview">
					<img src={ preview } alt="" />
				</div>
			) : null }
			<p className="cb-admin-term-image-picker__actions">
				<button type="button" className="cb-admin-app__btn cb-admin-app__btn--ghost" onClick={ openMedia }>
					{ __( 'Select image', 'cb-listing-anything' ) }
				</button>
				{ imageId > 0 ? (
					<button
						type="button"
						className="cb-admin-app__btn cb-admin-app__btn--ghost"
						onClick={ () => onChange( 0 ) }
					>
						{ __( 'Remove', 'cb-listing-anything' ) }
					</button>
				) : null }
			</p>
		</div>
	);
}
