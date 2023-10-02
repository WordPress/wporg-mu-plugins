function debounce( fn ) {
	// This holds the requestAnimationFrame reference, so we can cancel it if we wish
	let frame;

	// The debounce function returns a new function that can receive a variable number of arguments
	return ( ...params ) => {
		// If the frame variable has been defined, clear it now, and queue for next frame
		if ( frame ) {
			cancelAnimationFrame( frame );
		}

		// Queue our function call for the next frame
		frame = requestAnimationFrame( () => {
			// Call our function and pass any params we received
			fn( ...params );
		} );
	};
}

function init() {
	const container = document.querySelector( '.wp-block-wporg-local-navigation-bar' );
	if ( container ) {
		const onScroll = () => {
			const { top } = container.getBoundingClientRect();
			if ( top <= 32 ) {
				container.classList.add( 'is-sticking' );
			} else {
				container.classList.remove( 'is-sticking' );
			}
		};

		document.addEventListener( 'scroll', debounce( onScroll ), { passive: true } );
		onScroll();
	}
}
window.addEventListener( 'load', init );