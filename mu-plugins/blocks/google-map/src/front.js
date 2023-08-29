/* global wporgGoogleMap */

/**
 * External dependencies
 */
import { pick } from 'lodash';

/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Map from './components/map';
import List from './components/list';

const init = () => {
	const wrapper = document.getElementById( wporgGoogleMap.id );

	if ( ! wrapper ) {
		throw "Map container element isn't present in the DOM.";
	}

	const root = createRoot( wrapper );
	const mapArgs = pick( wporgGoogleMap, [ 'apiKey', 'markers', 'icon' ] );
	const listArgs = pick( wporgGoogleMap, [ 'markers' ] );

	root.render(
		<>
			{ wporgGoogleMap.showMap && <Map { ...mapArgs } /> }

			{ wporgGoogleMap.showList && <List { ...listArgs } /> }
		</>
	);
};

document.addEventListener( 'DOMContentLoaded', init );
