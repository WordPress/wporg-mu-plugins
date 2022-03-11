<?php

namespace WordPressdotorg\MU_Plugins\REST_API;

/**
 * Users_Controller
 */
class Users_Controller extends \WP_REST_Users_Controller {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->namespace = 'wporg/v1';
	}

	/**
	 * Registers the routes for users.
	 *
	 * At this time, this endpoint is exclusively read-only. Other routes from the parent class have been omitted.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the user.', 'wporg' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => array(
							'description'       => __( 'Scope under which the request is made; determines fields present in response.', 'wporg' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
							'validate_callback' => 'rest_validate_request_arg',
							// Omit the 'edit' context since this is read-only. This prevents including fields
							// containing non-public personal information.
							'enum'              => array( 'view', 'embed' ),
							'default'           => 'view',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Get the user, if the ID is valid.
	 *
	 * Modified from the parent method to remove the call to `is_user_member_of_blog()`.
	 *
	 * @param int $id Supplied ID.
	 *
	 * @return \WP_User|\WP_Error True if ID is valid, WP_Error otherwise.
	 */
	protected function get_user( $id ) {
		$error = new \WP_Error(
			'rest_user_invalid_id',
			__( 'Invalid user ID.', 'wporg' ),
			array( 'status' => 404 )
		);

		if ( (int) $id <= 0 ) {
			return $error;
		}

		$user = get_userdata( (int) $id );
		if ( empty( $user ) || ! $user->exists() ) {
			return $error;
		}

		return $user;
	}

	/**
	 * Checks if a given request has access to read a user.
	 *
	 * Modified from the parent method to remove capability checks that necessitate blog membership.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return true|\WP_Error True if the request has read access for the item, otherwise WP_Error object.
	 */
	public function get_item_permissions_check( $request ) {
		$user = $this->get_user( $request['id'] );
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		return true;
	}

	/**
	 * Prepares a single user output for response.
	 *
	 * @param \WP_User         $item    User object.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$response = parent::prepare_item_for_response( $item, $request );

		// The collection link is irrelevant since this endpoint only allows access to individual records.
		$response->remove_link( 'collection' );

		return $response;
	}
}
