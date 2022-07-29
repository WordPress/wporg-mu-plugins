const init = () => {
	const container = document.querySelector( '.wp-block-wporg-language-suggest' );

	if ( ! container ) {
		return;
	}

	// Construct the URL without protocol
	const path = window.location.host + window.location.pathname;

	fetch( `/lang-guess/lang-guess-ajax.php?uri=${ encodeURIComponent( path ) }` )
		.then( ( response ) => {
			if ( ! response.ok ) {
				throw Error( response.statusText );
			}

			return response.text();
		} )
		.then( ( body ) => ( container.innerHTML = body ) )
		.catch( () => {} );
};

document.addEventListener( 'DOMContentLoaded', init );
