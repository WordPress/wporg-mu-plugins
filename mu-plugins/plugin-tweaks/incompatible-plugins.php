<?php

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks\IncompatiblePlugins;

defined( 'WPINC' ) || die();

const PLUGINS = [
	[
		// If this plugin is enabled..
		'check' => 'blocks-everywhere/blocks-everywhere.php',
		// Don't load this plugin..
		'from'  => 'gutenberg/gutenberg.php',
		// Instead load this plugin
		'to'    => 'gutenberg-16.8/gutenberg.php',
	]
];

/**
 * Check the above list of plugins, and filter the appropriate option.
 *
 * This needs to be done on plugin inclusion, as network-wide plugins are included immediately after mu-plugins.
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

		if ( in_array( $from, $active_plugins, true ) ) {
			add_filter(
				'option_active_plugins',
				function( $plugins ) use ( $from, $to ) {
					// Splice to retain load order, if it's important.
					array_splice(
						$plugins,
						array_search( $from, $plugins, true ),
						1,
						$to
					);
					return $plugins;
				}
			);
		}

		if ( isset( $active_sitewide_plugins[ $from ] ) ) {
			add_filter(
				'site_option_active_sitewide_plugins',
				function( $plugins ) use ( $from, $to ) {
					$plugins[ $to ] = $plugins[ $from ];
					unset( $plugins[ $from ] );

					return $plugins;
				}
			);
		}
	}
}
filter_the_filters();
