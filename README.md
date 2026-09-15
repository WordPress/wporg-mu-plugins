# wporg-mu-plugins

Over time, this is intended to become the canonical source repository for all `mu-plugins` on the WordPress.org network. At the moment, it only includes a few.

## Usage

1. Add entries to the `repositories` and `require-dev` sections of `composer.json`. See [wporg-news-2021](https://github.com/WordPress/wporg-news-2021/) [composer.json](https://github.com/WordPress/wporg-news-2021/blob/trunk/composer.json) as an example.
1. Run `composer update` to install it
1. `require_once` the files that you want. e.g.,
	```php
	require_once WPMU_PLUGIN_DIR . '/wporg-mu-plugins/mu-plugins/blocks/global-header-footer/blocks.php';
	```
1. See individual plugin readmes for specific instructions


## Development

* `npm run start` during development, only builds `style.css`
* `npm run build` before commit/sync/deploy, builds `style.css` and `style-rtl.css`.

### PHP coding standards

Run `composer install` to install the lint tools. `composer run lint` reports PHP
coding-standard errors and warnings and checks compatibility with PHP 8.4 and later.

CI runs the full PHP scan on pull requests and before building trunk. PHP errors
and warnings fail the build. The host tooling runs on PHP 8.4; WordPress unit tests
remain on PHP 8.2.

PHPCompatibility packages are pinned to prereleases until compatible stable
versions are available. The PHPCS configuration is maintained in this repository;
keep it when `update-configs` asks whether to replace it.


## Sync/Deploy

The built here are synced to `dotorg.svn` so they can be deployed. The aren't synced to `meta.svn`, since they're already open.

The other `mu-plugins` in `meta.svn` are not synced here. Over time, they can be removed from `meta.svn` and added here.

To sync these to `dotorg.svn`, run `bin/sync/wporg-mu-plugins.sh` on a w.org sandbox. Once they're committed, you can deploy like normal.
