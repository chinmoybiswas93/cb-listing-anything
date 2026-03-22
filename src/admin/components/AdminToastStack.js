import {
	useState,
	useCallback,
	useEffect,
	useRef,
	createPortal,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * @param {Object} props
 * @param {'success'|'error'|'warning'|'info'} props.status
 */
function ToastIcon( { status } ) {
	const svgProps = {
		className: 'cb-admin-toast__icon-svg',
		width: 22,
		height: 22,
		viewBox: '0 0 24 24',
		fill: 'none',
		xmlns: 'http://www.w3.org/2000/svg',
		'aria-hidden': 'true',
	};

	if ( status === 'error' ) {
		return (
			<span className="cb-admin-toast__icon cb-admin-toast__icon--error">
				<svg { ...svgProps }>
					<circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.75" />
					<path
						d="M12 7v6M12 16h.01"
						stroke="currentColor"
						strokeWidth="1.75"
						strokeLinecap="round"
					/>
				</svg>
			</span>
		);
	}

	if ( status === 'warning' ) {
		return (
			<span className="cb-admin-toast__icon cb-admin-toast__icon--warning">
				<svg { ...svgProps }>
					<path
						d="M12 4l8 14H4l8-14z"
						stroke="currentColor"
						strokeWidth="1.5"
						strokeLinejoin="round"
					/>
					<path d="M12 9v4" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" />
					<circle cx="12" cy="17" r="1" fill="currentColor" />
				</svg>
			</span>
		);
	}

	if ( status === 'info' ) {
		return (
			<span className="cb-admin-toast__icon cb-admin-toast__icon--info">
				<svg { ...svgProps }>
					<circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.75" />
					<path d="M12 10v6M12 8h.01" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" />
				</svg>
			</span>
		);
	}

	return (
		<span className="cb-admin-toast__icon cb-admin-toast__icon--success">
			<svg { ...svgProps }>
				<circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.75" />
				<path
					d="M8 12l2.5 2.5L16 9"
					stroke="currentColor"
					strokeWidth="1.75"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		</span>
	);
}

/**
 * @param {Object} props
 * @param {{ id: number|string, message: string, status?: string }} props.toast
 * @param {(id: number|string) => void} props.onDismiss
 */
function ToastItem( { toast, onDismiss } ) {
	const status = toast.status || 'success';
	const [ exiting, setExiting ] = useState( false );
	const doneRef = useRef( false );

	const finish = useCallback( () => {
		if ( doneRef.current ) {
			return;
		}
		doneRef.current = true;
		onDismiss( toast.id );
	}, [ onDismiss, toast.id ] );

	const requestDismiss = useCallback( () => {
		setExiting( true );
	}, [] );

	useEffect( () => {
		if ( ! exiting ) {
			return;
		}
		const t = window.setTimeout( finish, 400 );
		return () => window.clearTimeout( t );
	}, [ exiting, finish ] );

	const handleAnimationEnd = useCallback(
		( event ) => {
			if ( event.animationName !== 'cb-toast-out' ) {
				return;
			}
			finish();
		},
		[ finish ]
	);

	return (
		<div
			className={ `cb-admin-toast cb-admin-toast--${ status }${ exiting ? ' cb-admin-toast--exiting' : '' }` }
			onAnimationEnd={ handleAnimationEnd }
		>
			<ToastIcon status={ status } />
			<span className="cb-admin-toast__message">{ toast.message }</span>
			<button
				type="button"
				className="cb-admin-toast__dismiss"
				onClick={ requestDismiss }
				aria-label={ __( 'Dismiss notice', 'cb-listing-anything' ) }
			>
				×
			</button>
		</div>
	);
}

/**
 * Fixed bottom-right toast stack; newest toasts appear above older ones.
 *
 * @param {Object}   props
 * @param {Array<{id: number|string, message: string, status: 'success'|'error'|'warning'|'info'}>} props.toasts
 * @param {(id: number|string) => void} props.onDismiss
 */
export default function AdminToastStack( { toasts, onDismiss } ) {
	if ( ! toasts.length ) {
		return null;
	}

	const stack = (
		<div
			className="cb-admin-toast-stack"
			role="status"
			aria-live="polite"
			aria-relevant="additions text"
		>
			{ toasts.map( ( t ) => (
				<ToastItem key={ t.id } toast={ t } onDismiss={ onDismiss } />
			) ) }
		</div>
	);

	return createPortal( stack, document.body );
}
