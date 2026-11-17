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
			const id = decodeURIComponent( link.getAttribute( 'href' ).slice( 1 ) );
			const heading = document.getElementById( id );
			return { link, heading };
		} )
		.filter( ( { heading } ) => !! heading );

	if ( ! sections.length ) {
		return;
	}

	let currentLink = null;
	let ticking = false;

	function update() {
		ticking = false;

		const threshold = window.innerHeight / 3;
		let nextLink = null;

		sections.forEach( ( { link, heading } ) => {
			if ( heading.getBoundingClientRect().top <= threshold ) {
				nextLink = link;
			}
		} );

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

	function onScroll() {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( update );
		}
	}

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
