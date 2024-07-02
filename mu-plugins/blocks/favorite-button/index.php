<?php
/**
 * Block Name: Favorite Button
 * Description: A button to toggle favoriting on the current item.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Favorite_Button_Block;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\init' );
add_action( 'rest_api_init', __NAMESPACE__ . '\api_init' );

/**
 * Register the block.
 */
function init() {
	register_block_type( __DIR__ . '/build' );
}

/**
 * Get the block settings, set up the filters.
 */
function get_block_settings( $post_id ) {
	/**
	 * Get the settings for the favorite button, used in rendering and saving favorites.
	 *
	 * @param array $settings {
	 *     Array of settings for the favorite button.
	 *
	 *     The return value should use the following format.
	 *
	 *     @type callable $add_callback    Callback function to handle adding the current
	 *                                   item to a user's favorites. The function will
	 *                                   be passed the post ID and request object. It
	 *                                   should return a WP_Error, true, or the updated
	 *                                   count of favorite items.
	 *     @type callable $delete_callback Callback function to handle removing the current
	 *                                   item from a user's favorites. Same arguments and
	 *                                   return value as the `add_callback`.
	 *     @type int      $count           Number of times this item has been favorited.
	 *     @type bool     $is_favorite     Check if the current item is favorited.
	 * }
	 * @param int $post_id The current post ID.
	 */
	$settings = apply_filters( 'wporg_favorite_button_settings', array(), $post_id );
	if ( empty( $settings ) ) {
		return false;
	}

	$settings = wp_parse_args(
		$settings,
		array(
			'add_callback' => '__return_false',
			'delete_callback' => '__return_false',
			'count' => 0,
			'is_favorite' => false,
		)
	);

	return $settings;
}

/**
 * Initialize the API endpoints.
 */
function api_init() {
	$namespace = 'wporg/v1';
	$args = array(
		'id' => array(
			'validate_callback' => function( $param, $request, $key ) {
				return is_numeric( $param );
			},
			'required' => true,
		),
	);
	register_rest_route(
		$namespace,
		'/favorite',
		array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => __NAMESPACE__ . '\add_favorite',
			'args' => $args,
			'permission_callback' => 'is_user_logged_in',
		)
	);
	register_rest_route(
		$namespace,
		'/favorite',
		array(
			'methods' => \WP_REST_Server::DELETABLE,
			'callback' => __NAMESPACE__ . '\delete_favorite',
			'args' => $args,
			'permission_callback' => 'is_user_logged_in',
		)
	);
}

/**
 * Set the favorite status for a given item.
 */
function add_favorite( $request ) {
	$id = intval( $request['id'] );
	$settings = get_block_settings( $id );
	$result = call_user_func( $settings['add_callback'], $id, $request );

	if ( is_wp_error( $result ) ) {
		return $result;
	} else if ( false !== $result ) {
		if ( is_numeric( $result ) ) {
			return new \WP_REST_Response( $result, 200 );
		} else {
			return new \WP_REST_Response( [ 'success' => true ] );
		}
	}

	return new \WP_Error(
		'favorite-failed',
		// Users should never see this error, so we can leave it untranslated.
		'Unable to favorite this item.',
		array( 'status' => 500 )
	);
}

/**
 * Remove the favorite status for a given item.
 */
function delete_favorite( $request ) {
	$id = intval( $request['id'] );
	$settings = get_block_settings( $id );
	$result = call_user_func( $settings['delete_callback'], $id, $request );

	if ( is_wp_error( $result ) ) {
		return $result;
	} else if ( false !== $result ) {
		if ( is_numeric( $result ) ) {
			return new \WP_REST_Response( $result, 200 );
		} else {
			return new \WP_REST_Response( [ 'success' => true ] );
		}
	}

	return new \WP_Error(
		'unfavorite-failed',
		// Users should never see this error, so we can leave it untranslated.
		'Unable to remove this item from favorites.',
		array( 'status' => 500 )
	);
}
