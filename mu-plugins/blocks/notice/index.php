<?php
/**
 * Block Name: Notice Block
 * Description: Add a color-coded notice to your post.
 */

namespace WordPressdotorg\MU_Plugins\Notice;

// Run after `WPorg_Handbook_Callout_Boxes` registers the shortcodes.
add_action( 'init', __NAMESPACE__ . '\init', 11 );

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
		add_shortcode( $shortcode, __NAMESPACE__ . '\render_shortcode' );
	}
}

/**
 * Display the callout shortcodes using the notice block.
 *
 * @param array|string $attr    Shortcode attributes array or empty string.
 * @param string       $content Shortcode content.
 * @param string       $tag     Shortcode name.
 *
 * @return string Shortcode output as HTML markup.
 */
function render_shortcode( $attr, $content = '', $tag ) {
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

	$block_markup = <<<EOT
<!-- wp:wporg/notice {"type":"$type"} -->
<div class="wp-block-wporg-notice is-{$type}-notice">
<div class="wp-block-wporg-notice__icon"></div>
<div class="wp-block-wporg-notice__content">$content</div></div>
<!-- /wp:wporg/notice -->
EOT;

	return do_blocks( $block_markup );
}
