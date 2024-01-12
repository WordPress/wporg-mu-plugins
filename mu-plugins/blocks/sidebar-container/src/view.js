/**
 * Fallback values for custom properties match CSS defaults.
 */

const GLOBAL_NAV_HEIGHT = getCustomPropValue( '--wp-global-header-height' ) || 90;
const ADMIN_BAR_HEIGHT = parseInt(
	window.getComputedStyle( document.documentElement ).getPropertyValue( 'margin-top' ),
	10
);
const SPACE_TO_TOP = getCustomPropValue( '--wp--custom--wporg-sidebar-container--spacing--margin--top' ) || 80;
const SCROLL_POSITION_TO_FIX = GLOBAL_NAV_HEIGHT + SPACE_TO_TOP - ADMIN_BAR_HEIGHT;

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
 */
function onScroll() {
	// Only run the scroll code if the sidebar is floating on a wide screen.
	if ( ! window.matchMedia( '(min-width: 1200px)' ).matches ) {
		return;
	}

	const { scrollY, innerHeight: windowHeight } = window;
	// const footerTop = footer.getBoundingClientRect().top;
	const scrollPosition = scrollY - ADMIN_BAR_HEIGHT;

	// Toggle the fixed position based on whether the scrollPosition is greater than the initial gap from the top.
	container.classList.toggle( 'is-fixed-sidebar', scrollPosition > SCROLL_POSITION_TO_FIX );

	const footerStart = mainEl.offsetTop + mainEl.offsetHeight;

	// Is footerStart visible in the viewport?
	if ( footerStart < scrollPosition + windowHeight ) {
		container.style.setProperty( 'height', `${ footerStart - scrollPosition - container.offsetTop }px` );
	} else {
		container.style.removeProperty( 'height' );
	}
}

function init() {
	container = document.querySelector( '.wp-block-wporg-sidebar-container' );
	mainEl = document.getElementById( 'wp--skip-link--target' );

	if ( mainEl && container ) {
		onScroll(); // Run once to avoid footer collisions on load (ex, when linked to #reply-title).
		window.addEventListener( 'scroll', onScroll );
	}

	// If there is no table of contents, hide the heading.
	if ( ! document.querySelector( '.wp-block-wporg-table-of-contents' ) ) {
		const heading = document.querySelector( '.wp-block-wporg-sidebar-container h2' );
		heading?.style.setProperty( 'display', 'none' );
	}
}

window.addEventListener( 'load', init );
