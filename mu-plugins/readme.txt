** Note **

Try to avoid naming files in a directory as `index.php`, since this can produce fatal errors should a request go directly to the directory.

It can be avoided by either specifying a WPINC check prior to any class or function call, or by naming the file something like `blocks.php`.

See: https://github.com/WordPress/wporg-mu-plugins/pull/584 for examples and some information of it.
