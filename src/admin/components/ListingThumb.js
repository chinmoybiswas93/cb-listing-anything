/**
 * Featured image cell: skeleton while batch URL pending or image decoding; optional deferred src.
 */
import { useState, useEffect } from '@wordpress/element';

/**
 * @param {Object} props
 * @param {string|null} props.src - `null` = batch still loading; `''` = no image; URL = load image
 * @param {string} props.alt
 * @param {'low'|'high'} [props.fetchPriority]
 */
export default function ListingThumb( { src, alt, fetchPriority = 'low' } ) {
	const [ displaySrc, setDisplaySrc ] = useState( null );
	const [ loaded, setLoaded ] = useState( false );
	const [ error, setError ] = useState( false );

	useEffect( () => {
		setError( false );
		setLoaded( false );

		if ( src === null ) {
			setDisplaySrc( null );
			return;
		}

		if ( ! src ) {
			setDisplaySrc( null );
			return;
		}

		let cancelled = false;
		let idleId = 0;

		const applySrc = () => {
			if ( ! cancelled ) {
				setDisplaySrc( src );
			}
		};

		const raf = window.requestAnimationFrame( () => {
			if ( typeof window.requestIdleCallback === 'function' ) {
				idleId = window.requestIdleCallback( applySrc, { timeout: 200 } );
			} else {
				idleId = window.setTimeout( applySrc, 0 );
			}
		} );

		return () => {
			cancelled = true;
			window.cancelAnimationFrame( raf );
			if ( typeof window.cancelIdleCallback === 'function' && idleId ) {
				window.cancelIdleCallback( idleId );
			} else {
				window.clearTimeout( idleId );
			}
		};
	}, [ src ] );

	if ( src === null ) {
		return (
			<span
				className="cb-admin-table__thumb-skeleton"
				aria-hidden="true"
			/>
		);
	}

	if ( src === '' || error ) {
		return (
			<span
				className="cb-admin-table__thumb-placeholder"
				aria-hidden="true"
			/>
		);
	}

	const showSkeleton = ! loaded && Boolean( src );

	return (
		<span className="cb-admin-table__thumb-wrap">
			{ showSkeleton && (
				<span className="cb-admin-table__thumb-skeleton" aria-hidden="true" />
			) }
			{ displaySrc && (
				<img
					className={ `cb-admin-table__thumb-img${ loaded ? ' is-loaded' : '' }` }
					src={ displaySrc }
					alt={ alt }
					loading="lazy"
					decoding="async"
					width="48"
					height="48"
					fetchPriority={ fetchPriority }
					onLoad={ () => setLoaded( true ) }
					onError={ () => {
						setError( true );
						setLoaded( false );
					} }
				/>
			) }
		</span>
	);
}
