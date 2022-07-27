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

function Block( { link, previewLink, version, caption } ) {
	const wrapperRef = useRef();
	const [ frameHeight, setFrameHeight ] = useState( '1px' );
	const isVisible = useInView( { element: wrapperRef } );
	const [ shouldLoad, setShouldLoad ] = useState( false );

	useEffect( () => {
		const handleOnResize = () => {
			try {
				setFrameHeight(
					getCardFrameHeight( wrapperRef.current.clientWidth )
				);
			} catch ( err ) {}
		};

		handleOnResize();

		window.addEventListener( 'resize', handleOnResize );

		return () => {
			window.addEventListener( 'resize', handleOnResize );
		};
	}, [ isVisible ] );

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
			} }
			href={ link }
		>
			{ caption && (
				<span className="screen-reader-text">{ caption }</span>
			) }
			<ScreenShot
				src={ `${ previewLink }&version=${ version }` }
				isReady={ shouldLoad }
			/>
		</a>
	);
}

export default Block;
