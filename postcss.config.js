module.exports = {
	plugins: {
		// This has to run before any other plugins, to concatenate all files into one.
		'postcss-import': {},

		'postcss-nesting': {},
		'postcss-custom-media': {},
		'postcss-preset-env': {},
		'cssnano': {}
	}
};
