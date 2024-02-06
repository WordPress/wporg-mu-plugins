<?php

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks\Gutenberg;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_filter( 'render_block_core/post-title', __NAMESPACE__ . '\swap_h0_for_paragraph', 20 );
add_filter( 'render_block_core/query-title', __NAMESPACE__ . '\swap_h0_for_paragraph', 20 );

/**
 * Replace invalid `h0` tags with paragraphs.
 *
 * Setting the `level` to 0 technically works for site-title, post-title,
 * and query-title, but the latter two don't do any validation before outputting
 * `<h{level}>`, so we end up with the invalid `h0` when trying to remove
 * heading semantics.
 *
 * @param string $block_content The block content.
 *
 * @return string The updated block content.
 */
function swap_h0_for_paragraph( $block_content ) {
	return str_replace(
		array( '<h0', '</h0>' ),
		array( '<p', '</p>' ),
		$block_content
	);
}

