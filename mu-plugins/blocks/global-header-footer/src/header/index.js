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
	{ label: __( 'White on charcoal-1', 'wporg' ), value: 'white-on-dark-black' },
	{ label: __( 'White on charcoal-2 (default)', 'wporg' ), value: 'white-on-black' },		
	{ label: __( 'White on blueberry-1', 'wporg' ), value: 'white-on-blue' },
	{ label: __( 'Black on white', 'wporg' ), value: 'black-on-white' },
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
		title: sprintf( __( 'Global Header: %s', 'wporg' ), label ),
		isActive: ( blockAttributes, variationAttributes ) => blockAttributes.style === variationAttributes.style,
		scope: [ 'transform' ],
		attributes: { style: value },
	} ) ),
} );
