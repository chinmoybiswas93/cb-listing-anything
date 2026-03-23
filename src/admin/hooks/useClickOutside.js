import { useEffect, useRef } from '@wordpress/element';

/**
 * Invoke callback when user clicks/touches outside the given element.
 *
 * @param {import('react').RefObject<HTMLElement|null>} ref      Primary element (wrapper around trigger + popover).
 * @param {boolean}                                      enabled  When false, listener is not attached.
 * @param {() => void}                                   onOutside
 */
export function useClickOutside( ref, enabled, onOutside ) {
	const cbRef = useRef( onOutside );
	cbRef.current = onOutside;

	useEffect( () => {
		if ( ! enabled ) {
			return;
		}
		function handler( event ) {
			const el = ref.current;
			if ( ! el || el.contains( event.target ) ) {
				return;
			}
			cbRef.current();
		}
		document.addEventListener( 'mousedown', handler );
		document.addEventListener( 'touchstart', handler );
		return () => {
			document.removeEventListener( 'mousedown', handler );
			document.removeEventListener( 'touchstart', handler );
		};
	}, [ ref, enabled ] );
}
