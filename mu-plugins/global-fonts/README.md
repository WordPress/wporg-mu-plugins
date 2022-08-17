# Global Fonts

Inter and EB Garamond are used across WordPress.org. This mu-plugin sets up local versions to load, rather than loading from Google fonts.

Sources:

- [Inter](https://github.com/rsms/inter), compressed and subsetted to woff2 with [glyphhanger](https://github.com/zachleat/glyphhanger)
- [EB Garamond](https://fonts.google.com/specimen/EB+Garamond), compressed and subsetted to woff2 with [glyphhanger](https://github.com/zachleat/glyphhanger)

## How to use:

If you want to use these fonts in a theme, just add the `wporg-global-fonts` handle as a dependency.

```php
wp_register_style(
	'wporg-some-theme',
	get_stylesheet_uri(),
	array( 'wporg-global-fonts' ),
	$css_version
);
```

If you wish to have one (or more) fonts preloaded automatically, you can add the `preload` style data.

For example, to preload Inter in normal and italic:

```php
wp_style_add_data( 'wporg-global-fonts', 'preload', [ 'Inter', 'Inter italic' ] );
```