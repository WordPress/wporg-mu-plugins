<?php
/**
 * Block Name: Sidebar Container
 * Description: A sticky container to be used in 2-column layouts.
 * Only added in templates (code), not enabled in the editor.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Sidebar_Container_Block;

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
	$inline_breakpoint = $attributes['inlineBreakpoint'];
	$back_to_top = $attributes['hasBackToTop']
		? sprintf(
			'<p class="has-small-font-size is-link-to-top"><a href="#wp--skip-link--target">%s</a></p>',
			esc_html__( '↑ Back to top', 'wporg' )
		)
		: '';

	return sprintf(
		'<div %1$s data-breakpoint="%2$s">%3$s%4$s</div>',
		$wrapper_attributes,
		esc_attr( $inline_breakpoint ),
		$content,
		$back_to_top
	);
}
