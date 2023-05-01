/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
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
				template={ [
					[ 'core/button' ],
					[
						'wporg/modal-inner-content',
						{},
						[
							[
								'core/navigation',
								{ overlayMenu: 'never', layout: { type: 'flex', orientation: 'vertical' } },
							],
						],
					],
				] }
				templateLock="all"
			/>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => {
		return <InnerBlocks.Content />;
	},
	variations: [
		{
			name: 'default',
			title: metadata.title,
			attributes: { type: 'modal' },
			scope: [ 'inserter', 'transform' ],
			isDefault: true,
			isActive: [ 'type' ],
		},
		{
			name: 'inline',
			title: __( 'Collapsed', 'wporg' ),
			attributes: { type: 'inline' },
			scope: [ 'inserter', 'transform' ],
			isActive: [ 'type' ],
		},
		{
			name: 'popover',
			title: __( 'Popover', 'wporg' ),
			attributes: { type: 'popover' },
			scope: [ 'inserter', 'transform' ],
			isActive: [ 'type' ],
		},
	],
} );
