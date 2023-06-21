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
				<InnerBlocks
					templateLock={ false }
					template={ [
						[
							'core/navigation',
							{ overlayMenu: 'never', layout: { type: 'flex', orientation: 'vertical' } },
						],
					] }
				/>
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
