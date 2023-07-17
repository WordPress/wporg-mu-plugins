<?php
/**
 * Block Name: Time
 * Description: Attempts to parse a time string like <code>[time]any-valid-time-string-here[/time]</code> and creates a format that shows it in the viewers local time zone.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Time;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Enqueues the script for the Time format in the block editor using the metadata loaded from the `block.json` file,
 * and adds the filter for transforming the time blocks.
 */
function init() {
	// Register and enqueue the block editor script to add the time format to the toolbar.
	$index_asset_file = include plugin_dir_path( __FILE__ ) . 'build/index.asset.php';
	wp_register_script(
		'wporg-time-format',
		plugins_url( 'build/index.js', __FILE__ ),
		$index_asset_file['dependencies'],
		$index_asset_file['version'],
		true
	);

	add_action(
		'enqueue_block_editor_assets',
		function() {
			wp_enqueue_script( 'wporg-time-format' );
		}
	);

	// Register the script for converting the times to local times on the frontend.
	$converter_asset_file = include plugin_dir_path( __FILE__ ) . 'build/convert_times.asset.php';
	wp_register_script(
		'wporg-time-format-converter',
		plugins_url( 'build/convert_times.js', __FILE__ ),
		$converter_asset_file['dependencies'],
		$converter_asset_file['version'],
		true
	);

	add_filter( 'the_content', __NAMESPACE__ . '\transform_times', 99, 1 );
}

/**
 * Builds the time block output.
 *
 * This implements replacing the raw time strings in the post content with formatted times able to be converted to local times with JS.
 *
 * @param string $content Post content.
 * @return string Content with display times reformatted.
 */
function transform_times( $content ) {
	if ( empty( $content ) || is_admin() ) {
		return $content;
	}

	// Find the time block elements by the classname "wporg-time"
	$dom = new \DOMDocument();

	// Ignore warnings about htlm5 tags.
	$dom->loadHTML( $content, LIBXML_NOERROR );
	$xpath = new \DOMXPath( $dom );
	$time_elements = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' wporg-time ')]" );

	if ( empty( $time_elements ) ) {
		return $content;
	}

	wp_enqueue_script( 'wporg-time-format-converter' );

	foreach ( $time_elements as $time_element ) {
		$time_content = $time_element->nodeValue; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$parsed_time = parse_time( $time_content );

		if ( null === $parsed_time ) {
			continue;
		}

		$time_element->setAttribute( 'datetime', gmdate( 'c', $parsed_time ) );

		// Create a new link with the time as the href, clone the time element inside the link,
		// and replace the original time element with the link.
		$link_element = $dom->createElement( 'a' );
		$link_element->setAttribute( 'href', 'https://www.timeanddate.com/worldclock/fixedtime.html?iso=' . gmdate( 'Ymd\THi', $parsed_time ) );
		$link_element->appendChild( $time_element->cloneNode( true ) );
		$time_element->parentNode->replaceChild( $link_element, $time_element ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	$content = $dom->saveHTML();

	return $content;
}

/**
 * Parse the datetime description string and return a timestamp.
 *
 * @param string $content Datetime description, eg. `Monday, April 6 at 19:00 UTC`.
 * @return string Unix timestamp or null if the time string could not be parsed.
 */
function parse_time( $content ) {
	// Replace non-breaking spaces with a regular white space.
	$gmtcontent = preg_replace( '/\xC2\xA0|&nbsp;/', ' ', $content );

	// PHP understands "GMT" better than "UTC" for timezones.
	$gmtcontent = str_replace( 'UTC', 'GMT', $gmtcontent );

	// Remove the word "at" from the string, if present. Allows strings like "Monday, April 6 at 19:00 UTC" to work.
	$gmtcontent = str_replace( ' at ', ' ', $gmtcontent );

	// Try to parse the time, relative to the post time.
	$time = strtotime( $gmtcontent, get_the_date( 'U' ) );

	// If that didn't work, give up.
	if ( false === $time || -1 === $time ) {
		return null;
	}

	return $time;
}