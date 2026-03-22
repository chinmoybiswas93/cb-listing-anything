import {
	createContext,
	useContext,
	useState,
	useCallback,
	useRef,
	useEffect,
	useMemo,
} from '@wordpress/element';
import AdminToastStack from '../components/AdminToastStack';

const ToastContext = createContext( null );

const TOAST_MS = 5000;

/**
 * App-wide toast stack (bottom-right portal). Wrap the admin app root with this provider.
 *
 * @param {{ children: import('react').ReactNode }} props
 */
export function ToastProvider( { children } ) {
	const [ toasts, setToasts ] = useState( [] );
	const toastIdRef = useRef( 0 );
	const toastTimeoutsRef = useRef( new Map() );

	const removeToast = useCallback( ( id ) => {
		const t = toastTimeoutsRef.current.get( id );
		if ( t ) {
			window.clearTimeout( t );
			toastTimeoutsRef.current.delete( id );
		}
		setToasts( ( prev ) => prev.filter( ( x ) => x.id !== id ) );
	}, [] );

	const showToast = useCallback(
		( message, status = 'success' ) => {
			const id = ++toastIdRef.current;
			setToasts( ( prev ) => [ ...prev, { id, message, status } ] );
			const timer = window.setTimeout( () => {
				removeToast( id );
			}, TOAST_MS );
			toastTimeoutsRef.current.set( id, timer );
		},
		[ removeToast ]
	);

	useEffect( () => {
		return () => {
			toastTimeoutsRef.current.forEach( ( t ) => window.clearTimeout( t ) );
			toastTimeoutsRef.current.clear();
		};
	}, [] );

	const value = useMemo( () => ( { showToast } ), [ showToast ] );

	return (
		<ToastContext.Provider value={ value }>
			{ children }
			<AdminToastStack toasts={ toasts } onDismiss={ removeToast } />
		</ToastContext.Provider>
	);
}

/**
 * @returns {{ showToast: (message: string, status?: 'success'|'error'|'warning'|'info') => void }}
 */
export function useToast() {
	const ctx = useContext( ToastContext );
	if ( ! ctx ) {
		throw new Error( 'useToast must be used within ToastProvider' );
	}
	return ctx;
}
