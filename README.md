# wporg-mu-plugins

Over time, this is intended to become the canonical source repository all `mu-plugins` on the WordPress.org network. At the moment, it only includes a few.

## Usage

1. Add to a project's `composer.json`, example
1. run `composer update` to download it
1. do in env/0-sandbox.php .  on production, it's already defined
	```php
	define( 'WPORG_GIT_MUPLUGINS_DIR', dirname( ABSPATH ) . '/vendor/wporg/wporg-mu-plugins' );
	```
1. `require_once` the files that you want. e.g., have a themes's `functions.php`
	```php
	require_once WPORG_GIT_MUPLUGINS_DIR . '/mu-plugins/blocks/global-header-footer/blocks.php';
	```

## Sync/Deploy

The files here are commited to `dotorg.svn` so they can be deployed. The aren't synced to `meta.svn`, since they're already open.

The other `mu-plugins` in `meta.svn` are not synced here. Eventually they'll be removed from `meta.svn` and added here, but until then they can stay where they are.

To sync these to `dotorg.svn`, run `composer exec sync-svn` (WIP) and follow the instructions. Once they're committed, you can deploy like normal.
