<?php

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks\IncompatiblePlugins;

defined( 'WPINC' ) || die();

// Don't run this on the plugins.php page, as we don't want to operate on filtered options.
if ( defined( 'WP_ADMIN' ) && WP_ADMIN && str_contains( $_SERVER['REQUEST_URI'] ?? '', '/plugins.php' ) ) {
	return;
}

/**
 * Plugin config.
 *
 * Each item in the array contains a plugin to check (`check`), and a plugin
 * that should be deactivated (`from`) in favor of another plugin (`to`).
 */
const PLUGINS = [
	[
		// Blocks Everywhere: Uses private/unstable APIs,
		// which are blocked after GB 16.8.
		'check' => 'blocks-everywhere/blocks-everywhere.php',
		'from'  => 'gutenberg/gutenberg.php',
		'to'    => 'gutenberg-16.8/gutenberg.php',
	],
	[
		// Pattern Creator: Uses private/unstable APIs,
		// which are blocked after GB 16.8.
		'check' => 'pattern-creator/pattern-creator.php',
		'from'  => 'gutenberg/gutenberg.php',
		'to'    => 'gutenberg-16.8/gutenberg.php',
	],
];

/**
 * Check the above list of plugins, and filter the appropriate option.
 *
 * This needs to be done on plugin inclusion, as network-wide plugins are included immediately after mu-plugins.
 *
 * NOTE: This doesn't support blog switching well at all, on a switched blog the filters will be applied to the wrong blog,
 *       this isn't as bad as it sounds, as loading plugins for other sites from the context of a different site rarely
 *       works in the first place. Instead, this code simply double-checks that the `$from` plugin is active.
 *       The `$check` plugin is not checked for in the switched context, as including two different versions of the same
 *       plugin on the same request is not going to work, so it still needs to attempt to load the versioned version.
 */
function filter_the_filters() {
	$active_plugins          = (array) get_option( 'active_plugins', [] );
	$active_sitewide_plugins = is_multisite() ? get_site_option( 'active_sitewide_plugins', [] ) : [];

	foreach ( PLUGINS as $incompatible_plugin ) {
		$check = $incompatible_plugin['check'];
		$from  = $incompatible_plugin['from'];
		$to    = $incompatible_plugin['to'];

		// Check to see if the incompatible plugin is active first.
		// Not using the functions that do this, as they're only loaded in wp-admin.
		if (
			! in_array( $check, $active_plugins, true ) &&
			! isset( $active_sitewide_plugins[ $check ] )
		) {
			continue;
		}

		add_filter(
			'option_active_plugins',
			function( $plugins ) use ( $from, $to ) {
				$pos = array_search( $from, $plugins, true );
				if ( false !== $pos ) {
					// Splice to retain load order, if it's important.
					array_splice(
						$plugins,
						$pos,
						1,
						$to
					);
				}

				return $plugins;
			}
		);

		add_filter(
			'site_option_active_sitewide_plugins',
			function( $plugins ) use ( $from, $to ) {
				if ( isset( $plugins[ $from ] ) ) {
					$plugins[ $to ] = $plugins[ $from ];
					unset( $plugins[ $from ] );
				}

				return $plugins;
			}
		);
	}
}

filter_the_filters();
