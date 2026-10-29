/**
 * Shared flat ESLint configuration for WordPress.org projects.
 */

/**
 * Internal dependencies
 */
const createConfig = require( './vendor/wporg/wporg-repo-tools/configs/eslint' );

module.exports = [
	{
		ignores: [ '**/vendor/**', '**/node_modules/**', '**/build/**' ],
	},
	...createConfig( {
		textDomain: 'wporg',
		prettierConfig: require( './.prettierrc' ),
	} ),
	{
		settings: {
			// These imports are externalized to scripts supplied by WordPress.
			'import/core-modules': [ '@wordpress/element', '@wordpress/hooks', '@wordpress/i18n' ],
		},
	},
];
