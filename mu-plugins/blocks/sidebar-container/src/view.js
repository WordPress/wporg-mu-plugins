/**
 * Fallback values for custom properties match CSS defaults.
 */
const SPACE_TO_TOP = getCustomPropValue( '--wp--custom--wporg-sidebar-container--spacing--margin--top' ) || 80;

let containers;
let mainEl;
let adminBarHeight;
let globalNavHeight;
const scrollHandlers = [];

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
	if ( '0' === value ) {
		return 0;
	}
	if ( 'px' === value.slice( -2 ) ) {
		return Number( value.replace( 'px', '' ) );
	}
	return value;
}

/**
 * Check the position of the sidebar relative to the scroll position,
 * and toggle the "fixed" class at a certain point.
 * Reduce the height of the sidebar to stop it overlapping the footer.
 *
 * @param {HTMLElement} container The sidebar container.
 * @return {Function}   onScroll  The sidebar scroll handler.
 */
function createScrollHandler( container ) {
	return function onScroll() {
		// Only run the scroll code if the sidebar is floating.
		if ( ! container.classList.contains( 'is-floating-sidebar' ) ) {
			return false;
		}

		const { scrollY, innerHeight: windowHeight } = window;
		const scrollPosition = scrollY - adminBarHeight;
		const localNavOffset = getCustomPropValue( '--local--nav--offset', container );
		const paddingTop = getCustomPropValue( '--local--padding', container );

		// Toggle the fixed position based on whether the scrollPosition is greater than the
		// initial gap from the top minus the padding applied when fixed.
		container.classList.toggle(
			'is-fixed-sidebar',
			scrollPosition > SPACE_TO_TOP + globalNavHeight + localNavOffset - adminBarHeight - paddingTop
		);

		const footerStart = mainEl.offsetTop + mainEl.offsetHeight;

		// Is the footer visible in the viewport?
		if ( footerStart < scrollPosition + windowHeight ) {
			container.style.setProperty( 'height', `${ footerStart - scrollPosition - container.offsetTop }px` );
		} else {
			container.style.removeProperty( 'height' );
		}
	};
}

/**
 * Set the height for the admin bar and global nav vars.
 * Set the floating sidebar class on each container based on its breakpoint.
 * Show hidden containers after layout.
 */
function onResize() {
	adminBarHeight = parseInt(
		window.getComputedStyle( document.documentElement ).getPropertyValue( 'margin-top' ),
		10
	);
	globalNavHeight = getCustomPropValue( '--wp-global-header-height' ) || 90;

	containers.forEach( ( container ) => {
		// Toggle the floating class based on the configured breakpoint.
		const shouldFloat = window.matchMedia( `(min-width: ${ container.dataset.breakpoint })` ).matches;
		container.classList.toggle( 'is-floating-sidebar', shouldFloat );
		// Show the sidebar after layout, if it has been hidden to avoid FOUC.
		if ( 'none' === window.getComputedStyle( container ).display ) {
			container.style.setProperty( 'display', 'revert' );
		}
	} );

	scrollHandlers.forEach( ( handler ) => handler() );
}

function init() {
	containers = document.querySelectorAll( '.wp-block-wporg-sidebar-container' );
	mainEl = document.getElementById( 'wp--skip-link--target' );

	if ( mainEl && containers.length ) {
		containers.forEach( ( container ) => {
			const scrollHandler = createScrollHandler( container );
			scrollHandlers.push( scrollHandler );
			window.addEventListener( 'scroll', scrollHandler );
		} );
	}

	// Run once to set height vars and position elements on load.
	// Avoids footer collisions (ex, when linked to #reply-title).
	onResize();
	window.addEventListener( 'resize', onResize );
}

window.addEventListener( 'load', init );
