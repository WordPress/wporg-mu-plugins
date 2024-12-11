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
			context.modal.focus();
		},

		close: () => {
			const context = getContext();
			context.isOpen = false;
			context.toggleButton.focus();
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
				// If shift + tab it change the direction.
				if ( event.shiftKey && window.document.activeElement === context.firstFocusableElement ) {
					event.preventDefault();
					context.lastFocusableElement.focus();
				} else if ( ! event.shiftKey && window.document.activeElement === context.lastFocusableElement ) {
					event.preventDefault();
					context.firstFocusableElement.focus();
				}
			}
		},
	},

	callbacks: {
		init: () => {
			const context = getContext();
			const { ref } = getElement();
			context.toggleButton = ref.querySelector( '.wporg-modal__toggle' );
			context.modal = ref.querySelector( '.wporg-modal__modal' );

			if ( context.isOpen ) {
				const focusableElements = context.modal.querySelectorAll( focusableSelectors );
				context.firstFocusableElement = focusableElements[ 0 ];
				context.lastFocusableElement = focusableElements[ focusableElements.length - 1 ];
			}
		},
	},
} );
