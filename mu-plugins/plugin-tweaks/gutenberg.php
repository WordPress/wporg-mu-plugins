<?php

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks\Gutenberg;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_filter( 'render_block_core/post-title', __NAMESPACE__ . '\swap_h0_for_paragraph', 20 );
add_filter( 'render_block_core/query-title', __NAMESPACE__ . '\swap_h0_for_paragraph', 20 );
add_filter( 'wp_script_attributes', __NAMESPACE__ . '\inject_module_cachebuster' );

// Remove duplicate elements generation, can be removed after GB18.3+ is active.
// See https://github.com/WordPress/wporg-parent-2021/issues/135.
remove_filter( 'render_block_data', 'wp_render_elements_support_styles', 10, 1 );

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

/**
 * Add a custom cachebuster to the module scripts.
 *
 * See https://a8c.slack.com/archives/C0393K4ADM3/p1709930043067369
 *
 * @param array $attributes Key-value pairs representing `<script>` tag attributes.
 *
 * @return array
 */
function inject_module_cachebuster( $attributes ) {
	if ( ! isset( $attributes['src'], $attributes['type'] ) ) {
		return $attributes;
	}

	if ( 'module' !== $attributes['type'] ) {
		return $attributes;
	}

	$cachebuster = '20240308';

	$source = $attributes['src'];
	wp_parse_str( wp_parse_url( $source, PHP_URL_QUERY ), $source_query );
	$version = $source_query['ver'] ?? '';

	$source = str_replace(
		"ver={$version}",
		"ver={$version}-{$cachebuster}",
		$source
	);

	$attributes['src'] = $source;
	return $attributes;
}
