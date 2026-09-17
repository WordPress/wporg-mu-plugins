/**
 * WordPress dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { type } = attributes;
	const className = `is-${ type }-notice`;

	return (
		<div { ...useBlockProps.save( { className } ) }>
			<div className="wp-block-wporg-notice__icon" />
			<div className="wp-block-wporg-notice__content">
				<InnerBlocks.Content />
			</div>
		</div>
	);
}
