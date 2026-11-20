/**
 * Mark the link for the section currently in view, so readers can see where they are.
 *
 * A section is current once its heading has scrolled past the top third of the viewport.
 *
 * @param {HTMLElement} container The table of contents block.
 */
function initCurrentSection( container ) {
	const sections = Array.from( container.querySelectorAll( '.wporg-table-of-contents__list a[href^="#"]' ) )
		.map( ( link ) => {
			const heading = document.getElementById( link.getAttribute( 'href' ).slice( 1 ) );
			return { link, heading };
		} )
		.filter( ( { heading } ) => !! heading );

	if ( ! sections.length ) {
		return;
	}

	let currentLink = null;
	let pinnedLink = null;
	let ticking = false;

	/**
	 * Find the link for the last visible heading past the threshold.
	 *
	 * @return {HTMLElement|null} The link for the section in view.
	 */
	function findCurrentLink() {
		// Short final sections can't scroll up to the threshold, so at the bottom the last one wins.
		const isAtBottom =
			window.scrollY > 0 && window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 1;
		const threshold = isAtBottom ? Infinity : window.innerHeight / 3;
		let nextLink = null;

		sections.forEach( ( { link, heading } ) => {
			/*
			 * Skip headings that aren't rendered (e.g. in a closed details block). Closed details hide
			 * content with content-visibility where supported, which getClientRects() doesn't detect.
			 */
			const isVisible = heading.checkVisibility
				? heading.checkVisibility()
				: heading.getClientRects().length;
			if ( isVisible && heading.getBoundingClientRect().top <= threshold ) {
				nextLink = link;
			}
		} );

		return nextLink;
	}

	/**
	 * Move `aria-current` to the pinned link, or else to the link for the section in view.
	 */
	function update() {
		ticking = false;

		const nextLink = pinnedLink || findCurrentLink();

		if ( nextLink === currentLink ) {
			return;
		}

		if ( currentLink ) {
			currentLink.removeAttribute( 'aria-current' );
		}
		if ( nextLink ) {
			nextLink.setAttribute( 'aria-current', 'true' );
		}
		currentLink = nextLink;
	}

	/**
	 * Schedule one update per animation frame.
	 */
	function onScroll() {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( update );
		}
	}

	/*
	 * A jump to a short section can leave the next heading past the threshold too, so keep the
	 * section the reader asked for current until they scroll on their own.
	 */
	sections.forEach( ( { link } ) => {
		link.addEventListener( 'click', () => {
			pinnedLink = link;
			update();
		} );
	} );
	[ 'wheel', 'touchmove', 'keydown', 'mousedown' ].forEach( ( type ) => {
		window.addEventListener(
			type,
			() => {
				pinnedLink = null;
			},
			{ passive: true }
		);
	} );

	const hashSection = sections.find(
		( { link } ) => window.location.hash && link.hash === window.location.hash
	);
	pinnedLink = hashSection ? hashSection.link : null;

	window.addEventListener( 'scroll', onScroll, { passive: true } );
	window.addEventListener( 'resize', onScroll );
	update();
}

function init() {
	const container = document.querySelector( '.wp-block-wporg-table-of-contents' );

	if ( ! container ) {
		return;
	}

	const toggleButton = container.querySelector( '.wporg-table-of-contents__toggle' );
	const list = container.querySelector( '.wporg-table-of-contents__list' );

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

	initCurrentSection( container );
}

window.addEventListener( 'load', init );
