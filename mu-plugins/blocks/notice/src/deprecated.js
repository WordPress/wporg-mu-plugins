/**
 * WordPress dependencies
 */
import { createBlock, parseWithAttributeSchema } from '@wordpress/blocks';
import { RichText, useBlockProps } from '@wordpress/block-editor';

const migrateToInnerBlocks = ( attributes ) => {
	const { content, ...restAttributes } = attributes;

	return [
		{
			...restAttributes,
		},
		content
			? parseWithAttributeSchema( content, {
					type: 'array',
					source: 'query',
					selector: 'p',
					query: {
						content: {
							type: 'string',
							source: 'html',
						},
					},
			  } ).map( ( { content: paragraphContent } ) =>
					createBlock( 'core/paragraph', {
						content: paragraphContent,
					} )
			  )
			: [ createBlock( 'core/paragraph' ) ],
	];
};

const v1 = {
	attributes: {
		content: {
			type: 'string',
			source: 'html',
			selector: '.wp-block-wporg-notice__content',
			multiline: 'p',
		},
		type: {
			type: 'string',
			default: 'tip',
			enum: [ 'alert', 'info', 'warning', 'tip', 'success' ],
		},
	},
	migrate: migrateToInnerBlocks,
	save: ( { attributes } ) => {
		const { content, type } = attributes;
		const className = `is-${ type }-notice`;

		return (
			<div { ...useBlockProps.save( { className } ) }>
				<div className="wp-block-wporg-notice__icon" />
				<div className="wp-block-wporg-notice__content">
					<RichText.Content multiline="p" value={ content } />
				</div>
			</div>
		);
	},
};

export default [ v1 ];
