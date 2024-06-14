/* global languageSuggestData */

const init = () => {
	const container = document.querySelector( '.wp-block-wporg-language-suggest' );

	if ( ! container ) {
		return;
	}

	const endpoint = new URL(
		container.dataset.endpoint || 'https://wordpress.org/lang-guess/lang-guess-ajax.php'
	);
	endpoint.searchParams.set( 'uri', encodeURIComponent( window.location.pathname ) );
	endpoint.searchParams.set( 'locale', languageSuggestData.locale );

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
