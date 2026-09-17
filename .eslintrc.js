/**
 * Shared legacy ESLint configuration for this repository's tooling.
 */

/**
 * Internal dependencies
 */
const createConfig = require( './vendor/wporg/wporg-repo-tools/configs/eslintrc' );

module.exports = createConfig( {
	textDomain: 'wporg',
	prettierConfig: require( './.prettierrc' ),
} );
