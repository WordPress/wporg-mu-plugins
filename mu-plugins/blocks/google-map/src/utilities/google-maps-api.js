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
 * Add markers and their info windows to the map.
 *
 * Callback for `GoogleMapReact.onGoogleApiLoaded`.
 *
 * @param {google.maps.Map} map
 * @param {google.maps}     maps
 * @param {Array}           wpEvents
 * @param {Object}          rawIcon
 */
export function createClusteredMarkers( map, maps, wpEvents, rawIcon ) {
	if ( 'undefined' === typeof google || ! google.hasOwnProperty( 'maps' ) ) {
		throw 'Google Maps library is not loaded';
	}

	const markers = [];

	const infoWindow = new google.maps.InfoWindow( {
		pixelOffset: new google.maps.Size( -rawIcon.markerIconAnchorXOffset, 0 ),
	} );

	const icon = {
		url: rawIcon.markerUrl,
		size: new maps.Size( rawIcon.markerHeight, rawIcon.markerWidth ),
		anchor: new maps.Point( 34, rawIcon.markerWidth / 2 ),
		scaledSize: new maps.Size( rawIcon.markerHeight / 2, rawIcon.markerWidth / 2 ),
	};

	wpEvents.forEach( ( wpEvent ) => {
		const marker = new maps.Marker( {
			position: {
				lat: parseFloat( wpEvent.latitude ),
				lng: parseFloat( wpEvent.longitude ),
			},
			map: map,
			icon: icon,
		} );

		marker.addListener( 'click', () => openInfoWindow( infoWindow, map, marker, wpEvent ) );

		markers.push( marker );
	} );

	clusterMarkers( map, markers, rawIcon );
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
	infoWindow.open( map, markerObject );

	map.panTo(
		{
			lat: markerObject.position.lat(),
			lng: markerObject.position.lng(),
		},
		1000,
		google.maps.Animation.easeInOut
	);
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
function clusterMarkers( map, markers, rawIcon ) {
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
