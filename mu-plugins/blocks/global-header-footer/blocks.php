<?php

/*
 * This file should only contain logic related to blocks.
 * Anything that applies to loading the header as raw PHP, iframe'ing, etc, should go in `universal-header.php`.
 */

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\register_block_types', 10 );


/**
 * Register block types
 *
 * These are intentionally missing arguments like `title`, `category`, `icon`, etc, because we don't want them showing up in the Block Inserter, regardless of which theme is running.
 */
function register_block_types() {
	register_block_type(
		'wporg/global-header',
		array( 'render_callback' => __NAMESPACE__ . '\render_global_header' )
	);

	register_block_type(
		'wporg/global-footer',
		array( 'render_callback' => __NAMESPACE__ . '\render_global_footer' )
	);
}

/**
 * Render the global header in a block context.
 *
 * @return string
 */
function render_global_header() {
	ob_start();
	require_once __DIR__ . '/universal-header.php';
	return ob_get_clean();
}

/**
 * Render the global footer in a block context.
 *
 * @return string
 */
function render_global_footer() {
	ob_start();
	require_once __DIR__ . '/universal-footer.php';
	return ob_get_clean();
}
