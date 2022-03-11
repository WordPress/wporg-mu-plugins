<?php

namespace WordPressdotorg\MU_Plugins\REST_API;

/**
 * Actions and filters.
 */
add_action( 'rest_api_init', __NAMESPACE__ . '\initialize_rest_endpoints' );

/**
 * Turn on API endpoints.
 *
 * @return void
 */
function initialize_rest_endpoints() {
	require_once __DIR__ . '/endpoints/class-wporg-rest-users-controller.php';

	$users_controller = new Users_Controller();
	$users_controller->register_routes();
}
