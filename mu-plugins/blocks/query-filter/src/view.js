/**
 * WordPress dependencies
 */
import { store as wpStore } from '@wordpress/interactivity';

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

wpStore( {
	actions: {
		wporg: {
			queryFilter: {
				toggle: ( { context } ) => {
					context.wporg.queryFilter.isOpen = ! context.wporg.queryFilter.isOpen;
				},
				handleFocusout: ( { context, event, ref } ) => {
					if (
						! context.wporg.queryFilter.hasHover &&
						! ref.contains( event.relatedTarget ) &&
						event.target !== window.document.activeElement
					) {
						context.wporg.queryFilter.isOpen = false;
					}
				},
				handleKeydown: ( { context, event } ) => {
					// If Escape close the dropdown.
					if ( event.key === 'Escape' ) {
						context.wporg.queryFilter.isOpen = false;
						context.wporg.queryFilter.toggleButton.focus();
						return;
					}

					// Trap focus.
					if ( event.key === 'Tab' ) {
						// If shift + tab it change the direction.
						if (
							event.shiftKey &&
							window.document.activeElement === context.wporg.queryFilter.firstFocusableElement
						) {
							event.preventDefault();
							context.wporg.queryFilter.lastFocusableElement.focus();
						} else if (
							! event.shiftKey &&
							window.document.activeElement === context.wporg.queryFilter.lastFocusableElement
						) {
							event.preventDefault();
							context.wporg.queryFilter.firstFocusableElement.focus();
						}
					}
				},
				handleMouseEnter: ( { context } ) => {
					context.wporg.queryFilter.hasHover = true;
				},
				handleMouseLeave: ( { context } ) => {
					context.wporg.queryFilter.hasHover = null;
				},
				clearSelection: ( { ref } ) => {
					const form = ref.closest( 'form' );
					form.querySelectorAll( 'input[type="checkbox"]' ).forEach(
						( input ) => ( input.checked = false )
					);
				},
			},
		},
	},
	effects: {
		wporg: {
			queryFilter: {
				init: ( { context, ref } ) => {
					if ( context.wporg.queryFilter.isOpen ) {
						const focusableElements = ref.querySelectorAll( focusableSelectors );
						context.wporg.queryFilter.toggleButton = ref.querySelector(
							'.wporg-query-filter__toggle'
						);
						context.wporg.queryFilter.firstFocusableElement = focusableElements[ 0 ];
						context.wporg.queryFilter.lastFocusableElement =
							focusableElements[ focusableElements.length - 1 ];
					}
				},
				focusFirstElement: ( { context, ref } ) => {
					if ( context.wporg.queryFilter.isOpen ) {
						ref.querySelector( 'form input:first-child' ).focus();
					}
				},
			},
		},
	},
} );
