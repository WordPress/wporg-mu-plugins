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
 * body into the page as visible text. Compared in lower case because foreign
 * content keeps the authored casing, so `<svg><script>` is not `SCRIPT`.
 */
const SKIPPED_TAGS = [
	'script',
	'style',
	'template',
	'noscript',
	'title',
	'textarea',
	'iframe',
	'xmp',
	'plaintext',
	'noembed',
	'noframes',
];

/**
 * Returns a link target only when it is safe to render as an href.
 *
 * @param {string|null} value The parsed anchor's href attribute.
 *
 * @return {string|null} The resolved url, or null to drop the anchor.
 */
const getSafeHref = ( value ) => {
	if ( ! value ) {
		return null;
	}

	try {
		const url = new URL( value, window.location.href );

		return 'https:' === url.protocol ? url.toString() : null;
	} catch ( error ) {
		return null;
	}
};

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

		if ( SKIPPED_TAGS.includes( node.tagName.toLowerCase() ) ) {
			return;
		}

		// An anchor is only kept when it carries a usable https link.
		const href = 'A' === node.tagName ? getSafeHref( node.getAttribute( 'href' ) ) : null;
		const keep = ALLOWED_TAGS.includes( node.tagName ) && ( 'A' !== node.tagName || href );

		// Anything not kept contributes its text, so a suggestion still reads as a sentence.
		if ( ! keep ) {
			copyAllowed( node, target );
			return;
		}

		const element = document.createElement( node.tagName );

		if ( href ) {
			element.setAttribute( 'href', href );
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
