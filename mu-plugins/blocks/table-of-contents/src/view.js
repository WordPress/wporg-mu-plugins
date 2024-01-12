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
}

window.addEventListener( 'load', init );
