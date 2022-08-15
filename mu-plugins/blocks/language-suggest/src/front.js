const init = () => {
	const container = document.querySelector( '.wp-block-wporg-language-suggest' );

	if ( ! container ) {
		return;
	}

	fetch( `https://wordpress.org/lang-guess/lang-guess-ajax.php?uri=${ encodeURIComponent( window.location.pathname ) }` )
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
