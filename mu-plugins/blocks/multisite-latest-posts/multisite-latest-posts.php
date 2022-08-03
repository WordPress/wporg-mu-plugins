<?php
/**
 * Plugin Name: Multisite Latest Posts
 * Description: A block for use across the whole wp.org network.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Multisite_Latest_Posts;

class WPORG_Latest_Post {
	public $post_date;
	public $post_title;
	public $post_link;
	public $post_category_name;
	public $post_category_link;
}

/**
 * Helper function to find item in array by id.
 *
 * @param WP_Term[] $arr List of categories
 * @param number    $id Category ID
 * @return WP_Term|string
 */
function get_by_id( $arr, $id ) {
	foreach ( $arr as $item ) {
		if ( $id == $item->id ) {
			return $item;
		}
	}

	return '';
}

/**
 * Fetches categories from REST endpoint.
 *
 * @param string $endpoint URL
 * @return WP_Term[]|WP_Error
 */
function get_categories_via_api( $endpoint ) {
	$response = wp_remote_get( esc_url_raw( $endpoint . '/categories' . '?per_page=100' ) );

	if ( is_wp_error( $response ) ) {
		return new \WP_Error( 500, __( 'An error has occurred fetching categories.', 'wporg' ) );
	}

	$body = wp_remote_retrieve_body( $response );

	if ( empty( $body ) ) {
		return new \WP_Error( 500, __( 'An error has occurred fetching categories.', 'wporg' ) );
	}

	return json_decode( $body );
}

/**
 * Returns the category based on its ID.
 *
 * @param string $endpoint URL
 * @param number $id Category ID
 * @return WP_Term[]|string
 */
function get_categories( $endpoint ) {
	$categories = get_categories_via_api( $endpoint );

	// We are okay if we can't find the category
	if ( is_wp_error( $categories ) ) {
		return '';
	}

	return $categories;
}

/**
 * Returns a list of posts.
 *
 * @param string  $endpoint URL
 * @param string  $post_type WP_Post_Type label
 * @param integer $limit Numbers of posts to return
 * @return WP_Post[]|WP_Error
 */
function get_posts_via_api( $endpoint, $post_type = 'posts', $limit = 100 ) {
	$url = $endpoint . '/' . $post_type . '?_embed=true&per_page=' . $limit;

	$response = wp_remote_get( esc_url_raw( $url ) );

	if ( is_wp_error( $response ) ) {
		return new \WP_Error( 500, __( 'An error has occurred loading posts.', 'wporg' ) );
	}

	// Returns empty string if anything goes wrong
	$body = wp_remote_retrieve_body( $response );

	if ( empty( $body ) ) {
		return [];
	}

	$posts = json_decode( $body );
	$categories = get_categories( $endpoint );

	$results = array();
	foreach ( $posts as $post ) {
		$latest_post = new WPORG_Latest_Post();
		$latest_post->post_title = $post->title->rendered;
		$latest_post->post_link = $post->link;
		$latest_post->post_date = $post->date;

		if ( isset( $post->categories ) && isset( $post->categories[0] ) ) {
			$category = get_by_id( $categories, $post->categories[0] );
			var_dump( $category );
			if ( ! empty( $category ) ) {
				$latest_post->post_category_name = $category->name;
				$latest_post->post_category_link = $category->term_id;
			}
		}

		$results[] = $latest_post;
	}

	return $results;
}

/**
 * Undocumented function
 *
 * @param [type]  $endpoint URL
 * @param [type]  $blog_id A blog's id
 * @param integer $limit Numbers of posts to return
 * @return WPORG_Latest_Post[]|WP_Error
 */
function get_post_results( $endpoint, $blog_id, $limit = 3 ) {

	// If we don't have a blog id we'll try a rest call
	if ( ! is_multisite() || ! isset( $blog_id ) ) {
		if ( empty( $endpoint ) ) {
			return new \WP_Error( 500, __( 'This block is configured incorrectly.', 'wporg' ) );
		}

		return get_posts_via_api( $endpoint, 'posts', $limit );
	}

	switch_to_blog( $blog_id );

	$posts = wp_get_recent_posts(
		array(
			'numberposts' => $limit,
			'post_status' => 'publish',
		)
	);

	$results = array();
	foreach ( $posts as $post ) {
		$latest_post = new WPORG_Latest_Post();
		$latest_post->post_title = $post['post_title'];
		$latest_post->post_link = get_permalink( $post['ID'] );
		$latest_post->post_date = $post['post_date'];

		$category = get_the_category( $post['ID'] );

		if ( isset( $category[0] ) ) {
			$latest_post->post_category_name = $category[0]->name;
			$latest_post->post_category_link = get_category_link( $category[0]->term_id );
		}

		$results[] = $latest_post;
	}

	restore_current_blog();

	return $results;
}

function render_block( $attributes ) {
	// Check cache
	$posts = get_transient( __NAMESPACE__ );

	if ( ! $posts ) {
		$posts = get_post_results( $attributes['endpoint'], isset( $attributes['blogId'] ) ? $attributes['blogId'] : '', $attributes['itemsToShow'] );

		if ( is_wp_error( $posts ) ) {
			return $posts->get_error_message();
		}

		// Set Cache
		set_transient( __NAMESPACE__, $posts, HOUR_IN_SECONDS );
	}

	if ( empty( $posts ) ) {
		return __( 'No posts found.' );
	}

	$list_items = '';
	foreach ( $posts as $post ) {
		$title_element = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_html( $post->post_link ),
			esc_html( $post->post_title )
		);

		$category_element = '';
		if ( ! empty( $post->post_category_name ) ) {
			$category_element = sprintf(
				'<a href="%1$s" class="wporg-multisite-latest-posts-category">%2$s</a>',
				esc_html( $post->post_category_link ),
				esc_html( $post->post_category_name )
			);
		}

		$date = new \DateTime( $post->post_date );
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

	return sprintf( '<ul class="wporg-multisite-latest-posts wp-block-wporg-multisite-latest-posts">%s</ul>', $list_items );
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
