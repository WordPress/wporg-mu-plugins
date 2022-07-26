const init = () => {
	const container = document.querySelector( '.wp-block-wporg-language-suggest' );

	if ( ! container ) {
		return;
	}

	// Construct the URL without protocol
	const path = window.location.host + window.location.pathname;

	fetch( `/lang-guess/lang-guess-ajax.php?uri=${ encodeURIComponent( path ) }` )
		.then( ( response ) => response.text() )
		.then( ( body ) => container.innerHTML = body );
};

document.addEventListener( 'DOMContentLoaded', init );
