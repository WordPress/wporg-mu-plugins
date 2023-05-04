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
 * Return whether every item in the array has the class 'is-truncated'
 *
 * Example HTML:
 * <span>
 *    <a href="#" class="is-truncated" />
 * </span>
 *
 * @param {Array<HTMLElement>} arr
 * @return {boolean}
 */
const allTruncated = ( arr ) =>
	arr.every( ( { firstChild: anchorElement } ) => anchorElement.classList.contains( 'is-truncated' ) );

/**
 * Return whether every item in the array has the class 'is-hidden'
 *
 * @param {Array<HTMLElement>} arr
 * @return {boolean}
 */
const allHidden = ( arr ) => arr.every( ( crumb ) => crumb.classList.contains( 'is-hidden' ) );

/**
 * This function collapses the breadcrumbs until the available space is greater than the breakpoint.
 *
 * @param {Array<HTMLElement>} arr        List of breadcrumb elements.
 * @param {HTMLElement}        container  Breadcrumb parent container
 * @param {number}             breakpoint The breakpoint in pixels.
 *
 * @return {void}
 */
const collapseCrumbs = ( breadcrumbs, container, breakpoint ) => {
	const middleCrumbs = arr.slice( 1, arr.length - 1 );

	// First, try to truncate the text
	let index = 0;
	while ( getAvailableSpace( container ) < breakpoint && ! allTruncated( middleCrumbs ) ) {
		middleCrumbs[ index ].firstChild.classList.add( 'is-truncated' );
		middleCrumbs[ index ].firstChild.firstChild.classList.add( 'screen-reader-text' );
		index++;
	}

	// Second, hide the items if everything is truncated
	let index2 = 0;
	while ( getAvailableSpace( container ) < breakpoint && ! allHidden( middleCrumbs ) ) {
		middleCrumbs[ index2 ].classList.add( 'is-hidden' );
		index2++;
	}

	const remainingSpace = getAvailableSpace( container );
	// if we truncated and hid all the items, truncate the last part
	if ( remainingSpace < breakpoint ) {
		const lastPart = arr[ arr.length - 1 ];
		const width = Math.max( 75, lastPart.getBoundingClientRect().width - breakpoint );
		lastPart.style.width = `${ width }px`;
	}
};

/**
 * This function expands the breadcrumbs until the available space is less than the breakpoint.
 *
 * The expected html for this to work is:
 * <span>
 *     <a href="#" data-title="Breadcrumb Title">
 *         <span>Text</span>
 *     </a>
 * </span>
 *
 * @param {Array<HTMLElement>} arr                     List of breadcrumb elements.
 * @param {HTMLElement}        container               Breadcrumb parent container
 * @param {number}             breakpoint              The breakpoint in pixels.
 * @param {number}             finalPartOriginalLength The original width of the last crumb.
 *
 * @return {void}
 */
const expandCrumbs = ( arr, container, breakpoint, finalPartOriginalLength ) => {
	const middleCrumbs = arr.slice( 1, arr.length - 1 );
	const currentSpaceValue = getAvailableSpace( container );
	let pixelToAllocate = Math.ceil( currentSpaceValue - breakpoint );

	// If the last part has ellipses, expand it.
	const lastPart = arr[ arr.length - 1 ];
	const currWidth = parseInt( lastPart.style.width );

	if ( currWidth < finalPartOriginalLength ) {
		const newWidth = Math.min( currWidth + pixelToAllocate, finalPartOriginalLength );
		lastPart.style.width = `${ newWidth }px`;
		pixelToAllocate -= newWidth - currWidth;
	}

	// 20 is roughly the width of the ellipsis.
	if ( pixelToAllocate < 20 ) {
		return;
	}

	/**
	 * If there are hidden elements, show them first.
	 */
	const hiddenEls = middleCrumbs.filter( ( crumb ) => crumb.classList.contains( 'is-hidden' ) );

	if ( hiddenEls.length ) {
		hiddenEls[ 0 ].classList.remove( 'is-hidden' );
		return;
	}

	/**
	 * Loop through right to left, expand
	 */
	for ( let i = middleCrumbs.length - 1; i >= 0; i-- ) {
		const anchorElement = middleCrumbs[ i ].firstChild;

		// We don't need to do anything if the element is already expanded.
		if ( ! anchorElement.classList.contains( 'is-truncated' ) ) {
			continue;
		}

		const spanWidth = anchorElement.firstChild.getBoundingClientRect().width;

		// If this item can't fit, wait until it can.
		if ( spanWidth >= pixelToAllocate ) {
			return;
		}

		anchorElement.classList.remove( 'is-truncated' );
		anchorElement.firstChild.classList.remove( 'screen-reader-text' );

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

	// The menu and the breadcrumbs should never come closer than this.
	const breakpoint = 50;

	// Save the original width of the last crumb so we can restore it when the window is resized.
	const finalPartOriginalLength = crumbs[ crumbs.length - 1 ].getBoundingClientRect().width;

	/**
	 * Truncates or expands the breadcrumbs based on the available space.
	 */
	const truncate = () => {
		if ( getAvailableSpace( crumbContainer.parentElement ) < breakpoint ) {
			collapseCrumbs( crumbs, crumbContainer.parentElement, breakpoint );
		} else {
			expandCrumbs( crumbs, crumbContainer.parentElement, breakpoint, finalPartOriginalLength );
		}
	};

	// Run on init
	truncate();

	let timeout = null;
	window.addEventListener( 'resize', () => {
		clearTimeout( timeout );

		timeout = setTimeout( truncate, 50 );
	} );
};

document.addEventListener( 'DOMContentLoaded', init );
