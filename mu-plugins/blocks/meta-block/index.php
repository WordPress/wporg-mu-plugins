<?php
/**
 * Block Name: Meta Block
 * Description: A server-side dynamic block to set meta fields for SEO.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Meta_Block;

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function meta_block_init() {
	register_block_type(
		__DIR__ . '/build',
		array(
			'render_callback' => __NAMESPACE__ . '\render',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\meta_block_init' );


/**
 * Callback to render block content. In this case, there is no content, but a
 * filter is added to override the default page description (if the description
 * attribute exists).
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the block markup.
 */
function render( $attributes, $content, $block ) {
	if ( ! isset( $attributes['description'] ) ) {
		return '';
	}

	add_filter(
		'jetpack_open_graph_tags',
		function( $tags ) use ( $attributes ) {
			$tags['og:description']      = $attributes['description'];
			$tags['twitter:description'] = $attributes['description'];
			$tags['description']         = $attributes['description'];

			return $tags;
		},
		20 // This should run after anything in the theme.
	);

	return '';
}
