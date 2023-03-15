/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';

const ALLOWED_BLOCKS = [ 'wporg/site-breadcrumbs', 'core/navigation' ];

function Edit() {
	return (
		<div { ...useBlockProps() }>
			<InnerBlocks allowedBlocks={ ALLOWED_BLOCKS } />
		</div>
	);
}

function Save() {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: Save,
} );
