<?php
namespace WordPressdotorg\MU_Plugins\CDN;

/**
 * CDNise script/style assets to s.w.org.
 *
 * This only applies to assets which are:
 *  - Is a *wordpress.org domain. This ensures that Local environments and other W.org hosted domains are not affected.
 *  - Is NOT profiles.wordpress.org, or events.wordpress.org. These sites are not hoted in the same docroot as the rest of wordpress.org.
 *  - Only applies to URLs which already include ?ver= cache-busters, and do NOT contain other parameters.
 *
 * In CDN'ing, there's a few specific changes made:
 *  - ver= is always changed to a hash of the asset contents, for consistent and easy cache-busting.
 *  - assets are shared between sites, all use s.w.org/* instead of wordpress.org/wp-includes/* or wordpress.org/plugins/wp-includes/*.
 *  - non-production wp_get_envionment_type() skips CDN'isation by default, but can be overridden with a constant.
 *  - During a deploy, any file that has recently been modified temporarily uses a different cache buster until the deploy is finished.
 *    This avoids scenario's where a web-node references a newer version of the file, but the subsequent request is served by a node
 *    which hasn't yet got that new version of the file, which would then otherwise be cached by the CDN with the new version identifier.
 *
 * @param string $link   The non-CDNised URL.
 * @return string The potentially CDNised URL.
 */
function with_filemtime_cachebuster( $link ) {
	$hostname = strtolower( wp_parse_url( $link, PHP_URL_HOST ) );

	// Only WordPress.org domain files
	if ( 'wordpress.org' !== $hostname && ! str_ends_with( $hostname, '.wordpress.org' ) ) {
		return $link;
	}

	// Several sites are hosted on other Multisites, which are not available via this CDN.
	$other_networks = [
		'profiles.wordpress.org',
		'events.wordpress.org',
	];
	if ( in_array( $hostname, $other_networks, true ) ) {
		return $link;
	}

	$url_args     = [];
	// Trim the scheme & hostname off.
	$relative_url = preg_replace( '!^(\w+:)?//[^/]+/!', '', $link );

	// Trim any sub-site path off - We only use single-depth on WordPress.org at present.
	$relative_url = preg_replace( '!^[^/]+/(wp-(?:content|includes|admin)/)!', '$1', $relative_url );

	if ( str_contains( $relative_url, '?' ) ) {
		list( $filepath, $url_part_args ) = explode( '?', $relative_url, 2 );

		wp_parse_str( $url_part_args, $url_args );
	} else {
		$filepath = $relative_url;
		// No `$url_args` here.

		// Webpack files often include the cache-buster in the filename, 'react' does this.
		// Pretend that's the cache buster for the rest of the function.
		if ( preg_match( '!\.([a-f0-9]{8})\.(min\.)?js$!', $filepath, $m ) ) {
			$url_args = [
				'ver' => $m[1]
			];
		}
	}

	// If the link doesn't have a cache-buster, or has extra args, abort.
	if ( empty( $url_args['ver'] ) || count( $url_args ) > 1 ) {
		return $link;
	}

	// Find the modified time for the file, which may already be the version.
	$timestamp = $url_args['ver'];
	if ( ! is_timestamp( $timestamp ) ) {
		if ( file_exists( ABSPATH . $filepath ) ) {
			$timestamp = filemtime( ABSPATH . $filepath );
		} else {
			$timestamp = null;
		}
	}

	/*
	 * The version is set to the timestamp by default.
	 * CDN will override this with a content hash.
	 */
	$version = $timestamp ?: $url_args['ver'];

	// CDN is used in production by default.
	$use_cdn = ( 'production' === wp_get_environment_type() );

	/*
	 * Allow other environments to opt-in via constant.
	 * Note: This is not recommended, except for testing.
	 */
	if ( defined( 'USE_WPORG_CDN' ) && USE_WPORG_CDN ) {
		$use_cdn = true;
	}

	// If we're using the CDN, make the needed changes.
	if ( $use_cdn ) {
		$hostname = 's.w.org';

		/*
		 * Use a cache-buster that's unique to this file contents.
		 * This allows a cache to be consistent between servers with different modification times.
		 */
		$version = get_file_hash( $filepath, $timestamp ) ?: $timestamp;

		// While a deploy is occuring, use a mid-deploy version recently modified files.
		$version .= mid_deploy_cachebuster( $filepath, $timestamp );
	}

	// Generate the new link.
	$version = urlencode( $version );
	$link    = "https://{$hostname}/{$filepath}?ver={$version}";

	return $link;
}
add_filter( 'style_loader_src',  __NAMESPACE__ . '\with_filemtime_cachebuster', 5 );
add_filter( 'script_loader_src', __NAMESPACE__ . '\with_filemtime_cachebuster', 5 );
add_filter( 'plugins_url',       __NAMESPACE__ . '\with_filemtime_cachebuster', 5 );

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

/**
 * Fetch the file content cache-buster for a given file.
 *
 * @param string $file The file to fetch the cache-buster for.
 * @param int    $time Optional. The file modification time, if already known.
 * @return string The cache-buster. First 12 characters of the sha1.
 */
function get_file_hash( $file, $time = null ) {
	static $cached = [];

	$file   = trailingslashit( ABSPATH ) . $file;
	$time ??= filemtime( $file );
	$key    = $file . ':' . $time . ':hash';

	if ( ! isset( $cached[ $key ] ) ) {
		$cached[ $key ] = apcu_fetch( $key, $found );

		if ( ! $found ) {
			$cached[ $key ] = substr( sha1_file( $file ), 0, 12 );
			apcu_store( $key, $cached[ $key ], DAY_IN_SECONDS );
		}
	}

	return $cached[ $key ];
}

/**
 * Return a mid-deploy cache buster, during deploys.
 *
 * @param string $file          The file to fetch the cache-buster for.
 * @param int    $modified_time Optional. The file modification time, if already known.
 * @return string A cache buster, if required, an empty string otherwise.
 */
function mid_deploy_cachebuster( $file, $modified_time = false ) {
	wp_cache_add_global_groups( 'wporg_deploy' );

	$is_mid_deploy = wp_cache_get( 'is_mid_deploy', 'wporg_deploy', false, $found );

	if (
		$found &&
		$is_mid_deploy &&
		(
			! $modified_time ||
			// .. and cache buster version is a recent timestamp, older assets can re-use existing caches
			$modified_time > ( time() - HOUR_IN_SECONDS )
		)
	) {
		return '-mid-deploy';
	}

	return '';
}