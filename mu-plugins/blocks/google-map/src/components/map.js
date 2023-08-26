/**
 * External dependencies
 */
import GoogleMapReact from 'google-map-react';

/**
 * WordPress dependencies
 */
import { useCallback, useState } from '@wordpress/element';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { mapStyles } from '../utilities/map-styles';
import { createClusteredMarkers } from '../utilities/google-maps-api';

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
export default function Map( { apiKey, markers, icon } ) {
	const [ loaded, setLoaded ] = useState( false );

	const options = {
		zoomControl: true,
		mapTypeControl: false,
		streetViewControl: false,
		styles: mapStyles,
	};

	const mapLoaded = useCallback( ( { map, maps } ) => {
		createClusteredMarkers( map, maps, markers, icon );
		setLoaded( true );
	}, [] );

	return (
		// Container height must be set explicitly.
		<>
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
		</>
	);
}
