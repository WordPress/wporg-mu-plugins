/* global wporgGoogleMap */

/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Main from './components/main';

const init = () => {
	const containers = document.querySelectorAll( '.wp-block-wporg-google-map' );

	if ( ! containers.length ) {
		throw "Map container element isn't present in the DOM.";
	}

	let root;

	for ( const container of containers ) {
		root = createRoot( container );

		root.render( <Main { ...wporgGoogleMap[ container.dataset.mapId ] } /> );
	}
};

document.addEventListener( 'DOMContentLoaded', init );
