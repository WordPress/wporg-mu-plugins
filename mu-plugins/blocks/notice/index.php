<?php
/**
 * Block Name: Notice Block
 * Description: Add a color-coded notice to your post.
 */

namespace WordPressdotorg\MU_Plugins\Notice;

const CALLOUT_SHORTCODES = [ 'info', 'tip', 'alert', 'tutorial', 'warning' ];

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

	foreach ( CALLOUT_SHORTCODES as $shortcode ) {
		remove_shortcode( $shortcode );
		add_shortcode( $shortcode, __NAMESPACE__ . '\render_shortcode' );
	}
}

/**
 * Maps a callout type to a notice type.
 *
 * @param string $type The callout type.
 * @return string The corresponding notice type.
 */
function map_type( $type ) {
	$mapping = array(
		'info'     => 'info',
		'tip'      => 'tip',
		'alert'    => 'alert',
		'tutorial' => 'tip',
		'warning'  => 'warning',
	);

	return $mapping[ $type ] ?: 'tip';
}

/**
 * Highlights code in callout shortcodes before wpautop and wptexturize can alter it.
 *
 * SyntaxHighlighter parses its shortcodes at priority 7 but skips those nested in
 * other shortcodes, which would leave code in callouts to the formatting filters.
 *
 * @param string $content Post content.
 * @return string Post content with code in callouts highlighted.
 */
function highlight_code_in_callouts( $content ) {
	$syntax_highlighter = $GLOBALS['SyntaxHighlighter'] ?? null;

	if ( ! $syntax_highlighter instanceof \SyntaxHighlighter || ! str_contains( $content, '[/' ) ) {
		return $content;
	}

	// Leave shortcodes in HTML tags alone, as do_shortcode() does.
	$content = do_shortcodes_in_html_tags( $content, true, CALLOUT_SHORTCODES );
	$content = preg_replace_callback(
		'/' . get_shortcode_regex( CALLOUT_SHORTCODES ) . '/',
		function ( $m ) use ( $syntax_highlighter ) {
			// Skip escaped and self-closing shortcodes.
			if ( '[' === $m[1] || '' === $m[5] ) {
				return $m[0];
			}

			return "[{$m[2]}{$m[3]}]" . $syntax_highlighter->parse_shortcodes( $m[5] ) . "[/{$m[2]}]";
		},
		$content
	);

	return unescape_invalid_shortcodes( $content );
}
add_filter( 'the_content', __NAMESPACE__ . '\highlight_code_in_callouts', 6 );

/**
 * Generates and processes block markup for a notice.
 *
 * @param string $type The notice type.
 * @param string $content The content to insert into the block.
 * @return string The processed block markup.
 */
function generate_notice_block_markup( $type, $content ) {
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
	return str_replace( $placeholder, $content, $processed_markup );
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
function render_shortcode( $attr, $content, $tag ) {
	$type = map_type( $tag );

	// Sanitize message content.
	$content = wp_kses_post( $content );
	// Temporarily disable o2 processing while formatting content.
	add_filter( 'o2_process_the_content', '__return_false', 1 );
	$content = apply_filters( 'the_content', $content );
	remove_filter( 'o2_process_the_content', '__return_false', 1 );

	return generate_notice_block_markup( $type, $content );
}

/**
 * Renders a callout block as a notice block.
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
	$tag  = $matches[1] ?? 'tip';
	$type = map_type( $tag );

	$content = '';
	foreach ( $parsed_block['innerBlocks'] as $inner_block ) {
		$content .= render_block( $inner_block );
	}
	// Sanitize message content.
	$content = wp_kses_post( $content );

	return generate_notice_block_markup( $type, $content );
}
