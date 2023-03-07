/**
 * Calculates the available space in the parent container between the breadcrumb container and the menu container.
 *
 * @param {HTMLElement} parent The parent container.
 *
 * @return {number} The available space in pixels
 */
const getAvailableSpace = ( parent ) => {
	const parentWidth = parent.getBoundingClientRect().width;
	let occupiedSpace = 0;

	for ( const child of parent.children ) {
		occupiedSpace += child.getBoundingClientRect().width;
	}

	return parentWidth - occupiedSpace;
};

/**
 * This function collapses the breadcrumbs until the available space is greater than the breakpoint.
 *
 * @param {Array<HTMLElement>} arr        List of breadcrumb elements.
 * @param {HTMLElement}        container  Breadcrumb parent container
 * @param {number}             breakpoint The breakpoint in pixels.
 *
 * @return {void}
 */
const collapseCrumbs = ( arr, container, breakpoint ) => {
	/**
	 * Loop through left to right, truncate until space exceeds breakpoint.
	 */
	for ( let i = 0; i < arr.length; i++ ) {
		if ( getAvailableSpace( container ) > breakpoint ) {
			return;
		}

		const allTruncated =
			arr.filter( ( crumb ) => crumb.firstChild.classList.contains( 'truncated' ) ).length === arr.length;

		if ( allTruncated ) {
			arr[ i ].classList.add( 'hidden' );
		} else {
			arr[ i ].firstChild.classList.add( 'truncated' );
		}
	}
};

/**
 * This function expands the breadcrumbs until the available space is less than the breakpoint.
 *
 * @param {Array<HTMLElement>} arr        List of breadcrumb elements.
 * @param {HTMLElement}        container  Breadcrumb parent container
 * @param {number}             breakpoint The breakpoint in pixels.
 *
 * @return {void}
 */
const expandCrumbs = ( arr, container, breakpoint ) => {
	const currentSpaceValue = getAvailableSpace( container );
	let pixelToAllocate = Math.ceil( currentSpaceValue - breakpoint );

	if ( pixelToAllocate < 0 ) {
		return;
	}

	/**
	 * Loop through right to left, expand
	 */
	for ( let i = arr.length - 1; i >= 0; i-- ) {
		/**
		 * If there are hidden elements, show them first.
		 */
		const hiddenEls = arr.filter( ( crumb ) => crumb.classList.contains( 'hidden' ) );

		if ( hiddenEls.length ) {
			hiddenEls[ 0 ].classList.remove( 'hidden' );
			return;
		}

		const anchorElement = arr[ i ].firstChild;

		// We don't need to do anything if the element is already expanded.
		if ( ! anchorElement.classList.contains( 'truncated' ) ) {
			continue;
		}

		const spanWidth = anchorElement.firstChild.getBoundingClientRect().width;

		// If the this item can't fit, wait until it can.
		if ( spanWidth >= pixelToAllocate ) {
			return;
		}

		anchorElement.classList.remove( 'truncated' );

		pixelToAllocate -= spanWidth;
	}
};

const init = () => {
	const crumbContainer = document.querySelector( '.wp-block-wporg-site-breadcrumbs' );

	if ( ! crumbContainer ) {
		return;
	}

	const crumbs = Array.from( crumbContainer.children );

	// We don't need to do anything for this many crumbs.
	if ( crumbs.length <= 3 ) {
		return;
	}

	const breakpoint = 50; // The menu and the breadcrumbs should never come closer than this.
	const middleCrumbs = crumbs.slice( 1, crumbs.length - 1 );
	let prevWindowWidth = window.innerWidth; // Track window expansion

	const truncate = () => {
		// Window is shrinking
		if ( prevWindowWidth >= window.innerWidth ) {
			collapseCrumbs( middleCrumbs, crumbContainer.parentElement, breakpoint );
		} else {
			expandCrumbs( middleCrumbs, crumbContainer.parentElement, breakpoint );
		}

		prevWindowWidth = window.innerWidth;
	};

	truncate();

	window.addEventListener( 'resize', truncate );
};

document.addEventListener( 'DOMContentLoaded', init );
