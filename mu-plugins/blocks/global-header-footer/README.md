# Global Header

## Full Site Editing themes

1. See `../../../README.md` for installation prerequisites.
1. `require_once .../global-header-footer/blocks.php` file. See `wporg-news-2021` as an example.
1. Add `<!-- wp:wporg/global-header /-->` to the theme's `block-templates/*.html` files.


## Classic themes

The same as above, but instead of adding the block to `block-templates/*.html` files, you'd add it to `header.php`:

```php
echo do_blocks( '<!-- wp:wporg/global-header /-->' );
```

⚠️ You can't just `require universal-header.php` directly, because the dynamic blocks need to be processed by `do_blocks()`.


## Non-WP software (like Trac, Codex, etc)

```html
<iframe
	width="100%"
	height="auto"
	src="https://wordpress.org/header.php?embed_context=trac">
</iframe>
```

todo - or maybe download above url via curl? look at how trac/codex currenly do it

⚠️ The above won't work in local development environments, because of `X-FRAME-OPTIONS`.
