import { Modal } from '@wordpress/components';

/**
 * Shared confirmation dialog (destructive-safe primary action).
 *
 * @param {Object} props
 * @param {boolean} props.isOpen
 * @param {string} props.title Modal title (also used as aria-labelledby target).
 * @param {string} [props.message] Body text (plain string).
 * @param {string} [props.confirmLabel]
 * @param {string} [props.cancelLabel]
 * @param {boolean} [props.isDestructive] Style confirm button as danger (e.g. delete).
 * @param {boolean} [props.isBusy] Disable buttons (e.g. while an async action runs).
 * @param {function(): void} props.onConfirm
 * @param {function(): void} props.onCancel Close without confirming (overlay, Esc, Cancel).
 */
export default function ConfirmModal( {
	isOpen,
	title,
	message = '',
	confirmLabel,
	cancelLabel,
	isDestructive = false,
	isBusy = false,
	onConfirm,
	onCancel,
} ) {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			className="cb-admin-modal cb-admin-modal--confirm"
			title={ title }
			onRequestClose={ onCancel }
			isDismissible={ ! isBusy }
			shouldCloseOnClickOutside={ ! isBusy }
			shouldCloseOnEsc={ ! isBusy }
		>
			{ message ? (
				<p className="cb-admin-modal__confirm-message">{ message }</p>
			) : null }
			<div className="cb-admin-modal__actions">
				<button
					type="button"
					className="cb-admin-app__btn cb-admin-app__btn--ghost"
					disabled={ isBusy }
					onClick={ onCancel }
				>
					{ cancelLabel }
				</button>
				<button
					type="button"
					className={
						isDestructive
							? 'cb-admin-app__btn cb-admin-app__btn--danger'
							: 'cb-admin-app__btn cb-admin-app__btn--primary'
					}
					disabled={ isBusy }
					onClick={ onConfirm }
				>
					{ confirmLabel }
				</button>
			</div>
		</Modal>
	);
}
