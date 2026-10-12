<?php
/**
 * Block Name: Copy Page
 * Description: A button that copies the Markdown version of the current page, with a link to view it.
 * Only added in templates (code), not enabled in the editor.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Copy_Page_Block;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type( __DIR__ . '/build' );
}

/**
 * Get the Markdown URL for the current page.
 *
 * WordPress.org serves a Markdown rendering of any page when `?output_format=md`
 * is appended to its URL (or when the request sends `Accept: text/markdown`).
 * The URL is built from the current request, so it also works on archives and
 * other non-singular pages.
 *
 * @return string The URL.
 */
function get_markdown_url() {
	if ( wp_is_serving_rest_request() ) {
		// Rendered through the block-renderer endpoint (editor preview): the request URL
		// is the REST route, so point at the home page instead of a JSON endpoint.
		$url = home_url( '/?output_format=md' );
	} else {
		// Built from the request URI, forced to be root-relative so a request like
		// `//evil.example/x` can't turn it into a protocol-relative URL to another host.
		$url = '/' . ltrim( add_query_arg( 'output_format', 'md' ), '/\\' );
	}

	/**
	 * Filters the URL of the Markdown rendering of the current page.
	 *
	 * @param string $url The Markdown URL.
	 */
	return apply_filters( 'wporg_copy_page_markdown_url', $url );
}

/**
 * Map a copy status to the key of the label shown for it.
 *
 * @param string $status One of 'idle', 'loading', 'copied' or 'error'.
 * @return string 'copy', 'copied' or 'error'.
 */
function get_label_key( $status ) {
	return in_array( $status, array( 'copied', 'error' ), true ) ? $status : 'copy';
}
