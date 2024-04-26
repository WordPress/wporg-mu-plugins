<?php
/**
 * Plugin name: Gutenberg: Pattern Previewer
 * Description: A block that displays a pattern.
 * Version:     2.0
 * Author:      WordPress.org
 * Author URI:  http://wordpress.org/
 * License:     GPLv2 or later
 */

namespace WordPressdotorg\Gutenberg\ScreenshotPreview;

defined( 'WPINC' ) || die();

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_assets', 20 );

/**
 * Register scripts, styles, and block.
 */
function register_assets() {
	$deps_path = __DIR__ . '/build/index.asset.php';

	if ( ! file_exists( $deps_path ) ) {
		return;
	}

	$block_info = require $deps_path;

	if ( ! is_admin() && function_exists( 'wporg_themes_init' ) ) {
		wp_enqueue_script(
			'wporg-mshots-preview',
			plugin_dir_url( __FILE__ ) . 'build/index.js',
			$block_info['dependencies'],
			$block_info['version'],
			true
		);

		wp_enqueue_style(
			'wporg-mshots-preview-style',
			plugin_dir_url( __FILE__ ) . '/build/style.css',
			array(),
			filemtime( __DIR__ . '/build/style.css' )
		);

		wp_style_add_data( 'wporg-mshots-preview-style', 'rtl', 'replace' );
	}
}
