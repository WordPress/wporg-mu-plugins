/**
 * This is the calculated value of the admin bar + header height + local nav bar.
 * LOCAL_NAV_HEIGHT fallback value matches that of the CSS variable.
 */
const LOCAL_NAV_HEIGHT = getCustomPropValue( '--wp--custom--local-navigation-bar--spacing--height' ) || 60;
const FIXED_HEADER_HEIGHT = 32 + 90 + LOCAL_NAV_HEIGHT;
const GAP = getCustomPropValue( '--wp--custom--wporg-sidebar-container--spacing--margin--top' ) || 150;

let container;
let mainEl;

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

/**
 * Check the position of the sidebar vs the height of the viewport & page
 * container, and toggle the "bottom" class to position the sidebar without
 * overlapping the footer.
 *
 * @return {boolean} True if the sidebar is at the bottom of the page.
 */
function onScroll() {
	// Only run the scroll code if the sidebar is floating.
	if ( ! mainEl || ! container || ! window.matchMedia( '(min-width: 1200px)' ).matches ) {
		return;
	}

	const footerStart = mainEl.offsetTop + mainEl.offsetHeight;
	const viewportYOffset = window
		.getComputedStyle( document.documentElement )
		.getPropertyValue( 'margin-top' )
		.replace( 'px', '' );

	// This value needs to take account the margin on `html`.
	const scrollPosition = window.scrollY - viewportYOffset;

	if ( ! container.classList.contains( 'is-bottom-sidebar' ) ) {
		// The pixel location of the bottom of the sidebar, relative to the top of the page.
		const sidebarBottom = scrollPosition + container.offsetHeight + container.offsetTop;

		// Is the sidebar bottom crashing into the footer?
		if ( footerStart - GAP < sidebarBottom ) {
			container.classList.add( 'is-bottom-sidebar' );
			// Bottom sidebar is absolutely positioned, so we need to set the top relative to the page origin.
			container.style.setProperty(
				'top',
				// Starting from the footer Y position, subtract the sidebar height and gap/margins, and add
				// the viewport offset. This ensures the sidebar doesn't jump when the class is switched.
				`${ footerStart - container.clientHeight - GAP * 2 + viewportYOffset * 1 }px`
			);
			return true;
		}
	} else if ( footerStart - container.offsetHeight - GAP * 2 > scrollPosition ) {
		// If the scroll position is higher than the top of the sidebar, switch back to just a fixed sidebar.
		container.classList.remove( 'is-bottom-sidebar' );
		container.style.removeProperty( 'top' );
	}

	// Toggle the fixed position based on whether the scrollPosition is greater than the initial gap from the top.
	container.classList.toggle( 'is-fixed-sidebar', scrollPosition > GAP );

	return false;
}

function isSidebarWithinViewport() {
	if ( ! container ) {
		return false;
	}
	// Margin offset from the top of the sidebar.
	const gap = getCustomPropValue( '--wp--custom--wporg-sidebar-container--spacing--margin--top' );
	// Usable viewport height.
	const viewHeight = window.innerHeight - FIXED_HEADER_HEIGHT;
	// Get the height of the sidebar, plus the top margin and 50px for the
	// "Back to top" link, which isn't visible until `is-fixed-sidebar` is
	// added, therefore not included in the offsetHeight value.
	const sidebarHeight = container.offsetHeight + gap + 50;
	// If the sidebar is shorter than the view area, apply the class so
	// that it's fixed and scrolls with the page content.
	return sidebarHeight < viewHeight;
}

function init() {
	container = document.querySelector( '.wp-block-wporg-sidebar-container' );
	mainEl = document.getElementById( 'wp--skip-link--target' );
	const toggleButton = container?.querySelector( '.wporg-table-of-contents__toggle' );
	const list = container?.querySelector( '.wporg-table-of-contents__list' );

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

	if ( isSidebarWithinViewport() ) {
		onScroll(); // Run once to avoid footer collisions on load (ex, when linked to #reply-title).
		window.addEventListener( 'scroll', onScroll );

		const observer = new window.ResizeObserver( () => {
			// If the sidebar is positioned at the bottom and mainEl resizes,
			// it will remain fixed at the previous bottom position, leading to a broken page layout.
			// In this case manually trigger the scroll handler to reposition.
			if ( container.classList.contains( 'is-bottom-sidebar' ) ) {
				container.classList.remove( 'is-bottom-sidebar' );
				container.style.removeProperty( 'top' );
				const isBottom = onScroll();
				// After the sidebar is repositioned, also adjusts the scroll position
				// to a point where the sidebar is visible.
				if ( isBottom ) {
					window.scrollTo( {
						top: container.offsetTop - FIXED_HEADER_HEIGHT,
						behavior: 'instant',
					} );
				}
			}
		} );

		observer.observe( mainEl );
	}

	// If there is no table of contents, hide the heading.
	if ( ! document.querySelector( '.wp-block-wporg-table-of-contents' ) ) {
		const heading = document.querySelector( '.wp-block-wporg-sidebar-container h2' );
		heading?.style.setProperty( 'display', 'none' );
	}
}

window.addEventListener( 'load', init );
