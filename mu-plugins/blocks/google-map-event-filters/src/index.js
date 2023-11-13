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
		<div { ...useBlockProps() }>
			<InnerBlocks
				allowedBlocks={ [ 'wporg/google-map' ] }
				template={ [ [ 'wporg/google-map' ] ] }
				templateLock="all"
			/>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
} );
