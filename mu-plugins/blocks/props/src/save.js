/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { RichText, useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { content, align } = attributes;

	const className = classnames( {
		[ `has-text-align-${ align }` ]: align,
	} );

	return (
		<p { ...useBlockProps.save( { className } ) }>
			<RichText.Content value={ content } />
		</p>
	);
}
