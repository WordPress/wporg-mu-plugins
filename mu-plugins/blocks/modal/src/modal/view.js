/**
 * External dependencies
 */
import MicroModal from 'micromodal';

let idCounter = 0;
function getModalId( prefix = 'modal-' ) {
	idCounter++;
	return prefix + idCounter;
}

/**
 * Set up the modal behavior for the modal type.
 *
 * @param {HTMLElement} container
 */
function intializeModal( container ) {
	const modalId = getModalId( 'wporg-modal-' );
	container.querySelector( '.wporg-modal__button' ).setAttribute( 'data-micromodal-trigger', modalId );
	container.querySelector( '.wporg-modal__modal' ).id = modalId;

	MicroModal.init( {
		onShow: ( modal ) => {
			const button = container.querySelector(
				`.wporg-modal__button[data-micromodal-trigger=${ modal.id }]`
			);
			button.setAttribute( 'aria-expanded', true );
		},
		onClose: ( modal ) => {
			const button = container.querySelector(
				`.wporg-modal__button[data-micromodal-trigger=${ modal.id }]`
			);
			button.setAttribute( 'aria-expanded', false );
		},
	} );
}

/**
 * Set up the handlers for opening/closing the popover drawer.
 *
 * @param {HTMLElement} container
 */
function intializePopover( container ) {
	intializeInline( container, true );
}

/**
 * Set up the click handler for opening/closing the inline drawer.
 *
 * If `shouldAutoClose` is true (for the "popover" style), it adds handlers
 * for closing when click or focus moves out of the popover.
 *
 * See the navigation block submenu behavior.
 *
 * @param {HTMLElement} container
 * @param {boolean}     shouldAutoClose
 */
function intializeInline( container, shouldAutoClose = false ) {
	const button = container.querySelector( '* > .wporg-modal__button' );
	const content = container.querySelector( '.wporg-modal__modal' );

	if ( ! button || ! content ) {
		return;
	}

	button.addEventListener( 'click', () => {
		if ( button.getAttribute( 'aria-expanded' ) === 'true' ) {
			button.setAttribute( 'aria-expanded', false );
			content.classList.remove( 'is-open' );
		} else {
			button.setAttribute( 'aria-expanded', true );
			content.classList.add( 'is-open' );
		}
	} );

	if ( shouldAutoClose ) {
		// Close on click outside.
		document.addEventListener( 'click', function ( event ) {
			if ( ! container.contains( event.target ) ) {
				button.setAttribute( 'aria-expanded', false );
				content.classList.remove( 'is-open' );
			}
		} );

		// Close on focus outside or escape key.
		document.addEventListener( 'keyup', function ( event ) {
			if ( event.key === 'Escape' || ! container.contains( event.target ) ) {
				button.setAttribute( 'aria-expanded', false );
				content.classList.remove( 'is-open' );
			}
		} );
	}
}

function init() {
	const containers = document.querySelectorAll( '.wp-block-wporg-modal' );

	containers.forEach( ( container ) => {
		if ( container.classList.contains( 'is-type-modal' ) ) {
			intializeModal( container );
		} else if ( container.classList.contains( 'is-type-popover' ) ) {
			intializePopover( container );
		} else {
			intializeInline( container );
		}
	} );
}

window.addEventListener( 'load', init );
