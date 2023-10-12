/**
 * External dependencies
 */
import GoogleMapReact from 'google-map-react';

/**
 * WordPress dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { mapStyles } from '../utilities/map-styles';
import {
	assignMarkerReferences,
	clusterMarkers,
	combineDuplicateLocations,
	panToCenter,
	updateMapMarkers,
} from '../utilities/google-maps-api';

/**
 * Render a Google Map with info windows for the given markers.
 *
 * @see https://github.com/google-map-react/google-map-react#use-google-maps-api
 *
 * @param {Object} props
 * @param {string} props.apiKey
 * @param {Array}  props.markers
 * @param {Object} props.icon
 *
 * @return {JSX.Element}
 */
export default function Map( { apiKey, markers: rawMarkers, icon } ) {
	const [ loaded, setLoaded ] = useState( false );
	const [ clusterer, setClusterer ] = useState( null );
	const [ googleMap, setGoogleMap ] = useState( null );
	const [ googleMapsApi, setGoogleMapsApi ] = useState( null );
	const infoWindow = useRef( null );
	let combinedMarkers = [];

	const options = {
		zoomControl: true,
		mapTypeControl: false,
		streetViewControl: false,
		styles: mapStyles,
	};

	/**
	 * Add markers to the map and cluster them.
	 *
	 * Callback for `onGoogleApiLoaded`.
	 */
	const mapLoaded = useCallback( ( { map, maps } ) => {
		if ( ! map || ! maps ) {
			throw 'Google Maps API is not loaded.';
		}

		setGoogleMap( map );
		setGoogleMapsApi( maps );

		infoWindow.current = new maps.InfoWindow( {
			pixelOffset: new maps.Size( -icon.markerIconAnchorXOffset, 0 ),
		} );

		combinedMarkers = combineDuplicateLocations( rawMarkers );
		combinedMarkers = assignMarkerReferences( map, maps, infoWindow.current, combinedMarkers, icon );

		setClusterer(
			clusterMarkers(
				map,
				maps,
				combinedMarkers.map( ( marker ) => marker.markerRef ),
				icon
			)
		);

		setLoaded( true );
	}, [] );

	/**
	 * Update the map whenever the supplied markers change.
	 */
	useEffect( () => {
		if ( ! clusterer ) {
			return;
		}

		infoWindow.current.close();

		combinedMarkers = combineDuplicateLocations( rawMarkers );
		combinedMarkers = assignMarkerReferences(
			googleMap,
			googleMapsApi,
			infoWindow.current,
			combinedMarkers,
			icon
		);

		const markerObjects = combinedMarkers.map( ( marker ) => marker.markerRef );

		updateMapMarkers( clusterer, markerObjects, googleMap );
		panToCenter( markerObjects, googleMap, googleMapsApi );
	}, [ clusterer, rawMarkers ] );

	return (
		<div className="wporg-google-map__container">
			{ ! loaded && <Spinner /> }

			<GoogleMapReact
				defaultZoom={ 1 }
				defaultCenter={ {
					lat: 30.0,
					lng: 10.0,
				} }
				bootstrapURLKeys={ { key: apiKey } }
				yesIWantToUseGoogleMapApiInternals
				onGoogleApiLoaded={ mapLoaded }
				options={ options }
			/>
		</div>
	);
}
