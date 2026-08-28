/* global languageSuggestData, Node, DOMParser */

/**
 * Elements the suggestion endpoints are allowed to return.
 *
 * The responses are a single wrapper holding text and language links, so the
 * wrapper has to survive: the block's padding comes from a `> *` child selector.
 */
const ALLOWED_TAGS = [ 'P', 'DIV', 'A' ];

/**
 * Elements whose contents are source text rather than prose.
 *
 * These are dropped whole. Recursing into them would paste the script or style
 * body into the page as visible text.
 */
const SKIPPED_TAGS = [ 'SCRIPT', 'STYLE', 'TEMPLATE', 'NOSCRIPT', 'TITLE', 'TEXTAREA' ];

/**
 * Copies the allowed parts of a parsed node into a node being built.
 *
 * Text is kept verbatim. Disallowed elements are dropped but their text is
 * kept, so a suggestion still reads as a sentence if one link is rejected.
 *
 * @param {Node} source Parsed node to read from.
 * @param {Node} target Node to append the sanitized copy to.
 */
const copyAllowed = ( source, target ) => {
	source.childNodes.forEach( ( node ) => {
		if ( Node.TEXT_NODE === node.nodeType ) {
			target.appendChild( document.createTextNode( node.nodeValue ) );
			return;
		}

		if ( Node.ELEMENT_NODE !== node.nodeType ) {
			return;
		}

		if ( SKIPPED_TAGS.includes( node.tagName ) ) {
			return;
		}

		if ( ! ALLOWED_TAGS.includes( node.tagName ) ) {
			copyAllowed( node, target );
			return;
		}

		const element = document.createElement( node.tagName );

		if ( 'A' === node.tagName ) {
			const value = node.getAttribute( 'href' );
			let href = null;

			if ( value ) {
				try {
					href = new URL( value, window.location.href );
				} catch ( error ) {
					href = null;
				}
			}

			// An anchor without a usable https link contributes its text only.
			if ( ! href || 'https:' !== href.protocol ) {
				copyAllowed( node, target );
				return;
			}

			element.setAttribute( 'href', href.toString() );
		}

		// The legacy network endpoint wraps its notice in a separately styled ID.
		if ( 'lang-guess' === node.getAttribute( 'id' ) ) {
			element.setAttribute( 'id', 'lang-guess' );
		}

		copyAllowed( node, element );
		target.appendChild( element );
	} );
};

/**
 * Rebuilds a suggestion response out of nodes this block is willing to render.
 *
 * @param {string} html Response body.
 *
 * @return {DocumentFragment} The sanitized markup.
 */
const sanitize = ( html ) => {
	const parsed = new DOMParser().parseFromString( html, 'text/html' );
	const fragment = document.createDocumentFragment();

	copyAllowed( parsed.body, fragment );

	return fragment;
};

const init = () => {
	const container = document.querySelector( '.wp-block-wporg-language-suggest' );

	if ( ! container ) {
		return;
	}

	// Resolved server-side, never from the DOM; the literal only covers pages cached before that existed.
	const endpoint = new URL(
		languageSuggestData.endpoint || 'https://wordpress.org/lang-guess/lang-guess-ajax.php'
	);
	endpoint.searchParams.set( 'uri', window.location.pathname );
	endpoint.searchParams.set( 'locale', languageSuggestData.locale );

	fetch( endpoint )
		.then( ( response ) => {
			if ( ! response.ok ) {
				throw Error( response.statusText );
			}

			return response.text();
		} )
		.then( ( body ) => {
			container.replaceChildren( sanitize( body ) );
		} )
		.catch( () => {} );
};

document.addEventListener( 'DOMContentLoaded', init );
