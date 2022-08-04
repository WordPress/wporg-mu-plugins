/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @param {Object} props
 * @param {Object} props.attributes
 *
 * @return {WPElement} Element to render.
 */
export default function save( { attributes } ) {
	return (
		<div
			{ ...useBlockProps.save() }
			data-endpoint={ attributes.endpoint }
			data-per-page={ attributes.perPage }
		></div>
	);
}
