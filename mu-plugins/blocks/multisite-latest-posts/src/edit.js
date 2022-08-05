/**
 * WordPress dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	__experimentalNumberControl as NumberControl, // eslint-disable-line
	PanelBody,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal Dependencies
 */
import Block from './block.js';

/**
 * Renders controls and a preview of this dynamic block.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { endpoint, perPage } = attributes;

	const blockProps = useBlockProps();

	const onEndpointChange = ( value ) => setAttributes( { endpoint: value } );
	const onPerPageChange = ( value ) => setAttributes( { perPage: value * 1 } );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'wporg' ) }>
					<TextControl
						label={ __( 'Endpoint', 'wporg' ) }
						value={ endpoint }
						onChange={ onEndpointChange }
					/>
					<NumberControl
						label={ __( 'Items To Show', 'wporg' ) }
						onChange={ onPerPageChange }
						value={ perPage }
					/>
				</PanelBody>
			</InspectorControls>
			<Block endpoint={ endpoint } perPage={ perPage } />
		</div>
	);
}
