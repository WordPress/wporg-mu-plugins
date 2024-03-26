const init = () => {
	const container = document.querySelector( '.wp-block-wporg-language-suggest' );

	if ( ! container ) {
		return;
	}

	const endpoint =
		container.dataset.endpoint ||
		'https://wordpress.org/lang-guess/lang-guess-ajax.php?uri=' + encodeURIComponent( window.location.pathname );

	fetch( endpoint )
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
