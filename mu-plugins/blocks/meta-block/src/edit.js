/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextareaControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

export default function Edit( { attributes, setAttributes } ) {
	const excerpt = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'excerpt' ) );
	const { editPost } = useDispatch( 'core/editor' );
	const { description } = attributes;
	const style = {
		border: '1px dashed #757575',
		color: '#757575',
		padding: '1em',
		textAlign: 'center',
		fontStyle: 'italic',
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'wporg' ) }>
					<TextareaControl
						label={ __( 'Page description', 'wporg' ) }
						help={ __( 'Shown in search results and in social media embeds.', 'wporg' ) }
						value={ description || excerpt }
						onChange={ ( value ) => {
							setAttributes( { description: value } );
							editPost( { excerpt: value } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<p { ...useBlockProps() } style={ style }>
				{ __( 'Nothing to see here.', 'wporg' ) }
			</p>
		</>
	);
}
