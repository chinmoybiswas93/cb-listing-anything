import {
	createContext,
	useContext,
	useState,
	useCallback,
	useRef,
	useEffect,
	useMemo,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ConfirmModal from '../components/ConfirmModal';

const ConfirmDialogContext = createContext( null );

/**
 * @typedef {Object} ConfirmOptions
 * @property {string} title
 * @property {string} [message]
 * @property {string} [confirmLabel]
 * @property {string} [cancelLabel]
 * @property {boolean} [isDestructive]
 */

/**
 * App-wide confirm dialog. Wrap the admin root with this provider.
 *
 * @param {{ children: import('react').ReactNode }} props
 */
export function ConfirmDialogProvider( { children } ) {
	const [ dialog, setDialog ] = useState( null );
	const resolveRef = useRef( null );

	const finish = useCallback( ( confirmed ) => {
		setDialog( null );
		const r = resolveRef.current;
		resolveRef.current = null;
		if ( r ) {
			r( confirmed );
		}
	}, [] );

	/**
	 * @param {ConfirmOptions} options
	 * @returns {Promise<boolean>} `true` if user confirmed, `false` if cancelled.
	 */
	const showConfirm = useCallback( ( options ) => {
		return new Promise( ( resolve ) => {
			resolveRef.current = resolve;
			setDialog( {
				title: options.title,
				message: options.message ?? '',
				confirmLabel: options.confirmLabel ?? __( 'Confirm', 'cb-listing-anything' ),
				cancelLabel: options.cancelLabel ?? __( 'Cancel', 'cb-listing-anything' ),
				isDestructive: options.isDestructive ?? false,
			} );
		} );
	}, [] );

	useEffect( () => {
		return () => {
			if ( resolveRef.current ) {
				resolveRef.current( false );
				resolveRef.current = null;
			}
		};
	}, [] );

	const value = useMemo( () => ( { showConfirm } ), [ showConfirm ] );

	return (
		<ConfirmDialogContext.Provider value={ value }>
			{ children }
			<ConfirmModal
				isOpen={ dialog !== null }
				title={ dialog?.title ?? '' }
				message={ dialog?.message ?? '' }
				confirmLabel={ dialog?.confirmLabel }
				cancelLabel={ dialog?.cancelLabel }
				isDestructive={ dialog?.isDestructive }
				onConfirm={ () => finish( true ) }
				onCancel={ () => finish( false ) }
			/>
		</ConfirmDialogContext.Provider>
	);
}

/**
 * @returns {{ showConfirm: (options: ConfirmOptions) => Promise<boolean> }}
 */
export function useConfirmDialog() {
	const ctx = useContext( ConfirmDialogContext );
	if ( ! ctx ) {
		throw new Error( 'useConfirmDialog must be used within ConfirmDialogProvider' );
	}
	return ctx;
}
