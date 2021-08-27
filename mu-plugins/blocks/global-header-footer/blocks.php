<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\register_assets', 9 );
// why 9? if can be 10, then callers could be 9


function register_assets() {
	// don't want this visible in Inserter. need to create ticket for that?

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
 * @param array $attributes Block attributes.
 *
 * @return string
 */
function render_global_header( $attributes ) {
	/*
	todo
	meta tags included called automaticaly in FSE themes
	so the header needs to avoid adding them for FSE, or we need to disable FSE automatically adding them
	*/

	ob_start();
	require_once __DIR__ . '/universal-header.php';
	// cant include inside namespace b/c that messes things up?
	// if so, is there a way to de-scope it?

	return ob_get_clean();
}

/**
 * Render the global footer in a block context.
 *
 * @param array $attributes Block attributes.
 *
 * @return string
 */
function render_global_footer( $attributes ) {
	ob_start();
	require_once __DIR__ . '/universal-footer.php';
	return ob_get_clean();
}


// maybe make an api endpoint to serve this to codex/trac?

 // all universal logic should go inside header.php, so that Trac, the Codex, etc can load it
