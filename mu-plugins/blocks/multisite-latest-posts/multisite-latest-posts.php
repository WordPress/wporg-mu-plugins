<?php
/**
 * Plugin Name: Multisite Latest Posts
 * Description: A block for use across the whole wp.org network.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Multisite_Latest_Posts;

/**
 * Renders the `wporg/mulsite-latest-posts` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the event year for the current post.
 */
function render_block(  $attributes, $content, $block ) {
	wp_enqueue_script( $block->block_type->view_script );

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
add_action( 'init', __NAMESPACE__ . '\multisite_latest_posts_block_init' );
