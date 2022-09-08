/**
 * WordPress dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	__experimentalNumberControl as NumberControl, // eslint-disable-line
	PanelBody,
	TextControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
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
	const { blogId, perPage } = attributes;

	const blockProps = useBlockProps();

	const onBlogIdChange = ( value ) => setAttributes( { blogId: value } );
	const onPerPageChange = ( value ) => setAttributes( { perPage: value * 1 } );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'wporg' ) }>
					<TextControl label={ __( 'Blog Id', 'wporg' ) } value={ blogId } onChange={ onBlogIdChange } />
					<NumberControl
						label={ __( 'Items To Show', 'wporg' ) }
						onChange={ onPerPageChange }
						value={ perPage }
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender block="wporg/multisite-latest-posts" attributes={ attributes } />
		</div>
	);
}
