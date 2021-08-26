# Global Header

## Setup




## Register as a block (for Full Site Editing themes)

Add as a composer dependency, install, then add this to a theme's `functions.php`:

```php
require_once WPORG_GIT_MUPLUGINS_DIR . '/mu-plugins/blocks/global-header-footer/blocks.php';
```


## Include directly in PHP (for classic themes)

Add as a composer dependency, install, then

```php
require_once WPORG_GIT_MUPLUGINS_DIR . '/mu-plugins/blocks/global-header-footer/universal-header.php';
```

todo path should be "blocks", or more generic like "components", "template-parts", ?


## Embed as an iframe (for Trac, Codex, etc)

<iframe ...>

src=http...?embed_context=codex
