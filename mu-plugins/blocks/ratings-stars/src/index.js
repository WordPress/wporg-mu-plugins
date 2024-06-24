/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './style.scss';

function Edit( { attributes, name } ) {
	return (
		<div { ...useBlockProps() }>
			<ServerSideRender block={ name } attributes={ attributes } skipBlockSupportAttributes />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
