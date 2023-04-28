/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { InnerBlocks, RichText, useBlockProps, store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';

function Edit( { attributes, setAttributes, isSelected, clientId } ) {
	const { label } = attributes;
	const isInnerBlockSelected = useSelect(
		( select ) => select( blockEditorStore ).hasSelectedInnerBlock( clientId, true ),
		[ clientId ]
	);

	const onChangeContent = ( newValue ) => {
		setAttributes( { label: newValue } );
	};
	const modalClass = isSelected || isInnerBlockSelected ? ' is-open' : '';

	return (
		<div { ...useBlockProps() }>
			<RichText tagName="p" className="wp-block-button__link" onChange={ onChangeContent } value={ label } />
			<div className={ `wporg-modal__modal ${ modalClass }` }>
				<InnerBlocks
					template={ [
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
