<?php
/**
 * Plugin Name: Multisite Latest Posts
 * Description: A block for use across the whole wp.org network.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\wporg;

function get_categories_via_api( $endpoint ) {
	$response = wp_remote_get( esc_url_raw( $endpoint . '/categories' . '?per_page=100' ) );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	return json_decode( wp_remote_retrieve_body( $response ) );
}

function get_by_id( $arr, $id ) {
	foreach ( $arr as $item ) {
		if ( $id == $item->id ) {
			return $item;
		}
	}

	return '';
}

function get_category( $endpoint, $id ) {
	$categories = get_categories_via_api( $endpoint );

	if ( is_wp_error( $categories ) ) {
		return '';
	}

	return get_by_id( $categories, $id );
}

function get_posts_via_api( $endpoint, $post_type = 'posts', $limit = 10 ) {
	$url = $endpoint . '/' . $post_type . '?_embed=true&per_page=' . $limit;

	$response = wp_remote_get( esc_url_raw( $url ) );

	if ( is_wp_error( $response ) ) {
		return [];
	}

	return json_decode( wp_remote_retrieve_body( $response ) );
}

function render_block( $attributes ) {
	if ( ! isset( $attributes['endpoint'] ) || ! isset( $attributes['itemsToShow'] ) ) {
		return '';
	}

	$posts = get_posts_via_api( $attributes['endpoint'], 'posts', $attributes['itemsToShow'] );

	$list_items = '';
	foreach ( $posts as $post ) {
		$title_element = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_html( $post->link ),
			esc_html( $post->title->rendered )
		);

		$category_element = '';
		if ( isset( $post->categories ) && isset( $post->categories[0] ) ) {
			$category = get_category( $attributes['endpoint'], $post->categories[0] );

			if ( ! empty( $category ) ) {
				$category_element = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_html( $category->link ),
					esc_html( $category->name )
				);
			}
		}

		$date = new \DateTime( $post->date );
		$date_element = sprintf(
			'<time datetime="%1$s">%2$s</time>',
			$date->format( 'c' ),
			$date->format( 'F j, Y' )
		);

		$list_items .= sprintf(
			'<li>%1$s <div>%2$s %3$s %4$s</div></li>',
			$title_element,
			$category_element,
			! empty( $category_element ) ? '<span>·</span>' : '',
			$date_element,
		);
	}

	return sprintf( '<ul class="wporg-multisite-latest-posts">%s</ul>', $list_items );
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function multisite_latest_posts_block_init() {
	register_block_type(
		__DIR__ . '/build',
		array(
			'render_callback' => __NAMESPACE__ . '\render_block',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\multisite_latest_posts_block_init' );
