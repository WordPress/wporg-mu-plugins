/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import { useCallback, useEffect, useRef } from '@wordpress/element';
import { getTextContent, registerFormatType, slice, toggleFormat } from '@wordpress/rich-text';

/**
 * External dependencies
 */
import { gmdate, strtotime } from 'locutus/php/datetime';

/**
 * Internal dependencies
 */
import metadata from './block.json';

const { name, icon, title } = metadata;

// Return the first time description from the content, if present.
const getTimeFromContent = ( content ) => {
	const tempElement = document.createElement( 'div' );
	tempElement.innerHTML = content;

	const wporgTimeElement = tempElement.querySelector( '.wporg-time' );

	return wporgTimeElement ? wporgTimeElement.textContent : null;
};

const Edit = ( { isActive, onChange, value } ) => {
	const { date_gmt } = useSelect( ( select ) => select( 'core/editor' ).getCurrentPost() );
	const {
		attributes: { content },
	} = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getSelectedBlock();
	}, [] );
	const nextTime = getTimeFromContent( content );
	const timeRef = useRef( nextTime );

	const toggleWithoutEnhancing = useCallback( () => {
		onChange(
			toggleFormat( value, {
				type: name,
			} )
		);
	} );

	// If the timeRef description changes, toggle the format off.
	useEffect( () => {
		if ( timeRef.current && nextTime && timeRef.current !== nextTime ) {
			toggleWithoutEnhancing();
		}

		timeRef.current = nextTime;
	}, [ nextTime, timeRef ] );

	return (
		<RichTextToolbarButton
			icon={ icon }
			title={ title }
			onClick={ () => {
				const dateDescription = getTextContent( slice( value ) );

				if ( ! dateDescription || isActive ) {
					toggleWithoutEnhancing();

					return;
				}

				// Remove the word "at" from the string, if present.
				// Allows strings like "Monday, April 6 at 19:00 UTC" to work.
				const dateCleaned = dateDescription.replace( 'at ', '' );

				// strtotime understands "GMT" better than "UTC" for timezones.
				dateCleaned.replace( 'UTC', 'GMT' );

				// Try to parse the time, relative to the post time.
				const postTimestamp = strtotime( date_gmt );
				const time = strtotime( dateCleaned, postTimestamp );

				// If that didn't work, give up.
				if ( false === time || -1 === time ) {
					toggleWithoutEnhancing();

					return;
				}

				const datetime = gmdate( 'c', time );
				const datetimeISO = gmdate( 'Ymd\\THi', time );

				onChange(
					toggleFormat( value, {
						type: name,
						attributes: {
							datetime: datetime,
							'data-iso': datetimeISO,
						},
					} )
				);
			} }
			isActive={ isActive }
		/>
	);
};

registerFormatType( name, {
	title: title,
	tagName: 'time',
	className: 'wporg-time',
	edit: Edit,
} );
