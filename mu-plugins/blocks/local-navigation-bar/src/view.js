function debounce( callback ) {
	// This holds the requestAnimationFrame reference, so we can cancel it if we wish
	let frame;

	// The debounce function returns a new function that can receive a variable number of arguments
	return ( ...params ) => {
		// If the frame variable has been defined, clear it now, and queue for next frame
		if ( frame ) {
			window.cancelAnimationFrame( frame );
		}

		// Queue our function call for the next frame
		frame = window.requestAnimationFrame( () => {
			// Call our function and pass any params we received
			callback( ...params );
		} );
	};
}

function init() {
	const container = document.querySelector( '.wp-block-wporg-local-navigation-bar' );
	// The div will hit the "sticky" position when the top offset is 0, or if
	// the admin bar exists, 32px (height of admin bar). The bar unstickies
	// on smaller screens, so the admin bar height change does not affect this.
	const topOffset = document.body.classList.contains( 'admin-bar' ) ? 32 : 0;
	if ( container ) {
		const onScroll = () => {
			const { top } = container.getBoundingClientRect();

			if ( top <= topOffset ) {
				container.classList.add( 'is-sticking' );
			} else {
				container.classList.remove( 'is-sticking' );
			}
		};

		document.addEventListener( 'scroll', debounce( onScroll ), { passive: true } );
		onScroll();

		// Check the size of child elements to determine if the local navigation
		// menu should be collapsed in mobile-view by default. If so, toggle a
		// CSS class to show the nav block with {"overlayMenu":"always"}
		// added by `add_extra_navigation`.
		const onResize = () => {
			const navElement = container.querySelector( 'nav:not(.wporg-is-collapsed-nav)' );

			// Bail early on small screens, the visible nav block is already mobile.
			if ( window.innerWidth < 600 ) {
				container.classList.remove( 'wporg-hide-page-title', 'wporg-show-collapsed-nav' );
				navElement.classList.add( 'wporg-is-mobile-nav' );
				return;
			}

			navElement.classList.remove( 'wporg-is-mobile-nav' );

			// Fetch the navWidth from a data value which is set on page load,
			// so that the uncollapsed visible menu's width is used.
			let navWidth = container.dataset.navWidth;
			if ( ! navWidth ) {
				const navGap = parseInt( window.getComputedStyle( navElement ).gap, 10 ) || 20;
				// Get the nav width based on items, so that it stays
				// consistent even if the menu wraps to a new line.
				const menuItems = navElement.querySelectorAll( '.wp-block-navigation__container > li' );
				navWidth =
					[ ...menuItems ].reduce(
						( acc, current ) => ( acc += current.getBoundingClientRect().width ),
						0
					) +
					navGap * ( menuItems.length - 1 ); // 20px gap between items.

				// Save the value for future resize callbacks.
				container.dataset.navWidth = Math.ceil( navWidth );
			}

			const titleElement = container.querySelector( '.wp-block-site-title, div.wp-block-group' );
			if ( ! titleElement ) {
				return;
			}

			// Get the initial full width, before any elements are hidden.
			let fullTitleWidth = titleElement.dataset.fullWidth;
			if ( ! fullTitleWidth ) {
				// Like navWidth, get this by the individual items to get the
				// non-wrapped width.
				fullTitleWidth =
					[ ...titleElement.children ].reduce(
						( acc, current ) => ( acc += current.getBoundingClientRect().width ),
						0
					) +
					10 * ( titleElement.children.length - 1 ); // 10px margin between items.

				titleElement.dataset.fullWidth = Math.ceil( fullTitleWidth );
			}

			const {
				paddingInlineStart = '0px',
				paddingInlineEnd = '0px',
				gap = '0px',
			} = window.getComputedStyle( container );

			const availableWidth =
				window.innerWidth -
				parseInt( paddingInlineStart, 10 ) -
				parseInt( paddingInlineEnd, 10 ) -
				parseInt( gap, 10 ) -
				20; // 20px right padding is added when the collapsed nav is hidden.

			// If the title area is not a group block, use the same width for
			// short and full (as there is no page title to hide).
			let soloTitleWidth = fullTitleWidth;
			if ( titleElement.classList.contains( 'wp-block-group' ) ) {
				soloTitleWidth = titleElement.children[ 0 ].getBoundingClientRect().width;
			}

			const usedFullWidth = Math.ceil( fullTitleWidth ) + Math.ceil( navWidth );
			const usedShortWidth = Math.ceil( soloTitleWidth ) + Math.ceil( navWidth );

			if ( availableWidth > usedFullWidth ) {
				// Screen is large enough for everything, show all.
				container.classList.remove( 'wporg-show-collapsed-nav', 'wporg-hide-page-title' );
			} else if ( availableWidth > usedShortWidth ) {
				// Menu and title will collide, hide page title.
				container.classList.add( 'wporg-hide-page-title' );
				container.classList.remove( 'wporg-show-collapsed-nav' );
			} else {
				// Menu and title will collide even with only site title, use collapsed nav.
				container.classList.add( 'wporg-hide-page-title', 'wporg-show-collapsed-nav' );
			}
		};

		window.addEventListener( 'resize', debounce( onResize ), { passive: true } );
		onResize();
	}
}
window.addEventListener( 'load', init );
