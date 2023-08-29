/**
 * Format the `online` location type for display.
 *
 * @param {string} location
 *
 * @return {string}
 */
export function formatLocation( location ) {
	if ( 'online' === location ) {
		location = 'Online';
	}

	return location;
}
