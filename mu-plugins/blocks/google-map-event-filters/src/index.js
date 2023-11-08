/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Placeholder } from '@wordpress/components';

/**
 * Internal dependencies
 */
import metadata from './block.json';

function Edit() {
	return (
		<Placeholder
			instructions={ __(
				'This is a placeholder for the editor until a back-end UI is built. See the README for instructions on supplying data.',
				'wporg'
			) }
			label={ __( 'Google Map Event Filters', 'wporg' ) }
		/>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
} );
