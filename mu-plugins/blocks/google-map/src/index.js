/* global wporgGoogleMap */

/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Main from './components/main';

function Edit() {
	return (
		<div { ...useBlockProps() }>
			<Main { ...wporgGoogleMap } />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
} );
