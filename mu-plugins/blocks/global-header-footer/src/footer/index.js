/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Disabled } from '@wordpress/components';
import { registerBlockType } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';

const variations = [
	{ label: __( 'White on black (default)', 'wporg' ), value: 'white-on-black' },
	{ label: __( 'Black on white', 'wporg' ), value: 'black-on-white' },
	{ label: __( 'White on blue', 'wporg' ), value: 'white-on-blue' },
];

const Edit = ( { attributes } ) => (
	<div { ...useBlockProps() }>
		<Disabled>
			<ServerSideRender block={ metadata.name } attributes={ attributes } skipBlockSupportAttributes />
		</Disabled>
	</div>
);

registerBlockType( metadata.name, {
	edit: Edit,
} );
