<?php
/**
 * Helper functions for Rosetta (localized) sites.
 *
 * @package WordPressdotorg\MU_Plugins\Helpers
 */

declare( strict_types = 1 );

namespace WordPressdotorg\MU_Plugins\Helpers;

use Rosetta_Sites;

defined( 'WPINC' ) || die();

/**
 * Determine if the current site is a Rosetta site (e.g., `es-mx.wordpress.org`).
 *
 * This returns `false` for `translate.wordpress.org`; it's part of the Rosetta network, but isn't a Rosetta site.
 *
 * @return bool
 */
function is_rosetta_site(): bool {
	global $rosetta;

	return $rosetta instanceof Rosetta_Sites;
}

/**
 * Fetch the Rosetta site name.
 *
 * @return string
 */
function get_rosetta_name(): string {
	/** @var Rosetta_Sites $rosetta */
	global $rosetta;

	return get_blog_option( $rosetta->get_root_site_id(), 'blogname' );
}
