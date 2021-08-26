# wporg-mu-plugins

Over time, this is intended to become the canonical source repository all `mu-plugins` on the WordPress.org network. At the moment, it only includes a few.


## Sync/Deploy

The files here are commited to `dotorg.svn` so they can be deployed. The aren't synced to `meta.svn`, since they're already open.

The other `mu-plugins` in `meta.svn` are not synced here. Eventually they'll be removed from `meta.svn` and added here, but until then they can stay where they are.

To sync these to `dotorg.svn`, run `composer exec sync-svn` (WIP) and follow the instructions. Once they're committed, you can deploy like normal.
