<?php
/**
 * Block Name: Nav Group
 * Description: Groups children components for a sub nav.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Nav_Group;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Render the block content.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the block markup.
 */
function render( $attributes, $content, $block ) {
	$wrapper_attributes = get_block_wrapper_attributes();
	return sprintf(
		'<div %1$s>%2$s</div>',
		$wrapper_attributes,
		$content
	);
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type(
		dirname( dirname( __DIR__ ) ) . '/build/nav-group',
		array(
			'render_callback' => __NAMESPACE__ . '\render',
		)
	);
}
