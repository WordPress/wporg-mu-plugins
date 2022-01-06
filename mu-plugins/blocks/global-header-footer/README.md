# Global Header and Footer

⚠️ Changes here must be tested on all sites. See the old `header.php` for more info, until that's ported here.


## Full Site Editing themes

1. See `../../../README.md` for installation prerequisites.
1. `require_once .../global-header-footer/blocks.php` file. See `wporg-news-2021` as an example.
1. Add `<!-- wp:wporg/global-header /-->` to the theme's `block-templates/*.html` files.


## Classic themes

The same as above, but instead of adding the block to `block-templates/*.html` files, you'd add it to `themes/{name}/header.php`:

```php
echo do_blocks( '<!-- wp:wporg/global-header /-->' );
```

ℹ️ The block will output `<html>`, `wp_head()`, etc, so the above statement is the only thing you need in a `themes/{name}/header.php` file. The same is true for the footer and `</html>`, etc.

⚠️ You can't just `require header.php` directly, because the dynamic blocks need to be processed by `do_blocks()`, and `blocks.php` does additional work that's necessary.


## Non-WP software (like Trac, Codex, etc)

@todo - probably pull contents from a REST API

see https://github.com/WordPress/wporg-mu-plugins/issues/45
