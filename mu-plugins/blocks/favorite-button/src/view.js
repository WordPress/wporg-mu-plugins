/**
 * WordPress dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

store( 'wporg/favorite-button', {
	state: {
		get labelAction() {
			const { label, isFavorite } = getContext();
			return isFavorite ? label.remove : label.add;
		},
		get labelCount() {
			const { count } = getContext();
			return `${ count }`;
		},
		get labelScreenReader() {
			const { label, count } = getContext();
			return label.screenReader.replace( '%s', count );
		},
	},
	actions: {
		*triggerAction() {
			const context = getContext();
			context.isLoading = true;

			if ( context.isFavorite ) {
				try {
					const result = yield wp.apiFetch( {
						path: '/wporg/v1/favorite',
						method: 'DELETE',
						data: { id: context.id },
					} );
					if ( 'number' === typeof result ) {
						context.count = result;
					}
					context.isFavorite = false;
					wp.a11y.speak( context.label.unfavorited, 'polite' );
				} catch ( error ) {}
			} else {
				try {
					const result = yield wp.apiFetch( {
						path: '/wporg/v1/favorite',
						method: 'POST',
						data: { id: context.id },
					} );
					if ( 'number' === typeof result ) {
						context.count = result;
					}
					context.isFavorite = true;
					wp.a11y.speak( context.label.favorited, 'polite' );
				} catch ( error ) {}
			}

			context.isLoading = false;
		},
	},
} );
