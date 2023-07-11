<?php
/**
 * Block Name: Time
 * Description: Attempts to parse a time string like <code>[time]any-valid-time-string-here[/time]</code> and creates a format that shows it in the viewers local time zone.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Time;

use function WordPressdotorg\MU_Plugins\Helpers\register_assets_from_metadata;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type( __DIR__ . '/build' );
	add_filter('the_content', __NAMESPACE__ . '\transform_time_blocks', 99, 1);
}

/**
 * Builds the time block output.
 *
 * This implements replacing the raw time strings in the post content with formatted times able to be converted to local times with JS.
 *
 * @param string $content Post content.
 * @return string Content with display times reformatted.
 */
function transform_time_blocks( $content) {
	// Find the time block elements by the classname "wporg-time"
	$dom = new \DOMDocument();
	$dom->loadHTML( $content );
	$xpath = new \DOMXPath( $dom );
	$time_elements = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' wporg-time ')]" );
	
	foreach ( $time_elements as $time_element ) {
		$time_content = $time_element->nodeValue;
		$parsed_time = parse_time( $time_content );

		if ( $parsed_time === null ) {
			continue;
		}
		
		// Build the link and abbr microformat.
		$time_element->setAttribute( 'href', 'https://www.timeanddate.com/worldclock/fixedtime.html?iso=' . gmdate( 'Ymd\THi', $parsed_time ) );

		$new_time_content = $dom->createElement( 'abbr', $time_content );
		$new_time_content->setAttribute( 'class', 'wporg-time-date' );
		$new_time_content->setAttribute( 'title', gmdate( 'c', $parsed_time ) );

		// Replace the raw time with the formatted time
		$time_element->nodeValue = null;
		$time_element->appendChild( $new_time_content );
	}

	$content = $dom->saveHTML();

	return $content;
}

/**
 * Parse the datetime description string and return a timestamp.
 *
 * @param string $content Datetime description, eg. `Monday, April 6 at 19:00 UTC`
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

	// Add the time converter JS code.
	if ( ! has_action( 'wp_footer', __NAMESPACE__ . '\time_converter_script' ) ) {
		add_action( 'wp_footer', __NAMESPACE__ . '\time_converter_script', 999 );
	}

	return $time;
}

/**
 * Prints script to convert time in the viewers local time zone.
 */
function time_converter_script() {
	?>
	<script type="text/javascript" id="time_converter_script">
		( function() {
			function convertTimes() {
				const parseDate = function( text ) {
					var m = /^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})\+00:00$/.exec( text );

					return new Date(
						// Date.UTC(year, monthIndex (0..11), day, hour, minute, second)
						Date.UTC( + m[1], + m[2] - 1, + m[3], + m[4], + m[5], + m[6] )
					);
				};

				const formatTime = function( d ) {
					return d.toLocaleTimeString( navigator.language, {
						weekday     : 'long',
						month       : 'long',
						day         : 'numeric',
						year        : 'numeric',
						hour        : '2-digit',
						minute      : '2-digit',
						timeZoneName: 'short'
					} );
				};

				const formatDate = function( d ) {
					return d.toLocaleDateString( navigator.language, {
						weekday: 'long',
						month  : 'long',
						day    : 'numeric',
						year   : 'numeric'
					} );
				};

				// Not all browsers, particularly Safari, support arguments to .toLocaleTimeString().
				const toLocaleTimeStringSupportsLocales = (
					function() {
						try {
							new Date().toLocaleTimeString( 'i' );
						} catch ( e ) {
							return e.name === 'RangeError';
						}

						return false;
					}
				)();

				document.querySelectorAll('.wporg-time-date').forEach( ( dateElement ) => {
					let localTime = '';
					const date = parseDate( dateElement.getAttribute( 'title' ) );

					if ( date ) {
						if ( ! toLocaleTimeStringSupportsLocales ) {
							localTime += formatDate( date );
							localTime += ' ';
						}

						localTime += formatTime( date );

						dateElement.innerText = localTime;
					}
				} );
			}

			document.addEventListener( 'DOMContentLoaded', () => {
				convertTimes();
			} );
		} )();
	</script>
<?php
}
