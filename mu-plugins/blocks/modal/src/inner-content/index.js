/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';

function Edit() {
	return (
		<div className="wporg-modal__modal">
			<div { ...useBlockProps() }>
				<InnerBlocks templateLock={ false } />
			</div>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => {
		return <InnerBlocks.Content />;
	},
} );
