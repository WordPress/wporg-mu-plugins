<?php

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks\Jetpack;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_filter( 'jetpack_get_available_modules', __NAMESPACE__ . '\available_jetpack_blocks', 100, 2 );
add_filter( 'jetpack_active_modules', __NAMESPACE__ . '\activate_jetpack_blocks' );

/**
 * Add "blocks" module to available list, using `min_version` to ensure it's active.
 *
 * @param string[] $modules     Array of modules (module name => version introduced).
 * @param string   $min_version Minimum version number required to use modules.
 * @return string[]
 */
function available_jetpack_blocks( $modules, $min_version ) {
	if ( ! defined( 'JETPACK__VERSION' ) || version_compare( JETPACK__VERSION, '13.8', '<=' ) ) {
		return $modules;
	}
	if ( ! isset( $modules['blocks'] ) ) {
		$modules['blocks'] = $min_version;
	}
	return $modules;
}

/**
 * Ensure "blocks" module is active.
 *
 * The subscribe block is used in the footer template, so this must be active
 * for all sites. The filter only fires if Jetpack itself is already active.
 *
 * @param string[] $modules Array of active Jetpack modules.
 * @return string[]
 */
function activate_jetpack_blocks( $modules ) {
	if ( ! in_array( 'blocks', $modules, true ) ) {
		$modules[] = 'blocks';
	}
	return $modules;
}
