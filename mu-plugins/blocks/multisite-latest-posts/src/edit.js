/**
 * WordPress dependencies
 */
import ServerSideRender from '@wordpress/server-side-render';

import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	__experimentalNumberControl as NumberControl, // eslint-disable-line
	PanelBody,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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
	const { endpoint, itemsToShow } = attributes;

	const blockProps = useBlockProps();

	const handleEndpoint = ( value ) => setAttributes( { endpoint: value } );
	const handleItems = ( value ) => setAttributes( { itemsToShow: value } );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'wporg' ) }>
					<TextControl
						label={ __( 'Endpoint', 'wporg' ) }
						value={ endpoint }
						onChange={ handleEndpoint }
					/>
					<NumberControl
						label={ __( 'Items To Show', 'wporg' ) }
						onChange={ handleItems }
						value={ itemsToShow }
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender block={ blockProps[ 'data-type' ] } attributes={ attributes } />
		</div>
	);
}
