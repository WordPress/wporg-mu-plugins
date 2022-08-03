/**
 * WordPress dependencies
 */
import { createElement, render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Block from './block.js';

const init = () => {
	const blockElements = document.querySelectorAll( '.wporg-multisite-latest-posts-js' );

	if ( ! blockElements ) {
		return;
	}

	for ( let i = 0; i < blockElements.length; i++ ) {
		const blockEl = blockElements[ i ];

		render( createElement( Block, blockEl.dataset ), blockEl );
	}
};

document.addEventListener( 'DOMContentLoaded', init );
