/**
 * WordPress dependencies
 */
import { registerFormatType, toggleFormat } from '@wordpress/rich-text';
import { RichTextToolbarButton } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';

const Edit = ( { isActive, onChange, value } ) => (
	<RichTextToolbarButton
		icon={ metadata.icon }
		title={ metadata.title }
		onClick={ () => {
			onChange(
				toggleFormat( value, {
					type: metadata.name,
				} )
			);
		} }
		isActive={ isActive }
	/>
);

registerFormatType( metadata.name, {
	title: metadata.title,
	tagName: 'time',
	className: 'wporg-time',
	edit: Edit,
} );
