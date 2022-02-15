# Global Fonts

Inter and EB Garamond are used across WordPress.org. This mu-plugin sets up local versions to load, rather than loading from Google fonts.

Sources:

- [Inter](https://github.com/rsms/inter)
- [EB Garamond](https://fonts.google.com/specimen/EB+Garamond)

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
