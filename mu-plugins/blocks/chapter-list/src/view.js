/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

const init = () => {
	const container = document.querySelector( '.wp-block-wporg-chapter-list' );
	const toggleButton = container?.querySelector( '.wporg-chapter-list__toggle' );
	const list = container?.querySelector( '.wporg-chapter-list__list' );

	if ( toggleButton && list ) {
		toggleButton.addEventListener( 'click', function () {
			if ( toggleButton.getAttribute( 'aria-expanded' ) === 'true' ) {
				toggleButton.setAttribute( 'aria-expanded', false );
				list.removeAttribute( 'style' );
			} else {
				toggleButton.setAttribute( 'aria-expanded', true );
				list.setAttribute( 'style', 'display:block;' );
			}
		} );
	}

	if ( container ) {
		container.classList.toggle( 'has-js-control' );

		const parents = container.querySelectorAll( '.page_item_has_children' );
		parents.forEach( ( item ) => {
			// Get link, remove (will re-ad later).
			const link = item.querySelector( ':scope > a' );
			link.remove();

			// Get submenu
			const submenu = item.querySelector( ':scope > ul' );

			// Create the toggle button.
			const button = document.createElement( 'button' );
			button.setAttribute( 'aria-expanded', false );
			// translators: %s link title.
			button.setAttribute( 'aria-label', sprintf( __( 'Open %s submenu', 'wporg' ), link.innerText ) );
			button.onclick = () => {
				submenu.classList.toggle( 'is-open' );
				// This attribute returns a string.
				const isOpen = button.getAttribute( 'aria-expanded' );
				button.setAttribute( 'aria-expanded', isOpen === 'false' );
				if ( isOpen === 'false' ) {
					button.setAttribute(
						'aria-label',
						// translators: %s link title.
						sprintf( __( 'Close %s submenu', 'wporg' ), link.innerText )
					);
				} else {
					button.setAttribute(
						'aria-label',
						// translators: %s link title.
						sprintf( __( 'Open %s submenu', 'wporg' ), link.innerText )
					);
				}
			};

			const buttonGroup = document.createElement( 'span' );
			buttonGroup.className = 'wporg-chapter-list__button-group';
			buttonGroup.append( button, link );

			item.insertBefore( buttonGroup, submenu );

			// Automatically open the trail to the current page.
			if (
				item.classList.contains( 'current_page_item' ) ||
				item.classList.contains( 'current_page_ancestor' )
			) {
				submenu.classList.toggle( 'is-open' );
				button.setAttribute( 'aria-expanded', true );
				button.setAttribute(
					'aria-label',
					// translators: %s link title.
					sprintf( __( 'Close %s submenu', 'wporg' ), link.innerText )
				);
			}
		} );
	}
};

window.addEventListener( 'load', init );
