/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ScreenShot from '../../screenshot-preview/src/block';
import Handle from './handle';

/**
 * Module constants
 */
const CARD_WIDTH = 100;
const CARD_GAP = 12;
/**
 * The default number of tiles that are advanced on next arrow click.
 */
const SET_WIDTH = CARD_WIDTH * 3;

function Block( { items, title } ) {
	const outerRef = useRef();
	const [ scrollLeftPos, setScrollLeftPos ] = useState( 0 );
	const [ canPrevious, setCanPrevious ] = useState( false );
	const [ canNext, setCanNext ] = useState( true );

	// Calculate to total width of the content
	const totalContainerWidth = items.length * ( CARD_WIDTH + CARD_GAP ) - CARD_GAP;

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
		setScrollLeftPos( outerRef.current.scrollLeft - SET_WIDTH );
	};

	const handleNext = () => {
		if ( ! canNext ) {
			return;
		}
		setScrollLeftPos( outerRef.current.scrollLeft + SET_WIDTH );
	};

	useEffect( () => {
		scrollContainer( scrollLeftPos );
	}, [ scrollLeftPos ] );

	useEffect( () => {
		if ( ! outerRef.current ) {
			return;
		}

		const handleScrollEvent = () => {
			setCanPrevious( outerRef.current.scrollLeft > 0 );
			setCanNext( totalContainerWidth - outerRef.current.scrollLeft > outerRef.current.offsetWidth );
		};

		handleScrollEvent();

		outerRef.current.addEventListener( 'scroll', handleScrollEvent );

		return () => {
			outerRef.current.removeEventListener( 'scroll', handleScrollEvent );
		};
	}, [ outerRef ] );

	return (
		<div>
			<div className="horizontal-slider-header">
				<span>
					<h3 className="horizontal-slider-title">{ title }</h3>
				</span>
				<span className="horizontal-slider-controls">
					<Handle
						text={ __( 'Previous style variations', 'wporg' ) }
						disabled={ ! canPrevious }
						onClick={ handlePrev }
					/>
					<Handle
						text={ __( 'Next style variations', 'wporg' ) }
						disabled={ ! canNext }
						onClick={ handleNext }
					/>
				</span>
			</div>
			<div className="horizontal-slider-wrapper" ref={ outerRef }>
				{ items.map( ( item ) => (
					<ScreenShot key={ item.title } { ...item } width="100px" isReady={ true } />
				) ) }
			</div>
		</div>
	);
}

export default Block;
