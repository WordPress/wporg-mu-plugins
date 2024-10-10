<?php
/**
 * Format Name: Time
 * Description: Attempts to parse a time string like 'Tuesday, April 5th, at 15:00 UTC' relative to the post date,
 * and creates a format that shows it in the viewer's local time zone.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Time;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Enqueues the scripts for the Time format in the block editor and frontend using the metadata loaded from the `block.json` file.
 */
function init() {
	// Add the JS script to add the Time formatting option to the toolbar. The dependencies are autogenerated in block.json,
	// and can be read with `wp_json_file_decode` & `register_block_script_handle.
	$metadata_file = __DIR__ . '/build/block.json';
	$metadata = wp_json_file_decode( $metadata_file, array( 'associative' => true ) );
	$metadata['file'] = $metadata_file;

	$editor_script_handle = register_block_script_handle( $metadata, 'editorScript' );
	add_action(
		'enqueue_block_assets',
		function() use ( $editor_script_handle ) {
			if ( wp_should_load_block_editor_scripts_and_styles() && is_admin() ) {
				wp_enqueue_script( $editor_script_handle );
			}
		}
	);

	$view_script_handle = register_block_script_handle( $metadata, 'viewScript' );
	add_action(
		'wp_enqueue_scripts',
		function() use ( $view_script_handle ) {
			if ( ! is_admin() ) {
				wp_enqueue_script( $view_script_handle );
				wp_script_add_data( $view_script_handle, 'group', 1 );
			}
		}
	);

	add_filter( 'wp_kses_allowed_html', __NAMESPACE__ . '\add_time_to_allowed_html', 10, 2 );
}

/**
 * Adds the `time` tag to the allowed HTML tags for the block editor.
 *
 * @param array  $allowed_tags Allowed tags, attributes, and/or entities.
 * @param string $context      Context to judge allowed tags by.
 * @return array Allowed tags, attributes, and/or entities.
 */
function add_time_to_allowed_html( $allowed_tags, $context ) {
	if ( 'post' === $context ) {
		$allowed_tags['time'] = array(
			'class'    => true,
			'data-iso' => true,
			'datetime' => true,
		);
	}

	return $allowed_tags;
}
