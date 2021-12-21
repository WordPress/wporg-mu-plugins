# Global Header

⚠️ Changes here must be tested on all sites. See the old `header.php` for more info, until that's ported here.


## Full Site Editing themes

1. See `../../../README.md` for installation prerequisites.
1. `require_once .../global-header-footer/blocks.php` file. See `wporg-news-2021` as an example.
1. Add `<!-- wp:wporg/global-header /-->` to the theme's `block-templates/*.html` files.


## Classic themes

The same as above, but instead of adding the block to `block-templates/*.html` files, you'd add it to `header.php`:

```php
echo do_blocks( '<!-- wp:wporg/global-header /-->' );
```

⚠️ You can't just `require header.php` directly, because the dynamic blocks need to be processed by `do_blocks()`, and `blocks.php` does additional work that's necessary.


## Non-WP software (like Trac, Codex, etc)

@todo - probably pull contents from a REST API
