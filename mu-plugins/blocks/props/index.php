<?php
/**
 * Block Name: Notice Block
 * Description: Add a color-coded notice to your post.
 */

namespace WordPressdotorg\MU_Plugins\Props;

add_action( 'init', __NAMESPACE__ . '\init' );
add_action( 'save_post', __NAMESPACE__ . '\handle_save_post', 10, 2 );

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
 * Handles syncing the props to WordPress.org when publishing/updating a post with the props block in it
 *
 * @param int      $post_id The post id that is saved.
 * @param \WP_Post $post The post object with the new data.
 */
function handle_save_post( $post_id, $post ) {
	if ( 'publish' !== $post->post_status ) {
		return;
	}

	if ( ! has_block( 'wporg/props', $post ) ) {
		return;
	}

	$existing_handled_props = get_post_meta( $post_id, '_wporg_props', true );
	if ( empty( $existing_handled_props ) ) {
		$existing_handled_props = array();
	}

	$props_blocks = search_props_block( parse_blocks( $post->post_content ) );

	$props_to_handle = array();
	foreach ( $props_blocks as $block ) {
		$props           = parse_props( $block['innerHTML'] );
		$props_to_handle = array_merge( $props_to_handle, $props );
	}

	// TODO: does not remove props.
	$new_props  = array_diff( $props_to_handle, $existing_handled_props );
	$post_link  = get_permalink( $post_id );
	$post_title = get_the_title( $post_id );
	foreach ( $new_props as $prop_user ) {
		WordPressdotorg\Profiles\add_activity(
			/* translators: link to post the user has received props in */
			sprintf( __( 'Received props in %s', 'wporg' ), '<a href="' . esc_url( $post_link ) . '">' . esc_html( $post_title ) . '</a>' ),
			'', // TODO: which type?
			$prop_user
		);
	}

	$handled_props = array_unique( array_merge( $existing_handled_props, $new_props ) );
	update_post_meta( $post_id, '_wporg_props', $handled_props );
}

/**
 * Search for top-level and nested props blocks in an array of blocks
 *
 * @param mixed $blocks An array of blocks to search in (eg from parse_blocks or nested innerBlocks).
 * @return array All found blocks of the props blockType.
 */
function search_props_block( $blocks ) {
	$props_blocks = array();
	foreach ( $blocks as $block ) {
		if ( 'wporg/props' === $block['blockName'] ) {
			$props_blocks[] = $block;
			continue;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$nested_blocks = search_props_block( $block['innerBlocks'] );
			$props_blocks  = array_merge( $props_blocks, $nested_blocks );
		}
	}

	return $props_blocks;
}

/**
 * Parses props (=user mentions) from the HTML block content
 *
 * Regex for parsing taken from bbPress: bbp_find_mentions
 *
 * @param string $block_content The innerHTML from the block.
 * @return string[]
 */
function parse_props( $block_content ) {
	if ( function_exists( 'bbp_find_mentions' ) ) {
		return bbp_find_mentions( $block_content );
	}
	$pattern = '/[@]+([A-Za-z0-9-_\.@]+)\b/';
	preg_match_all( $pattern, $block_content, $usernames );
	$usernames = array_unique( $usernames[1] );
	return $usernames;
}
