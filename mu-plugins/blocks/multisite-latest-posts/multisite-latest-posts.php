<?php
/**
 * Plugin Name: Multisite Latest Posts
 * Description: A block for use across the whole wp.org network.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Multisite_Latest_Posts;

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_assets', 20 );
add_action( 'init', __NAMESPACE__ . '\multisite_latest_posts_block_init' );

/**
 * Renders the `wporg/mulsite-latest-posts` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the event year for the current post.
 */
function render_block( $attributes ) {
	return sprintf(
		'<div 
			class="wporg-multisite-latest-posts-js"
			data-endpoint="%1$s" 
			data-items-to-show="%2$s" 
		></div>',
		esc_attr( $attributes['endpoint'] ),		
		esc_attr( $attributes['itemsToShow'] )
	);
}


/**
 * Register scripts, styles, and block.
 */
function register_assets() {
	$deps_path = __DIR__ . '/build/index.asset.php';
	
	if ( ! file_exists( $deps_path ) ) {
		return;
	}

	$block_info = require $deps_path;

	if ( ! is_admin() ) {
		wp_enqueue_script(
			'wporg-multisite-latest-post',
			plugin_dir_url( __FILE__ ) . 'build/front.js',
			$block_info['dependencies'],
			$block_info['version'],
			true
		);

		wp_enqueue_style(
			'wporg-multisite-latest-post-style',
			plugin_dir_url( __FILE__ ) . '/build/style.css',
			array(),
			filemtime( __DIR__ . '/build/style.css' )
		);

		wp_style_add_data( 'wporg-multisite-latest-post-style', 'rtl', 'replace' ); 
	}
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function multisite_latest_posts_block_init() {
	register_block_type(
		__DIR__ . '/build',
		array(
			'render_callback' => __NAMESPACE__ . '\render_block',
		)
	);
}

