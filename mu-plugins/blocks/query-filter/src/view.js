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

const { actions } = store( 'wporg/query-filter', {
	actions: {
		/**
		 * Toggles the overflow-x style of the query filter between 'hidden' and 'scroll'.
		 *
		 * In certain themes (e.g., showcase), an 'overflow-x: scroll' is added on mobile screens to always display
		 * the horizontal scrollbar, indicating to users that there's more content to the right.
		 * However, this persistent display feature causes the dropdown menu to be overlaid by the scrollbar
		 * when opened (See issue https://github.com/WordPress/wporg-mu-plugins/issues/467#issuecomment-1754349676).
		 * This function serves to address that issue.
		 *
		 */
		toggleOverflowX: () => {
			const filtersElement = document.querySelector( '.wporg-query-filters' );

			if ( filtersElement ) {
				const currentOverflowX = window.getComputedStyle( filtersElement ).overflowX;

				if ( 'hidden' === currentOverflowX ) {
					filtersElement.style.overflowX = 'scroll';
				} else if ( 'scroll' === currentOverflowX || 'auto' === currentOverflowX ) {
					filtersElement.style.overflowX = 'hidden';
				}
			}
		},

		closeDropdown: () => {
			const context = getContext();
			context.isOpen = false;
			context.form?.reset();

			const count = context.form?.querySelectorAll( 'input:checked' ).length;
			actions.updateButtons( count );
			document.documentElement.classList.remove( 'is-query-filter-open' );

			actions.toggleOverflowX();
		},

		updateButtons: ( count ) => {
			const context = getContext();
			if ( ! context.form ) {
				return;
			}

			const applyButton = context.form.querySelector( 'input[type="submit"]' );
			const clearButton = context.form.querySelector( '.wporg-query-filter__modal-action-clear' );

			// Only update the apply button if multiple selections are allowed.
			if ( context.hasMultiple ) {
				if ( count ) {
					applyButton.value = applyButton.dataset.labelWithCount.replace( '%s', count );
				} else {
					applyButton.value = applyButton.dataset.label;
				}
			}

			if ( clearButton ) {
				clearButton.setAttribute( 'aria-disabled', count ? 'false' : 'true' );
			}
		},

		toggle: () => {
			const context = getContext();
			if ( context.isOpen ) {
				actions.closeDropdown();
			} else {
				context.isOpen = true;
				document.documentElement.classList.add( 'is-query-filter-open' );
				actions.toggleOverflowX();
			}
		},
		handleKeydown: ( event ) => {
			const context = getContext();
			// If Escape close the dropdown.
			if ( event.key === 'Escape' ) {
				actions.closeDropdown();
				context.toggleButton.focus();
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
		handleFormChange: () => {
			const context = getContext();
			const count = context.form.querySelectorAll( 'input:checked' ).length;
			actions.updateButtons( count );
		},
		clearSelection: () => {
			const context = getContext();
			const { ref } = getElement();
			if ( 'true' === ref.getAttribute( 'aria-disabled' ) ) {
				return;
			}
			context.form.querySelectorAll( 'input' ).forEach( ( input ) => ( input.checked = false ) );
			actions.updateButtons( 0 );
		},
	},
	effects: {
		init: () => {
			const context = getContext();
			const { ref } = getElement();
			context.toggleButton = ref.querySelector( '.wporg-query-filter__toggle' );
			context.form = ref.querySelector( 'form' );

			if ( context.isOpen ) {
				const focusableElements = ref.querySelectorAll( focusableSelectors );
				context.firstFocusableElement = focusableElements[ 0 ];
				context.lastFocusableElement = focusableElements[ focusableElements.length - 1 ];
			}
		},
		checkPosition: () => {
			const context = getContext();
			const { ref } = getElement();
			if ( context.isOpen ) {
				const position = ref.getBoundingClientRect();
				if ( position.left < 0 ) {
					ref.style.left = 0;
				}
			}
		},
		focusFirstElement: () => {
			const context = getContext();
			const { ref } = getElement();
			if ( context.isOpen ) {
				ref.querySelector( 'form input:first-child' ).focus();
			}
		},
	},
} );
