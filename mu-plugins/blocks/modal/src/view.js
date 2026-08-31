/**
 * WordPress dependencies
 */
import { getContext, getElement, store } from '@wordpress/interactivity';

// See https://github.com/WordPress/gutenberg/blob/37f52ae884a40f7cb77ac2484648b4e4ad973b59/packages/block-library/src/navigation/view-interactivity.js
const focusableSelectors = [
	'a[href]',
	'input:not([disabled]):not([type="hidden"]):not([aria-hidden])',
	'select:not([disabled]):not([aria-hidden])',
	'textarea:not([disabled]):not([aria-hidden])',
	'button:not([disabled]):not([aria-hidden])',
	'[contenteditable]',
	'[tabindex]:not([tabindex^="-"])',
];

/*
 * Context is a public namespace, readable from other markup as `wporg/modal::context.…`, so only
 * the serializable `isOpen` flag lives there. Focus-trap boundaries are computed per keydown
 * because the modal content is arbitrary inner blocks that can change while the modal is open.
 */

/**
 * The block wrapper for whichever element triggered the current action.
 *
 * @return {HTMLElement|null} The root element, or null when it can't be resolved.
 */
const getRoot = () => getElement().ref?.closest( '[data-wp-interactive="wporg/modal"]' ) ?? null;

const { actions } = store( 'wporg/modal', {
	actions: {
		toggle: () => {
			const context = getContext();
			if ( context.isOpen ) {
				actions.close();
			} else {
				actions.open();
			}
		},

		/**
		 * Close the modal only if the backdrop is clicked.
		 * Ignores clicks inside the modal itself.
		 *
		 * @param {Event} event
		 */
		clickBackdrop: ( event ) => {
			if ( event.target.classList.contains( 'wporg-modal__modal-backdrop' ) ) {
				actions.close();
			}
		},

		open: () => {
			const context = getContext();
			context.isOpen = true;
		},

		close: () => {
			const context = getContext();
			context.isOpen = false;
			getRoot()?.querySelector( '.wporg-modal__toggle' )?.focus();
		},

		handleKeydown: ( event ) => {
			const context = getContext();
			// Only handle key events if the dropdown is open.
			if ( ! context.isOpen ) {
				return;
			}

			// If Escape close the dropdown.
			if ( event.key === 'Escape' ) {
				actions.close();
				return;
			}

			// Trap focus.
			if ( event.key === 'Tab' ) {
				const modal = getRoot()?.querySelector( '.wporg-modal__modal' );
				const focusableElements = modal ? modal.querySelectorAll( focusableSelectors ) : [];
				if ( ! focusableElements.length ) {
					return;
				}
				const firstFocusableElement = focusableElements[ 0 ];
				const lastFocusableElement = focusableElements[ focusableElements.length - 1 ];

				// If shift + tab it change the direction.
				if ( event.shiftKey && window.document.activeElement === firstFocusableElement ) {
					event.preventDefault();
					lastFocusableElement.focus();
				} else if ( ! event.shiftKey && window.document.activeElement === lastFocusableElement ) {
					event.preventDefault();
					firstFocusableElement.focus();
				}
			}
		},
	},

	callbacks: {
		/**
		 * Runs after the render that unhides the modal; focusing in `open()` would
		 * target a still-hidden element and no-op.
		 */
		focusModal: () => {
			const context = getContext();
			if ( context.isOpen ) {
				getElement().ref?.focus();
			}
		},
	},
} );
