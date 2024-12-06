/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, InnerBlocks, RichText, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { registerBlockType } from '@wordpress/blocks';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './editor.scss';
import './style.scss';

function Edit( { attributes, setAttributes } ) {
	const [ isModalPreview, setIsModalPreview ] = useState( false );
	const classes = [];
	if ( isModalPreview ) {
		classes.push( 'is-modal-open' );
	}
	return (
		<>
			<InspectorControls key="modal-preview">
				<PanelBody title={ __( 'Settings', 'wporg' ) } initialOpen={ true }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show modal', 'wporg' ) }
						help={ __( 'Open modal for editing the contents.', 'wporg' ) }
						checked={ isModalPreview }
						onChange={ ( newValue ) => {
							setIsModalPreview( newValue );
						} }
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'URL', 'wporg' ) }
						help={ __(
							'Link to a zip file, or shortcode to generate a URL. The modal will appear while the download happens.',
							'wporg'
						) }
						value={ attributes.href }
						onChange={ ( href ) => setAttributes( { href } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: classes } ) }>
				<div className="wp-block-buttons">
					<div className="wp-block-button">
						<RichText
							tagName="button"
							className="wp-block-button__link"
							value={ attributes.label }
							onChange={ ( label ) => setAttributes( { label } ) }
							placeholder={ __( 'Open modal', 'wporg' ) }
						/>
					</div>
				</div>
				<div className="wporg-modal__modal-backdrop" hidden={ ! isModalPreview }>
					<div className="wporg-modal__modal">
						<InnerBlocks
							template={ [
								[
									'core/group',
									{
										style: {
											spacing: {
												padding: {
													top: 'var:preset|spacing|30',
													bottom: 'var:preset|spacing|30',
													left: 'var:preset|spacing|40',
													right: 'var:preset|spacing|40',
												},
											},
										},
										layout: { type: 'constrained' },
									},
									[ [ 'core/paragraph' ] ],
								],
							] }
						/>
					</div>
				</div>
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => {
		return <InnerBlocks.Content />;
	},
} );
