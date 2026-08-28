/**
 * External dependencies
 */
import debounce from 'lodash.debounce';
const debouncedSpeak = debounce( speak, 1000 );

/**
 * WordPress dependencies
 */
import { speak } from '@wordpress/a11y';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Format the `online` location type for display.
 *
 * @param {string} location
 *
 * @return {string} The formatted location.
 */
export function formatLocation( location ) {
	if ( 'online' === location ) {
		location = 'Online';
	}

	return location;
}

/**
 * Returns a marker link only when its protocol is safe to render as an href.
 *
 * @param {string} url Candidate link target, which comes from the block's attributes.
 *
 * @return {string|undefined} The url, or undefined to render no href.
 */
export function getSafeHref( url ) {
	if ( ! url ) {
		return undefined;
	}

	try {
		const { protocol } = new URL( url, window.location.href );

		return 'https:' === protocol || 'http:' === protocol ? url : undefined;
	} catch ( error ) {
		return undefined;
	}
}

/**
 * Filter the list of markers based on a user's search query.
 *
 * @param {Array}  unfilteredMarkers
 * @param {string} query
 * @param {Array}  searchFields
 */
export function filterMarkers( unfilteredMarkers, query, searchFields ) {
	const filteredMarkers = [];

	if ( '' === query ) {
		return unfilteredMarkers;
	}

	unfilteredMarkers.forEach( ( marker ) => {
		let haystack = searchFields.map( ( field ) => marker[ field ] ).join( ' ' );

		// Sometimes `&nbsp;` is used as a hack to prevent typographic runts. If we don't replace that then a
		// `query` of `los angeles` wouldn't match `los&nbsp;angeles`.
		haystack = haystack.replace( /\u00A0/g, ' ' );

		const match = haystack.search( new RegExp( query, 'i' ) ) >= 0;

		if ( match ) {
			filteredMarkers.push( marker );
		}
	} );

	return filteredMarkers;
}

/**
 * Announce updates to the search results for screen readers.
 *
 * @param {string} query
 * @param {number} foundCount
 */
export function speakSearchUpdates( query, foundCount ) {
	if ( '' === query ) {
		debouncedSpeak( __( 'Search cleared, showing all events.', 'wporg' ) );
	} else if ( foundCount === 0 ) {
		debouncedSpeak(
			// Translators: %s is the search query.
			sprintf( __( 'No events were found matching %s.', 'wporg' ), query )
		);
	} else {
		debouncedSpeak(
			// Translators: %s is the search query.
			sprintf( __( 'Showing events that match %s.', 'wporg' ), query )
		);
	}
}
