<?php
/**
 * Block Name: Ratings (bars)
 * Description: The breakdown of ratings displayed as bars for each rating value.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Theme_Directory_2024\Ratings_Bars_Block;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Register the block.
 */
function init() {
	register_block_type( __DIR__ . '/build' );
}
