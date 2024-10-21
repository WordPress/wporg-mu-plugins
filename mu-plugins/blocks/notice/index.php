<?php
/**
 * Block Name: Notice Block
 * Description: Add a color-coded notice to your post.
 */

namespace WordPressdotorg\MU_Plugins\Notice;

// Run after `WPorg_Handbook_Callout_Boxes` registers the shortcodes.
add_action( 'init', __NAMESPACE__ . '\init', 11 );

add_filter( 'pre_render_block', __NAMESPACE__ . '\render_callout_block_as_notice', 11, 2 );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type( __DIR__ . '/build' );

	foreach ( [ 'info', 'tip', 'alert', 'tutorial', 'warning' ] as $shortcode ) {
		remove_shortcode( $shortcode );
		add_shortcode( $shortcode, __NAMESPACE__ . '\render_callout_as_notice' );
	}
}

/**
 * Display a callout using the notice block.
 *
 * @param array|string $attr    Callout shortcode attributes array or empty string.
 * @param string       $content Callout content.
 * @param string       $tag     Callout type.
 *
 * @return string Callout output as HTML markup.
 */
function render_callout_as_notice( $attr, $content, $tag ) {
	$shortcode_mapping = array(
		'info'     => 'info',
		'tip'      => 'tip',
		'alert'    => 'alert',
		'tutorial' => 'tip',
		'warning'  => 'warning',
	);

	$type = $shortcode_mapping[ $tag ] ?: 'tip';

	// Sanitize message content.
	$content = wp_kses_post( $content );
	// Temporarily disable o2 processing while formatting content.
	add_filter( 'o2_process_the_content', '__return_false', 1 );
	$content = apply_filters( 'the_content', $content );
	remove_filter( 'o2_process_the_content', '__return_false', 1 );

	// Create a unique placeholder for the content.
	// Directly processing `$content` with `do_blocks` can lead to buggy layouts on make.wp.org.
	// See https://github.com/WordPress/wporg-mu-plugins/pull/337#issuecomment-1819992059.
	$placeholder = '<!-- CONTENT_PLACEHOLDER -->';

	$block_markup = <<<EOT
<!-- wp:wporg/notice {"type":"$type"} -->
<div class="wp-block-wporg-notice is-{$type}-notice">
<div class="wp-block-wporg-notice__icon"></div>
<div class="wp-block-wporg-notice__content">$placeholder</div></div>
<!-- /wp:wporg/notice -->
EOT;

	$processed_markup = do_blocks( $block_markup );
	$final_markup = str_replace( $placeholder, $content, $processed_markup );

	return $final_markup;
}

/**
 * Renders a callout block as a notice.
 *
 * @param string|null $pre_render The pre-rendered content or null.
 * @param array       $parsed_block The parsed block array.
 * @return string|null The rendered notice or the original pre-render value.
 */
function render_callout_block_as_notice( $pre_render, $parsed_block ) {
	if ( is_admin() || 'wporg/callout' !== $parsed_block['blockName'] ) {
		return $pre_render;
	}

	$callout_wrapper = $parsed_block['innerHTML'];
	// Extract the specific "callout-*" class and remove the "callout-" prefix
	preg_match( '/\bcallout-([\w-]+)\b/', $callout_wrapper, $matches );
	$tag = $matches[1] ?? 'tip';

	$content = '';
	foreach ( $parsed_block['innerBlocks'] as $inner_block ) {
		$content .= render_block( $inner_block );
	}

	return render_callout_as_notice( '', $content, $tag );
}
