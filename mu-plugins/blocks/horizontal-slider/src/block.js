/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ScreenshotPreview from '../../screenshot-preview/src/block';
import Handle from './handle';

/**
 * Module constants
 */
const CARD_WIDTH = 124;

function Block( { items, title } ) {
	const outerRef = useRef();
	const [ scrollLeftPos, setScrollLeftPos ] = useState( 0 );
	const [ canPrevious, setCanPrevious ] = useState( false );
	const [ canNext, setCanNext ] = useState( true );

	const totalContainerWidth = items.length * CARD_WIDTH;
	const setWidth = CARD_WIDTH * 3;

	const scrollContainer = ( pos ) => {
		outerRef.current.scrollTo( {
			left: pos,
			behavior: 'smooth',
		} );
	};

	const handlePrev = () => {
		if ( ! canPrevious ) {
			return;
		}
		setScrollLeftPos( outerRef.current.scrollLeft - setWidth );
	};

	const handleNext = () => {
		if ( ! canNext ) {
			return;
		}
		setScrollLeftPos( outerRef.current.scrollLeft + setWidth );
	};

	useEffect( () => {
		scrollContainer( scrollLeftPos );
		setCanPrevious( scrollLeftPos > 0 );
		setCanNext( scrollLeftPos < totalContainerWidth );
	}, [ scrollLeftPos ] );

	return (
		<div>
			<div className="horizontal-slider-header">
				<span>
					<h3 className="horizontal-slider-title">{ title }</h3>
				</span>
				<span className="horizontal-slider-controls">
					<Handle disabled={ ! canPrevious } onClick={ handlePrev }>
						{ __( 'Previous', 'wporg' ) }
					</Handle>
					<Handle disabled={ ! canNext } onClick={ handleNext }>
						{ __( 'Next', 'wporg' ) }
					</Handle>
				</span>
			</div>
			<div className="horizontal-slider-wrapper" ref={ outerRef }>
				{ items.map( ( item ) => (
					<div
						key={ item.title }
						style={ {
							width: `${ CARD_WIDTH }px`,
						} }
					>
						<ScreenshotPreview { ...item } />
					</div>
				) ) }
			</div>
		</div>
	);
}

export default Block;
