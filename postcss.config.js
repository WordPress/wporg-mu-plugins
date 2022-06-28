module.exports = {
	plugins: [
		// This has to run before any other plugins, to concatenate all files into one.
		require( 'postcss-import' ),

		// Enable transforms for all experimental features.
		require( 'postcss-preset-env' )( {
			stage: 0,
		} ),

		// Minify.
		require( 'cssnano' ),
	],
};
