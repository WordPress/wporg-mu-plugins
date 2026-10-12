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
 * Browsers that have `ClipboardItem` but don't accept promise values (Chromium
 * before 133) throw synchronously, so fall back to `writeText` in that case.
 *
 * @param {Promise<string>} textPromise A promise resolving to the text to copy.
 * @return {Promise<void>}
 */
function writeToClipboard( textPromise ) {
	if ( window.ClipboardItem && navigator.clipboard?.write ) {
		try {
			const item = new window.ClipboardItem( {
				'text/plain': textPromise.then( ( text ) => new Blob( [ text ], { type: 'text/plain' } ) ),
			} );
			return navigator.clipboard.write( [ item ] );
		} catch {
			// Fall through to writeText.
		}
	}

	return textPromise.then( ( text ) => navigator.clipboard.writeText( text ) );
}

/**
 * Update the block state for a given status.
 *
 * Everything bound in the markup lives in context (not derived state) so that
 * the server can render the initial values when it processes the directives.
 *
 * @param {Object} context The block context.
 * @param {string} status  One of 'idle', 'loading', 'copied' or 'error'.
 */
function setStatus( context, status ) {
	const { label } = context;
	context.status = status;
	context.isLoading = 'loading' === status;
	context.isCopied = 'copied' === status;
	context.isError = 'error' === status;

	if ( 'copied' === status ) {
		context.labelText = label.copied;
		context.ariaLabel = label.copiedA11y;
	} else if ( 'error' === status ) {
		context.labelText = label.error;
		context.ariaLabel = label.errorA11y;
	} else {
		context.labelText = label.copy;
		context.ariaLabel = label.copyA11y;
	}
}

store( 'wporg/copy-page', {
	actions: {
		*copy() {
			const context = getContext();
			if ( context.isLoading ) {
				return;
			}

			clearTimeout( resetTimers.get( context ) );
			setStatus( context, 'loading' );

			try {
				yield writeToClipboard( fetchMarkdown( context.url ) );
				setStatus( context, 'copied' );
				window.wp?.a11y?.speak( context.label.copiedA11y, 'polite' );
			} catch {
				setStatus( context, 'error' );
				window.wp?.a11y?.speak( context.label.errorA11y, 'assertive' );
			}

			resetTimers.set(
				context,
				setTimeout( () => setStatus( context, 'idle' ), RESET_DELAY )
			);
		},
	},
} );
