/* global ResizeObserver */

let containers;
let main;
let footer;
let adminBarHeight;
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
		const localNavHeight = getCustomPropValue( '--local--nav--offset', container );
		const mainTop = main.getBoundingClientRect().top;

		// Toggle the fixed position based on whether main has reached the local nav.
		// This assumes that main and the sidebar are top aligned.
		const shouldFix = mainTop <= localNavHeight + adminBarHeight;
		container.classList.toggle( 'is-fixed-sidebar', shouldFix );

		// If the sidebar is fixed and the footer is visible in the viewport, reduce the height to stop overlap.
		const footerShowing = scrollPosition + windowHeight > footer.offsetTop;
		if ( shouldFix && footerShowing ) {
			container.style.setProperty(
				'height',
				`${ footer.offsetTop - scrollPosition - container.offsetTop }px`
			);
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

/**
 * Initialize the sidebar container.
 * Three elements are required: main, a sidebar container (or multiple), and a footer.
 */
function init() {
	main = document.querySelector( 'main' );
	containers = document.querySelectorAll( '.wp-block-wporg-sidebar-container' );
	footer = document.querySelector( '.wporg-footer' );

	if ( main && containers.length && footer ) {
		containers.forEach( ( container ) => {
			const scrollHandler = createScrollHandler( container );
			scrollHandlers.push( scrollHandler );
			window.addEventListener( 'scroll', scrollHandler );

			// When the sidebar container adjacent to the main element (usually the chapter list)
			// is taller than the main element, a glitch occurs when scrolling past the point where
			// the sidebar becomes fixed, because the main element height depends on the sidebar container
			// height. To avoid this, we observe the height of this container when it is floating and
			// not fixed, and set the min-height of the main element to match the container height.
			const isSibling = container.previousElementSibling === main || container.nextElementSibling === main;
			if ( 'ResizeObserver' in window && isSibling ) {
				const resizeObserver = new ResizeObserver( ( entries ) => {
					for ( const entry of entries ) {
						if (
							container.classList.contains( 'is-floating-sidebar' ) &&
							! container.classList.contains( 'is-fixed-sidebar' )
						) {
							const containerHeight = entry.contentRect.height;

							if ( containerHeight ) {
								main.style.setProperty( 'min-height', `${ containerHeight }px` );
							}
						}
					}
				} );

				resizeObserver.observe( container );
			}
		} );
	}

	// Run once to set height vars and position elements on load.
	// Avoids footer collisions (ex, when linked to #reply-title).
	onResize();
	window.addEventListener( 'resize', onResize );
}

window.addEventListener( 'load', init );
