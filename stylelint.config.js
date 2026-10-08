/**
 * Shared Stylelint configuration for WordPress.org projects.
 */

/**
 * Internal dependencies
 */
const sharedConfig = require( './vendor/wporg/wporg-repo-tools/configs/stylelint' );

module.exports = {
	...sharedConfig,
	// The .pcss sources use CSS nesting and literal imports, not Sass syntax.
	extends: '@wordpress/stylelint-config/stylistic',
	rules: {
		...sharedConfig.rules,
		'@stylistic/max-line-length': null,
		'no-duplicate-selectors': null,
	},
};
