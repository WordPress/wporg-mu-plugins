/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import {
	AlignmentControl,
	BlockControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { content, align } = attributes;

	const blockProps = useBlockProps( {
		className: classnames( {
			[ `has-text-align-${ align }` ]: align,
		} ),
	} );

	return (
		<>
			<BlockControls group="block">
				<AlignmentControl
					value={ align }
					onChange={ ( newAlign ) =>
						setAttributes( {
							align: newAlign,
						} )
					}
				/>
			</BlockControls>
			<RichText
				identifier="content"
				tagName="p"
				{ ...blockProps }
				onChange={ ( newContent ) =>
					setAttributes( { content: newContent } )
				}
				value={ content }
			/>
		</>
	);
}
