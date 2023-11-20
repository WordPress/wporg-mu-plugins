<?php

namespace WordPressdotorg\MU_Plugins\REST_API\Remembers;

use WP_Error;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;

defined( 'WPINC' ) || die();

/**
 *
 * This controller is used to provide user data for wp.org/remembers.
 *
 * @see WP_REST_Controller
 */
class Remembers_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'wporg/v1';
		$this->rest_base = 'remembers';

		$this->register_routes();
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_users' ),
				'permission_callback' => array( $this, 'get_users_permission_callback' ),
			)
		);
	}

	/**
	 * A Permission Check callback.
	 *
	 * @param \WP_REST_Request $request The Rest API Request.
	 * @return bool|\WP_Error True, WP_Error upon failure.
	 */
	function get_users_permission_callback( $request ) {
		return true;
	}

	 /**
	  * Get a list of memorialized users.
	  *
	  * @param WP_REST_Request $request
	  * @return WP_REST_Response
	  */
	public function get_users( $request ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			'
			SELECT user_id
			FROM bpmain_bp_xprofile_data
			WHERE field_id = "476" AND value = "Yes"'
		);

		$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL -- prepare called above.

		if ( ! $results ) {
			return new WP_Error( 'rest_error_fetching', 'Error fetching users.', array( 'status' => 500 ) );

		}

		return new WP_REST_Response( $results, 200 );
	}
}

new Remembers_Controller();
