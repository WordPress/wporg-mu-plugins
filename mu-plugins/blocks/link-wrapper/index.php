<?php
/**
 * Block Name: Link Wrapper
 * Description: Link a set of blocks to a given page.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Link_Wrapper_Block;

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
	register_block_type(
		__DIR__ . '/build',
		array(
			'render_callback' => __NAMESPACE__ . '\render',
		)
	);
}

/**
 * Renders the Link Wrapper block.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content.
 * @return string Rendered block HTML.
 */
function render( $attributes, $content ) {
	$wrapper_attributes = get_block_wrapper_attributes();
	$link  = isset( $attributes['url'] ) ? ' ' . $attributes['url'] : get_permalink();

	return sprintf(
		'<a href="%1$s" %2$s>%3$s</a>',
		esc_url( $link ),
		$wrapper_attributes,
		do_blocks( $content )
	);
}
