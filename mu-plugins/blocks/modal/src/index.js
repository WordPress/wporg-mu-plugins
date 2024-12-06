/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, RichText, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './style.scss';

function Edit( { attributes, setAttributes } ) {
	return (
		<div { ...useBlockProps() }>
			 <RichText
				tagName="button"
				value={ attributes.label }
				onChange={ ( label ) => setAttributes( { label } ) }
				placeholder={ __( 'Open modal', 'wporg' ) }
			/>
			<InnerBlocks template={ [ [ 'core/paragraph' ] ] } />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => {
		return <InnerBlocks.Content />;
	}
} );
