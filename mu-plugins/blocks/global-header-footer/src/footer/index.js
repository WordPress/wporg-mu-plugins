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
	{ label: __( 'White on dark black', 'wporg' ), value: 'white-on-dark-black' },
	{ label: __( 'Black on white', 'wporg' ), value: 'black-on-white' },
	{ label: __( 'White on blue', 'wporg' ), value: 'white-on-blue' },
];

const Edit = ( { attributes } ) => (
	<div { ...useBlockProps() }>
		<Disabled>
			<ServerSideRender block={ metadata.name } attributes={ attributes } />
		</Disabled>
	</div>
);

registerBlockType( metadata.name, {
	edit: Edit,

	variations: variations.map( ( { value, label } ) => ( {
		name: value,
		/* translators: %s is the color scheme label. */
		title: sprintf( __( 'Global Footer: %s', 'wporg' ), label ),
		isActive: ( blockAttributes, variationAttributes ) => blockAttributes.style === variationAttributes.style,
		scope: [ 'transform' ],
		attributes: { style: value },
	} ) ),
} );
