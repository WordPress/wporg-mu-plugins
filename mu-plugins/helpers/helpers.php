<?php

namespace WordPressdotorg\MU_Plugins\Helpers;

defined( 'WPINC' ) || die();

/**
 * Join a string with a natural language conjunction at the end.
 *
 * Based on https://stackoverflow.com/a/25057951/450127, modified to include an Oxford comma.
 */
function natural_language_join( array $list, $conjunction = 'and' ) : string {
	if ( empty( $list ) ) {
		return '';
	}

	$oxford_separator = 2 === count( $list ) ? ' ' : ', ';
	$last             = array_pop( $list );

	if ( $list ) {
		return implode( ', ', $list ) . $oxford_separator . $conjunction . ' ' . $last;
	}

	return $last;
}

/**
 * Check if a plugin is active on the current site.
 *
 * Clone of core's `is_plugin_active` and `is_plugin_active_for_network`, so
 * they can be used on the frontend.
 *
 * @param string $plugin Path to the plugin file relative to the plugins directory.
 * @return bool True, if in the active plugins list. False, not in the list.
 */
function is_plugin_active( $plugin ) {
	// Single-site active.
	if ( in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) ) {
		return true;
	}

	if ( ! is_multisite() ) {
		return false;
	}

	$plugins = get_site_option( 'active_sitewide_plugins' );
	if ( isset( $plugins[ $plugin ] ) ) {
		return true;
	}

	return false;
}
