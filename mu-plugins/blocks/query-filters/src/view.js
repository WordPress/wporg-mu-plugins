/**
 * WordPress dependencies
 */
import { store } from '@wordpress/interactivity';

store( {
	actions: {
		wporg: {
			'query-filter': {
				toggle: ( { context, ref } ) => {
					if ( ref.dataset.wporgModalTarget ) {
						Object.entries( context.wporg[ 'query-filter' ].isOpen ).forEach( ( [ key, val ] ) => {
							context.wporg[ 'query-filter' ].isOpen[ key ] =
								key === ref.dataset.wporgModalTarget && ! val;
						} );
					}
				},
			},
		},
	},
	effects: {
		wporg: {
			'query-filter': {
				// logIsOpen: ( { context } ) => {},
			},
		},
	},
} );
