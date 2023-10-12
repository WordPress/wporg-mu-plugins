/* global google */

/**
 * External dependencies
 */
import { MarkerClusterer } from '@googlemaps/markerclusterer';

/**
 * Internal dependencies
 */
import MarkerContent from '../components/marker-content';
import getElementHTML from '../utilities/dom';

/**
 * Return validated markers.
 *
 * Any markers that have invalid data will be removed.
 *
 * @param {Array} markers
 *
 * @return {Array}
 */
export function getValidMarkers( markers ) {
	markers = markers.map( ( marker ) => {
		marker.latitude = parseFloat( marker.latitude );
		marker.longitude = parseFloat( marker.longitude );

		return marker;
	} );

	markers = markers.filter( ( marker ) => {
		return ! Number.isNaN( marker.latitude ) && ! Number.isNaN( marker.longitude );
	} );

	return markers;
}

/**
 * Combine markers that share the exact same location into a single marker.
 *
 * @param {Array} rawMarkers
 *
 * @return {Array}
 */
export function combineDuplicateLocations( rawMarkers ) {
	const combinedMarkers = {};

	rawMarkers.forEach( ( rawMarker ) => {
		const index = rawMarker.latitude + '|' + rawMarker.longitude;

		if ( combinedMarkers[ index ] ) {
			const alreadyConvertedIntoContainer = combinedMarkers[ index ].hasOwnProperty( 'events' );

			if ( ! alreadyConvertedIntoContainer ) {
				combinedMarkers[ index ] = {
					type: 'combined',
					events: [ combinedMarkers[ index ] ],
					latitude: rawMarker.latitude,
					longitude: rawMarker.longitude,
				};
			}

			combinedMarkers[ index ].events.push( rawMarker );
		} else {
			combinedMarkers[ index ] = rawMarker;
		}
	} );

	return Object.values( combinedMarkers );
}

/**
 * Create Marker objects and save to references to them on the corresponding event.
 *
 * Creating the markers implicitly adds them to the map. The shared InfoWindow is assigned during creation.
 *
 * @param {google.maps.Map}        map
 * @param {google.maps}            maps
 * @param {google.maps.InfoWindow} infoWindow
 * @param {Array}                  wpEvents
 * @param {Object}                 rawIcon
 */
export function assignMarkerReferences( map, maps, infoWindow, wpEvents, rawIcon ) {
	const icon = {
		url: rawIcon.markerUrl,
		size: new maps.Size( rawIcon.markerHeight, rawIcon.markerWidth ),
		anchor: new maps.Point( 34, rawIcon.markerWidth / 2 ),
		scaledSize: new maps.Size( rawIcon.markerHeight / 2, rawIcon.markerWidth / 2 ),
	};

	wpEvents.forEach( ( wpEvent ) => {
		// Only the combined markers will need new marker refs, the regular ones will still have the ones they got
		// when the map was first loaded.
		if ( wpEvent.markerRef ) {
			return;
		}

		const marker = new maps.Marker( {
			position: {
				lat: parseFloat( wpEvent.latitude ),
				lng: parseFloat( wpEvent.longitude ),
			},
			map: map,
			icon: icon,
		} );

		marker.addListener( 'click', () => {
			openInfoWindow( infoWindow, map, marker, wpEvent );
			panToCenter( [ marker ], map );
		} );

		wpEvent.markerRef = marker;
	} );

	return wpEvents;
}

/**
 * Open an info window for the given marker.
 *
 * A single infoWindow is used for all markers, so that only one is open at a time.
 *
 * @param {google.maps.InfoWindow} infoWindow
 * @param {google.maps.Map}        map
 * @param {google.maps.Marker}     markerObject
 * @param {Array}                  rawMarker
 */
function openInfoWindow( infoWindow, map, markerObject, rawMarker ) {
	infoWindow.setContent( getElementHTML( <MarkerContent { ...rawMarker } /> ) );
	infoWindow.open( {
		anchor: markerObject,
		map: map,
		shouldFocus: false, // Don't steal focus from the search <input>.
	} );
}

/**
 * Cluster the markers into groups for improved performance and UX.
 *
 * @param {google.maps.Map}      map
 * @param {google.maps}          maps
 * @param {google.maps.Marker[]} markers
 * @param {Object}               rawIcon
 *
 * @return {MarkerClusterer}
 */
export function clusterMarkers( map, maps, markers, rawIcon ) {
	const clusterIcon = {
		url: rawIcon.clusterUrl,
		size: new maps.Size( rawIcon.clusterHeight, rawIcon.clusterWidth ),
		anchor: new maps.Point( rawIcon.clusterHeight, rawIcon.clusterWidth ),
		scaledSize: new maps.Size( rawIcon.clusterHeight, rawIcon.clusterWidth ),
	};

	const renderer = {
		render: ( { count, position } ) => {
			return new maps.Marker( {
				label: { text: String( count ), color: 'white', fontSize: '10px' },
				position: position,
				zIndex: Number( maps.Marker.MAX_ZINDEX ) + count, // Show above normal markers.
				icon: clusterIcon,
			} );
		},
	};

	return new MarkerClusterer( { map, markers, renderer } );
}

/**
 * Clear the map and add the given markers.
 *
 * @param {MarkerClusterer}      clusterer
 * @param {google.maps.Marker[]} markers
 */
export function updateMapMarkers( clusterer, markers ) {
	// Prevent re-drawing the map when clearing, because we'll redraw it when adding markers.
	clusterer.clearMarkers( true );
	clusterer.addMarkers( markers );
}

/**
 * Pan the map to the center of the given markers.
 *
 * @param {google.maps.Marker[]} markers
 * @param {google.maps.Map}      map
 * @param {google.maps}          maps
 */
export function panToCenter( markers, map, maps ) {
	if ( markers.length === 0 ) {
		return;
	}

	if ( markers.length === 1 ) {
		map.panTo(
			{
				lat: markers[ 0 ].position.lat(),
				lng: markers[ 0 ].position.lng(),
			},
			1000,
			google.maps.Animation.easeInOut
		);

		return;
	}

	const bounds = new maps.LatLngBounds();

	markers.map( ( marker ) => bounds.extend( marker.position ) );

	map.fitBounds( bounds );
}
