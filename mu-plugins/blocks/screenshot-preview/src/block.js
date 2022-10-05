/**
 * WordPress dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import getCardFrameHeight from './get-card-frame-height';
import useInView from './in-view';
import ScreenShot from './screenshot';

/**
 * Module constants
 */

function Block( {
	link,
	previewLink,
	caption,
	height = '1px',
	width = '100%',
	aspectRatio = 2 / 3,
	queryString = '?vpw=1200&vph=800',
} ) {
	const wrapperRef = useRef();
	const [ frameHeight, setFrameHeight ] = useState( height );
	const isVisible = useInView( { element: wrapperRef } );
	const [ shouldLoad, setShouldLoad ] = useState( false );

	useEffect( () => {
		const handleOnResize = () => {
			try {
				setFrameHeight( getCardFrameHeight( wrapperRef.current.clientWidth, aspectRatio ) );
			} catch ( err ) {}
		};

		handleOnResize();

		window.addEventListener( 'resize', handleOnResize );

		return () => {
			window.removeEventListener( 'resize', handleOnResize );
		};
	}, [ shouldLoad ] );

	useEffect( () => {
		if ( isVisible ) {
			setShouldLoad( true );
		}
	}, [ isVisible ] );

	return (
		<a
			className="wporg-screenshot-card"
			ref={ wrapperRef }
			style={ {
				height: frameHeight,
				width: width,
			} }
			href={ link }
		>
			{ caption && <span className="screen-reader-text">{ caption }</span> }
			<ScreenShot queryString={ queryString } src={ previewLink } isReady={ shouldLoad } />
		</a>
	);
}

export default Block;
