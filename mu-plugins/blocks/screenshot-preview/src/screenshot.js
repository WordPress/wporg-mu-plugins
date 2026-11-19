/* global FileReader */
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * Module constants
 */
const MAX_ATTEMPTS = 10;
const RETRY_DELAY = 2000;

/**
 * Thanks! https://overreacted.io/making-setinterval-declarative-with-react-hooks/.
 *
 * @param {Function} callback
 * @param {number}   delay
 */
function useInterval( callback, delay ) {
	const savedCallback = useRef();

	useEffect( () => {
		savedCallback.current = callback;
	}, [ callback ] );

	useEffect( () => {
		function tick() {
			savedCallback.current();
		}
		if ( delay !== null ) {
			const id = setInterval( tick, delay );
			return () => clearInterval( id );
		}
	}, [ delay ] );
}

/**
 *
 * We are using mShots which is a project that generates a screenshot from a webpage.
 * Since the screenshot generation takes some time, for never seen websites,
 * we need to do some custom handling.
 *
 * @param {Object}  props
 * @param {string}  props.alt         Alternate text for image.
 * @param {string}  props.queryString Arguments that are passed to mShots.
 * @param {string}  props.src         The url of the screenshot.
 * @param {boolean} props.isReady     Whether we should start try to show the image.
 *
 * @return {Object} React component
 */
function ScreenShotImg( { alt = '', queryString, src, isReady = false } ) {
	// queryString comes from a data- attribute, so append it as parsed params rather than raw text.
	const screenshotUrl = new URL( `https://s0.wp.com/mshots/v1/${ encodeURIComponent( src ) }` );
	new URLSearchParams( queryString ).forEach( ( value, key ) => {
		screenshotUrl.searchParams.set( key, value );
	} );
	const fullUrl = screenshotUrl.toString();

	const [ attempts, setAttempts ] = useState( 0 );
	const [ hasLoaded, setHasLoaded ] = useState( false );
	const [ hasError, setHasError ] = useState( false );
	const [ base64Img, setBase64Img ] = useState( '' );
	const [ shouldRetry, setShouldRetry ] = useState( false );

	// We don't want to keep trying infinitely.
	const hasAborted = attempts > MAX_ATTEMPTS;

	// The derived loading state
	const isLoading = isReady && ! hasLoaded && ! hasAborted && ! hasError;

	/**
	 * Since we already made the request, we'll use the response to be frugal.
	 *
	 * @param {string} res
	 */
	const convertResponseToBase64 = async ( res ) => {
		const blob = await res.blob();

		const reader = new FileReader();
		reader.onload = ( event ) => {
			setBase64Img( event.target.result );
		};
		reader.readAsDataURL( blob );
	};

	const fetchImage = async () => {
		try {
			const res = await fetch( fullUrl );

			if ( res.status === 200 && ! res.redirected ) {
				await convertResponseToBase64( res );

				setHasLoaded( true );
				setShouldRetry( false );
			} else {
				// mShots redirects while it generates the image; count each try so we eventually give up.
				setAttempts( ( count ) => count + 1 );
				setShouldRetry( true );
			}
		} catch {
			setHasError( true );
			setShouldRetry( false );
		}
	};

	// Wait until the card is in view, so off-screen previews don't use up their attempts.
	useEffect( () => {
		if ( isReady ) {
			( async () => await fetchImage() )();
		}
	}, [ isReady ] );

	/**
	 * The Snapshot service will redirect when its generating an image.
	 * We want to continue requesting the image until it doesn't redirect.
	 */
	useInterval(
		async () => {
			await fetchImage();
		},
		shouldRetry && ! hasAborted ? RETRY_DELAY : null
	);

	/**
	 * Start over after a failure, for example when mShots took too long.
	 *
	 * @param {Event} event
	 */
	const handleRetry = ( event ) => {
		// The preview sits inside the card's link, so don't follow it.
		event.preventDefault();
		event.stopPropagation();

		setAttempts( 0 );
		setHasError( false );
		setShouldRetry( false );
		fetchImage();
	};

	if ( ! isReady ) {
		return null;
	}

	if ( isLoading ) {
		return <div className="wporg-screenshot wporg-screenshot-loader" />;
	}

	if ( hasError || hasAborted ) {
		return (
			<div className="wporg-screenshot wporg-screenshot__has-error">
				<p>{ __( 'The preview could not be loaded.', 'wporg' ) }</p>
				<button type="button" className="wporg-screenshot__retry" onClick={ handleRetry }>
					{ __( 'Try again', 'wporg' ) }
				</button>
			</div>
		);
	}

	return <img src={ base64Img } alt={ alt } />;
}

export default ScreenShotImg;
