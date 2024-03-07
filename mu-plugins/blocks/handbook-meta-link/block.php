<?php
namespace WordPressdotorg\MU_Plugins\Handbook_Meta_Link;

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
	$post_type = $block->context['postType'];
	$post_id = $block->context['postId'];

	if (
		! isset( $post_id ) || ! isset( $post_type )
	) {
		return '';
	}

	$title = get_the_title( $post_id );
	$link_url = $attributes['linkURL'];

	return sprintf(
		do_blocks(
			'<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">

				<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"700"}}} -->
				<p style="font-style:normal;font-weight:700">%1$s</p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"external-link"} -->
				<p class="external-link"><a href="%2$s">%3$s</a></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->'
		),
		esc_html( $attributes['heading'] ),
		// Don't use esc_url for a shortcode.
		preg_match( '/^\[.*\]$/', $link_url ) ? esc_html( $link_url ) : esc_url( $link_url ),
		sprintf(
			/* translators: %1$s: call to action, %2$s: article title */
			__( '%1$s<span class="screen-reader-text">: %2$s"</span>', 'wporg' ),
			esc_html( $attributes['linkText'] ),
			esc_html( $title )
		)
	);
}
