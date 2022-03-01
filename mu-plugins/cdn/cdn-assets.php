<?php
namespace WordPressdotorg\MU_Plugins\CDN;

/**
 * CDNise script/style assets to s.w.org.
 * 
 * This only applies to assets which are:
 *  - Loaded from the same host as this site is served on
 *  - Is a *wordpress.org domain. This ensures that Local environments and other W.org hosted domains are not affected.
 *  - Is NOT profiles.wordpress.org. This site does not use the same docroot as the rest of wordpress.org.
 *  - Only applies to URLs which already include ?ver= cache-busters, and do NOT contain other parameters.
 * 
 * In CDN'ing, there's a few specific changes made:
 *  - ver= is always changed to the filemtime of the asset (if not already), for consistent and easy cache-busting.
 *  - Caches are chunked into two-minute windows, to avoid slight time differences between servers using different cache keys.
 *  - assets are shared between sites, all use s.w.org/* instead of wordpress.org/wp-includes/* or wordpress.org/plugins/wp-includes/*.
 *  - non-production wp_get_envionment_type() skips CDN'isation.
 * 
 * @param string $link   The non-CDNised URL.
 * @param string $handle The asset handle, used to skip certain assets.
 * @return string The potentially CDNised URL.
 */
function cdnise_with_filemtime_cachebuster( $link, $handle = '' ) {
	// Only same-host resources, and WordPress.org domains.
	if (
		! str_starts_with( $link, site_url( '/' ) ) ||
		! str_contains( $link, 'wordpress.org' ) ||
    str_contains( $link, 'profiles.wordpress.org' )
	) {
		return $link;
	}

	$relative_url = str_replace( site_url( '/' ), '', $link );

	if ( str_contains( $relative_url, '?' ) ) {
		list( $filepath, $url_args ) = explode( '?', $relative_url, 2 );
	} else {
		$filepath = $relative_url;
		$url_args = '';

		// Webpack files often include the cache-buster in the filename, 'react' does this.
		// Pretend that's the cache buster for the rest of the function.
		if ( preg_match( '!\.([a-f0-9]{8})\.(min\.)?js$!', $filepath, $m ) ) {
			$url_args = 'ver=' . $m[1];
		}
	}

	// If the link doesn't have a cache-buster already, or has extra args, abort.
	if (
		! $url_args ||
		! preg_match( '!^ver=(?P<version>[^&]+)$!i', $url_args, $url_cachebuster )
	) {
		return $link;
	}

	$version = false;
	// Set the version to the file modification time, for consistency.
	if ( ! is_timestamp( $url_cachebuster['version'] ) ) {
		$version = filemtime( ABSPATH . $filepath );
	}
	if ( ! $version ) {
		$version = $url_cachebuster['version'];
	}

	// Chunk the cache buster on a ~2 minute rolling window.
	// This allows for the production deploy process taking a few minutes, setting different modification times on different servers.
	if (
		'production' === wp_get_environment_type() &&
		is_timestamp( $version )
	) {
		$window  = 2 * MINUTE_IN_SECONDS;
		$version = floor( $version / $window ) * $window;
	}

	$use_cdn = (
		// CDN is used in production by default.
		'production' === wp_get_environment_type() ||

		// Allow other environments to opt-in via constant.
		( defined( 'USE_WPORG_CDN' ) && USE_WPORG_CDN )
	);

	// These scripts/styles break if loaded from the CDN without CORS headers.
	// See https://make.wordpress.org/systems/2022/02/22/cors-headers-for-s-w-org/
	$blocked_handles = [
		'wporg-news-style', // Mask images
		'wporg-global-fonts-css', 'jetpack-icons', 'wp-stream-icons', // Fonts
	];
	if ( in_array( $handle, $blocked_handles, true ) ) {
		$use_cdn = false;
	}

	$link = 'https://' . ( $use_cdn ? 's.w.org' : $_SERVER['HTTP_HOST'] ) . '/' . $filepath;

	// Add the version back onto the URL.
	$link = add_query_arg( 'ver', $version, $link );

	return $link;
}
add_filter( 'style_loader_src', __NAMESPACE__ . '\cdnise_with_filemtime_cachebuster', 5, 2 );
add_filter( 'script_loader_src', __NAMESPACE__ . '\cdnise_with_filemtime_cachebuster', 5, 2 );

/**
 * Determine if a string appears to be a timestamp.
 * 
 * Due to the use-case here, we're assuming that the timestamp will occur
 * between Y2.01K and now.
 * 
 * @param string|int $string The string to check.
 * @return bool Whether the input appears to be a UTC timestamp.
 */
function is_timestamp( $string ) {
	return (
		is_numeric( $string ) &&
		$string >= 1262304000 /* Y2.01K - 2010-01-01 */ &&
		$string <= time()
	);
}
