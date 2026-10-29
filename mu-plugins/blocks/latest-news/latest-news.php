<?php
/**
 * Block Name: Latest News
 * Description: A block for use across the whole wp.org network.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Latest_News;

use function WordPressdotorg\MU_Plugins\Global_Header_Footer\is_rosetta_site;

/**
 * Days after which a Rosetta site's newest post no longer counts as recent news.
 */
const ROSETTA_STALE_AFTER_DAYS = 365;

/**
 * Determines whether we can switch blogs.
 *
 * @return boolean
 */
function should_switch_to_blog() {
	return function_exists( 'is_multisite' ) && is_multisite();
}

/**
 * Gets the current site's most recent published posts, cached for an hour.
 *
 * @param int $per_page Number of posts.
 *
 * @return array[] Posts as arrays, newest first.
 */
function get_recent_posts( $per_page ) {
	$cache_key = 'wporg-latest-news-' . $per_page;
	$posts     = get_transient( $cache_key );
	if ( ! $posts ) {
		$posts = wp_get_recent_posts(
			array(
				'numberposts' => $per_page,
				'post_status' => 'publish',
			)
		);

		if ( $posts ) {
			set_transient( $cache_key, $posts, HOUR_IN_SECONDS );
		}
	}

	return $posts;
}

/**
 * Determines whether a list of posts is empty or its newest post is older than the Rosetta cutoff.
 *
 * @param array[] $posts Posts as arrays, newest first.
 *
 * @return bool
 */
function is_stale( $posts ) {
	if ( empty( $posts ) ) {
		return true;
	}

	$newest = new \DateTimeImmutable( $posts[0]['post_date'], wp_timezone() );
	$cutoff = new \DateTimeImmutable( '-' . ROSETTA_STALE_AFTER_DAYS . ' days', wp_timezone() );

	return $newest < $cutoff;
}

/**
 * Renders the `wporg/latest-news` block on server.
 *
 * @param array $attributes The block attributes.
 *
 * @return string Returns the block content with received post items.
 */
function render_block( $attributes ) {
	$defaults      = array(
		'perPage' => 3,
	);
	$attributes    = wp_parse_args( $attributes, $defaults );
	$blog_switched = false;

	$blog_id = isset( $attributes['blogId'] ) ? (int) $attributes['blogId'] : 0;

	/*
	 * Fail closed on an unknown id: switching would re-point $wpdb at a missing table
	 * prefix, and falling through would render the current site's posts as news.
	 *
	 * @todo Prevent switch if Rosetta, Rosetta should use local posts.
	 */
	if ( should_switch_to_blog() && $blog_id ) {
		if ( ! get_site( $blog_id ) ) {
			return '';
		}
		switch_to_blog( $blog_id );
		$blog_switched = true;
	}

	// finally guarantees restore; an unrestored switch leaks into the rest of the page's queries and capability checks.
	try {
		$posts = get_recent_posts( $attributes['perPage'] );

		// A Rosetta site that hasn't posted in a while shows the News blog's posts instead, so its homepage stays current.
		if (
			! $blog_switched
			&& should_switch_to_blog()
			&& defined( 'WPORG_NEWS_BLOGID' )
			&& function_exists( 'WordPressdotorg\MU_Plugins\Global_Header_Footer\is_rosetta_site' )
			&& is_rosetta_site()
			&& is_stale( $posts )
		) {
			switch_to_blog( WPORG_NEWS_BLOGID );
			$blog_switched = true;
			$posts         = get_recent_posts( $attributes['perPage'] );
		}

		if ( empty( $posts ) ) {
			return __( 'No posts found.', 'wporg' );
		}

		$class_name = '';
		if ( 0 === count( $posts ) % 4 ) {
			$class_name = 'is-4-column';
		} elseif ( 0 === count( $posts ) % 3 ) {
			$class_name = 'is-3-column';
		} elseif ( 0 === count( $posts ) % 2 ) {
			$class_name = 'is-2-column';
		}

		$list_items = '';

		foreach ( $posts as $post ) {
			$title_element = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( get_permalink( $post['ID'] ) ),
				esc_html( $post['post_title'] )
			);

			$category_element = '';
			$category         = get_the_category( $post['ID'] );

			if ( ! empty( $category ) ) {
				if ( isset( $category[0] ) ) {
					$category_element = sprintf(
						'<a href="%1$s" class="wp-block-wporg-latest-news__category">%2$s</a>',
						esc_url( get_category_link( $category[0]->term_id ) ),
						esc_html( $category[0]->name )
					);
				}
			}

			// Parse the cached date string directly: a stale post ID in the transient must not break rendering.
			$date         = new \DateTimeImmutable( $post['post_date'], wp_timezone() );
			$date_element = sprintf(
				'<time datetime="%1$s">%2$s</time>',
				esc_attr( $date->format( 'c' ) ),
				esc_html( wp_date( get_option( 'date_format' ), $date->getTimestamp(), null ) )
			);

			$list_items .= sprintf(
				'<li><div class="wp-block-wporg-latest-news__title">%1$s</div> <div class="wp-block-wporg-latest-news__details">%2$s %3$s %4$s</div></li>',
				$title_element,
				$category_element,
				! empty( $category_element ) ? '<span>·</span>' : '',
				$date_element,
			);
		}

		$popular_categories = '';

		if ( $attributes['showCategories'] ) {
			// Get the most popular categories.
			$cache_key               = 'wporg-latest-news-popular-categories';
			$popular_categories_list = get_transient( $cache_key );

			if ( ! $popular_categories_list ) {
				$popular_categories_list = get_categories(
					array(
						'orderby'    => 'count',
						'order'      => 'DESC',
						'number'     => 3,
						'hide_empty' => false,
					)
				);

				// Set Cache
				set_transient( $cache_key, $popular_categories_list, HOUR_IN_SECONDS );
			}

			if ( ! empty( $popular_categories_list ) ) {
				$popular_categories = '<div class="wp-block-wporg-latest-news__popular-categories">Popular categories: ';

				foreach ( $popular_categories_list as $category ) {
					$popular_categories .= sprintf(
						'<a href="%1$s">%2$s</a>, ',
						esc_url( get_category_link( $category->term_id ) ),
						esc_html( $category->name )
					);
				}

				$popular_categories  = rtrim( $popular_categories, ', ' );
				$popular_categories .= '.</div>';
			}
		}

		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $class_name ) );

		return sprintf(
			'<ul %1$s>%2$s</ul>%3$s',
			$wrapper_attributes,
			$list_items,
			$popular_categories
		);
	} finally {
		if ( $blog_switched ) {
			restore_current_blog();
		}
	}
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function block_init() {
	register_block_type(
		__DIR__ . '/build',
		array(
			'render_callback' => __NAMESPACE__ . '\render_block',
		)
	);

	register_block_style(
		'wporg/latest-news',
		array(
			'name'  => 'cards',
			'label' => __( 'Cards', 'wporg' ),
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\block_init' );
