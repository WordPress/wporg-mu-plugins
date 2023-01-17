/**
 * This is the calculated value of the admin bar + header height + local nav bar.
 */
const FIXED_HEADER_HEIGHT = 179;

/**
 * Get the value of a CSS custom property.
 *
 * @param {string}      name    Custom property name
 * @param {HTMLElement} element The element to use when calculating the custom property, defaults to body.
 *
 * @return {*} A number value if the property was in pixels, otherwise the value as seen in CSS.
 */
function getCustomPropValue( name, element = document.body ) {
	const value = window.getComputedStyle( element ).getPropertyValue( name );
	if ( 'px' === value.slice( -2 ) ) {
		return Number( value.replace( 'px', '' ) );
	}
	return value;
}

function onScroll() {
	const container = document.querySelector( '.wp-block-wporg-table-of-contents' );
	if ( ! container ) {
		return;
	}

	// Only run the scroll code if the sidebar is fixed.
	const sidebarContainer = container.parentNode;
	if ( ! sidebarContainer || ! sidebarContainer.classList.contains( 'is-fixed-sidebar' ) ) {
		return;
	}

	const mainEl = document.getElementById( 'wp--skip-link--target' );
	const footerStart = mainEl.offsetTop + mainEl.offsetHeight;

	const gap = getCustomPropValue( '--wp--preset--spacing--edge-space' );
	const viewportYOffset = window
		.getComputedStyle( document.documentElement )
		.getPropertyValue( 'margin-top' )
		.replace( 'px', '' );

	// This value needs to take account the margin on `html`.
	const scrollPosition = window.scrollY - viewportYOffset;

	if ( ! sidebarContainer.classList.contains( 'is-bottom-sidebar' ) ) {
		// The pixel location of the bottom of the sidebar, relative to the top of the page.
		const sidebarBottom = scrollPosition + sidebarContainer.offsetHeight + sidebarContainer.offsetTop;

		// Is the sidebar bottom crashing into the footer?
		if ( footerStart - gap < sidebarBottom ) {
			sidebarContainer.classList.add( 'is-bottom-sidebar' );
			// Bottom sidebar is absolutely positioned, so we need to set the top relative to the page origin.
			sidebarContainer.style.setProperty(
				'top',
				// Starting from the footer Y position, subtract the sidebar height and gap/margins, and add
				// the viewport offset. This ensures the sidebar doesn't jump when the class is switched.
				`${ footerStart - sidebarContainer.clientHeight - gap * 2 + viewportYOffset * 1 }px`
			);
		}
	} else if ( footerStart - sidebarContainer.offsetHeight - FIXED_HEADER_HEIGHT - gap * 2 > scrollPosition ) {
		// If the scroll position is higher than the top of the sidebar, switch back to just a fixed sidebar.
		sidebarContainer.classList.remove( 'is-bottom-sidebar' );
		sidebarContainer.style.removeProperty( 'top' );
	}
}

function init() {
	const container = document.querySelector( '.wp-block-wporg-table-of-contents' );

	if ( container ) {
		const viewHeight = window.innerHeight - FIXED_HEADER_HEIGHT;
		// If the table of contents sidebar is shorter than the view area, apply the
		// class so that it's fixed and scrolls with the page content.
		if ( container.parentNode?.offsetHeight < viewHeight ) {
			container.parentNode.classList.add( 'is-fixed-sidebar' );
			onScroll(); // Run once to avoid footer collisions on load (ex, when linked to #reply-title).
			window.addEventListener( 'scroll', onScroll );
		}
	}
}

window.addEventListener( 'load', init );
