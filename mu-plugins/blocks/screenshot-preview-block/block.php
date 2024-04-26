<?php
/**
 * Block Name: Screenshot Preview
 * Description: Show a preview of a website.
 */

namespace WordPressdotorg\MU_Plugins\ScreenshotPreview_Block;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Registers the `wporg/screenshot-preview` block on the server.
 */
function init() {
	register_block_type( __DIR__ . '/build/' );
}
