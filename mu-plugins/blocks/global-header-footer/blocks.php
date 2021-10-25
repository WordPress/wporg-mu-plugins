<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\register_block_types' );

/**
 * Register block types
 *
 * These are intentionally missing arguments like `title`, `category`, `icon`, etc, because we don't want them
 * showing up in the Block Inserter, regardless of which theme is running.
 */
function register_block_types() {
	wp_register_style(
		'wporg-global-header-footer',
		plugins_url( '/build/style.css', __FILE__ ),
		array( 'wp-block-library' ), // Load `block-library` styles first, so that our styles override them.
		filemtime( __DIR__ . '/build/style.css' )
	);

	register_block_type(
		'wporg/global-header',
		array(
			'render_callback' => __NAMESPACE__ . '\render_global_header',
			'style'           => 'wporg-global-header-footer',
			'editor_style'    => 'wporg-global-header-footer',
		)
	);

	register_block_type(
		'wporg/global-footer',
		array(
			'render_callback' => __NAMESPACE__ . '\render_global_footer',
			'style'           => 'wporg-global-header-footer',
			'editor_style'    => 'wporg-global-header-footer',
		)
	);
}

/**
 * Render the global header in a block context.
 *
 * @return string
 */
function render_global_header() {
	ob_start();

	// Allow multiple includes for the `site-header-offset` workaround.
	require __DIR__ . '/header.php';
	return do_blocks( ob_get_clean() );
}

/**
 * Render the global footer in a block context.
 *
 * @return string
 */
function render_global_footer() {
	ob_start();
	require_once __DIR__ . '/footer.php';
	return do_blocks( ob_get_clean() );
}
