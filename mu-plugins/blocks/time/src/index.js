/* global moment */

/**
 * WordPress dependencies
 */
import { getTextContent, registerFormatType, slice, toggleFormat } from '@wordpress/rich-text';
import { RichTextToolbarButton } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';

const { name, icon, title } = metadata;

const Edit = ( { isActive, onChange, value } ) => (
	<RichTextToolbarButton
		icon={ icon }
		title={ title }
		onClick={ () => {
			const dateDescription = getTextContent( slice( value ) ); // eg. Tuesday, April 5th, at 15:00 UTC

			if ( ! dateDescription ) {
				onChange(
					toggleFormat( value, {
						type: name,
					} )
				);

				return;
			}

			const cleanString = dateDescription.replace( 'at ', '' );

			// Parse the cleaned string into a Moment object in UTC
			const momentDate = moment.utc( cleanString, 'dddd, MMMM Do, HH:mm z' );

			// Format the Moment object in the desired output format
			const formatted = momentDate.isValid() ? momentDate.format( 'YYYY-MM-DDTHH:mm:ssZ' ) : null;

			onChange(
				toggleFormat( value, {
					type: name,
					attributes: formatted ? { datetime: formatted } : {},
				} )
			);
		} }
		isActive={ isActive }
	/>
);

registerFormatType( name, {
	title: title,
	tagName: 'time',
	className: 'wporg-time',
	edit: Edit,
} );
