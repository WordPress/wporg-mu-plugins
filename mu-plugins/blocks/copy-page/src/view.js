/**
 * WordPress dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

/**
 * How long the "Copied" / "Copy failed" state stays visible, in milliseconds.
 */
const RESET_DELAY = 3000;

/**
 * Pending reset timers, keyed by block context, so a re-click can cancel the previous one.
 */
const resetTimers = new WeakMap();

/**
 * Fetch the Markdown rendering of the page.
 *
 * @param {string} url The Markdown URL.
 * @return {Promise<string>} The Markdown text.
 */
async function fetchMarkdown( url ) {
	const response = await fetch( url, {
		credentials: 'same-origin',
		headers: { Accept: 'text/markdown' },
	} );

	if ( ! response.ok ) {
		throw new Error( `Request failed with status ${ response.status }` );
	}

	// Never put an HTML page on the clipboard, for example when the site does not serve Markdown.
	const contentType = response.headers.get( 'content-type' ) || '';
	if ( ! contentType.startsWith( 'text/markdown' ) ) {
		throw new Error( `Unexpected content type: ${ contentType }` );
	}

	return response.text();
}

/**
 * Write text to the clipboard.
 *
 * Safari only allows clipboard writes while the user gesture is active, which
 * is over by the time the fetch resolves. Passing a promise to `ClipboardItem`
 * keeps the write tied to the click, so prefer that where it's supported.
 * Chromium 76–97 has `ClipboardItem` but only accepts a `Blob`, not a promise,
 * and throws synchronously from the constructor; fall back to `writeText` there.
 *
 * @param {Promise<string>} textPromise A promise resolving to the text to copy.
 * @return {Promise<void>}
 */
function writeToClipboard( textPromise ) {
	if ( window.ClipboardItem && navigator.clipboard?.write ) {
		const blobPromise = textPromise.then( ( text ) => new Blob( [ text ], { type: 'text/plain' } ) );
		// If the constructor throws, nothing consumes this promise; a later fetch failure
		// is still reported through write() or the writeText fallback.
		blobPromise.catch( () => {} );

		try {
			const item = new window.ClipboardItem( { 'text/plain': blobPromise } );
			return navigator.clipboard.write( [ item ] );
		} catch {
			// Fall through to writeText.
		}
	}

	return textPromise.then( ( text ) => navigator.clipboard.writeText( text ) );
}

/**
 * Map a copy status to the key of the label shown for it.
 *
 * @param {string} status One of 'idle', 'loading', 'copied' or 'error'.
 * @return {string} 'copy', 'copied' or 'error'.
 */
function getLabelKey( status ) {
	return [ 'copied', 'error' ].includes( status ) ? status : 'copy';
}

/**
 * Set the copy status and schedule the reset to idle.
 *
 * @param {Object} context The block context.
 * @param {string} status  One of 'copied' or 'error'.
 */
function finish( context, status ) {
	context.status = status;
	resetTimers.set(
		context,
		setTimeout( () => {
			context.status = 'idle';
		}, RESET_DELAY )
	);
}

store( 'wporg/copy-page', {
	// Derived from `context.status`; the server renders the same values from render.php.
	state: {
		get isLoading() {
			return 'loading' === getContext().status;
		},
		get isCopied() {
			return 'copied' === getContext().status;
		},
		get isError() {
			return 'error' === getContext().status;
		},
		get labelText() {
			const { status, label } = getContext();
			return label[ getLabelKey( status ) ];
		},
		get ariaLabel() {
			const { status, label } = getContext();
			return label[ getLabelKey( status ) + 'A11y' ];
		},
	},
	actions: {
		*copy() {
			const context = getContext();
			if ( 'loading' === context.status ) {
				return;
			}

			clearTimeout( resetTimers.get( context ) );
			context.status = 'loading';

			try {
				yield writeToClipboard( fetchMarkdown( context.url ) );
				finish( context, 'copied' );
				window.wp?.a11y?.speak( context.label.copiedA11y, 'polite' );
			} catch {
				finish( context, 'error' );
				window.wp?.a11y?.speak( context.label.errorA11y, 'assertive' );
			}
		},
	},
} );
