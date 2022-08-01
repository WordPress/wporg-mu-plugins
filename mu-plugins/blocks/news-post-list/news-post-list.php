<?php
/**
 * Plugin Name: News Post List
 * Description: A block for use across the whole wp.org network.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\wporg;


function get_categories_via_api( $endpoint ) {
	$response = wp_remote_get( esc_url_raw( $endpoint . '/categories' ) );

	if( is_wp_error( $response ) ) {
		return false;
	}
	
	return json_decode( wp_remote_retrieve_body($response) );
}

function get_by_id( $arr, $id ) {
	foreach ( $arr as $item ) {
		if( $id == $item->id ) {
			return $item;
		}
	}

	return '';
}

function get_category( $endpoint, $id ) {	
	$categories = get_categories_via_api( $endpoint );

	if( is_wp_error( $categories ) ) {
		return '';
	}

	return get_by_id( $categories, $id );
}



function get_posts_via_api( $endpoint, $post_type = 'posts', $limit = 10 ) {
	$url = $endpoint . '/' . $post_type . '?per_page=' . $limit;

	$response = wp_remote_get( esc_url_raw( $url ) );

	if( is_wp_error( $response ) ) {
		return false;
	}

	return json_decode( wp_remote_retrieve_body($response) );
}

function render_block( $attributes ) {
	if( ! isset( $attributes['endpoint'] ) ) {
		return '';
	}

	$posts = get_posts_via_api( $attributes['endpoint'] );

	$list_items = "";
	foreach ( $posts as $post ) {
		$category = get_category( $attributes['endpoint'], $post->categories[0] );

		var_dump( $post->categories[0] );

		$link = sprintf(
			'<a href="%1$s">%2$s</a>',
			$post->link,
			$post->title->rendered
		);


		$list_items .= sprintf(
			'<li>%1$s, %2$s category: %3$s</li>',
			$link,
			! empty( $category ) ? $category->name : '',
			$post->date,
		);
	}

	return sprintf( '<ul>%s</ul>', $list_items );
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function news_post_list_block_init() {
	register_block_type( __DIR__ . '/build',
	array(
		'render_callback' => __NAMESPACE__ . '\render_block',
	));
}
add_action( 'init', __NAMESPACE__ . '\news_post_list_block_init' );
