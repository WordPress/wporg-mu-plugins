/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- experimental ok.
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	InnerBlocks,
	InspectorControls,
	RichText,
	useBlockProps,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- experimental ok.
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
	withColors,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { store as blocksStore, registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
// eslint-disable-next-line import/no-extraneous-dependencies
import { useState } from 'react';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './style.scss';

function Edit( {
	attributes,
	setAttributes,
	backgroundColor,
	setBackgroundColor,
	textColor,
	setTextColor,
	closeButtonColor,
	setCloseButtonColor,
	overlayColor,
	setOverlayColor,
	clientId,
} ) {
	const [ isModalPreview, setIsModalPreview ] = useState( false );
	const [ buttonStyleOptions, setButtonStyleOptions ] = useState( [] );
	const { customBackgroundColor, customCloseButtonColor, customTextColor, customOverlayColor } = attributes;
	const colorGradientSettings = useMultipleOriginColorsAndGradients();

	useSelect( ( select ) => {
		const { getBlockStyles } = select( blocksStore );
		const styles = getBlockStyles( 'core/button' );
		const options = styles.map( ( item ) => ( { label: item.label, value: item.name } ) );
		// Add the same options with the `is-small` modifier.
		styles.forEach( ( item ) => {
			options.push( { label: `${ item.label } (small)`, value: `${ item.name } is-small` } );
		} );
		setButtonStyleOptions( options );
	}, [] );

	const classes = [];
	if ( isModalPreview ) {
		classes.push( 'is-modal-open' );
	}

	const style = {
		'--wp--custom--wporg-modal--color--background': backgroundColor.slug
			? `var( --wp--preset--color--${ backgroundColor.slug } )`
			: customBackgroundColor,
		'--wp--custom--wporg-modal--color--text': textColor.slug
			? `var( --wp--preset--color--${ textColor.slug } )`
			: customTextColor,
		'--wp--custom--wporg-modal--color--close-button': closeButtonColor.slug
			? `var( --wp--preset--color--${ closeButtonColor.slug } )`
			: customCloseButtonColor,
		'--wp--custom--wporg-modal--color--overlay': overlayColor.slug
			? `var( --wp--preset--color--${ overlayColor.slug } )`
			: customOverlayColor,
	};

	const blockProps = useBlockProps( {
		className: classes,
		style: style,
	} );

	return (
		<>
			<InspectorControls group="color">
				<ColorGradientSettingsDropdown
					settings={ [
						{
							label: __( 'Modal background', 'wporg' ),
							colorValue: backgroundColor.color || customBackgroundColor,
							onColorChange: ( value ) => {
								setBackgroundColor( value );
								setAttributes( {
									customBackgroundColor: value,
								} );
							},
						},
						{
							label: __( 'Modal text', 'wporg' ),
							colorValue: textColor.color || customTextColor,
							onColorChange: ( value ) => {
								setTextColor( value );
								setAttributes( {
									customTextColor: value,
								} );
							},
						},
						{
							label: __( 'Close button', 'wporg' ),
							colorValue: closeButtonColor.color || customCloseButtonColor,
							onColorChange: ( value ) => {
								setCloseButtonColor( value );
								setAttributes( {
									customCloseButtonColor: value,
								} );
							},
						},
						{
							label: __( 'Overlay', 'wporg' ),
							colorValue: overlayColor.color || customOverlayColor,
							onColorChange: ( value ) => {
								setOverlayColor( value );
								setAttributes( {
									customOverlayColor: value,
								} );
							},
							enableAlpha: true,
						},
					] }
					panelId={ clientId }
					hasColorsOrGradients={ false }
					disableCustomColors={ false }
					__experimentalIsRenderedInSidebar
					{ ...colorGradientSettings }
				/>
			</InspectorControls>
			<InspectorControls group="default">
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
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Button style', 'wporg' ) }
						help={ __( 'Style to use for the toggle button.', 'wporg' ) }
						onChange={ ( newValue ) => {
							setAttributes( { buttonStyle: newValue } );
						} }
						value={ attributes.buttonStyle }
						options={ buttonStyleOptions }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="wp-block-buttons">
					<div className={ `wp-block-button is-style-${ attributes.buttonStyle }` }>
						<RichText
							tagName="div"
							className="wp-block-button__link"
							value={ attributes.label }
							onChange={ ( label ) => setAttributes( { label } ) }
							placeholder={ __( 'Open modal', 'wporg' ) }
						/>
					</div>
				</div>
				<div className="wporg-modal__modal-backdrop" hidden={ ! isModalPreview }>
					<div className="wporg-modal__modal">
						<button
							className="wporg-modal__modal-close"
							onClick={ () => setIsModalPreview( false ) }
							aria-label={ __( 'Close modal', 'wporg' ) }
						></button>
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
	edit: withColors( {
		backgroundColor: 'background-color',
		textColor: 'text-color',
		closeButtonColor: 'close-button-color',
		overlayColor: 'overlay-color',
	} )( Edit ),
	save: () => {
		return <InnerBlocks.Content />;
	},
} );
