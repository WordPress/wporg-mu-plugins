<?php
/**
 * Tests for the `wporg/v1/users` REST endpoint.
 *
 * @package WordPressdotorg\MU_Plugins
 */

/**
 * Verify that a non-string `slug` is rejected cleanly instead of causing a fatal error.
 */
class Test_WPORG_REST_Users_Controller extends WP_UnitTestCase {

	/**
	 * A user the endpoint can look up by slug.
	 *
	 * @var WP_User
	 */
	protected static $user;

	/**
	 * Create the shared fixture user.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$user = $factory->user->create_and_get( array( 'user_nicename' => 'slug-test-user' ) );
	}

	/**
	 * Boot the REST server so the plugin registers its routes.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	/**
	 * Reset the REST server.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tear_down();
	}

	/**
	 * A valid slug still resolves to the user.
	 *
	 * @return void
	 */
	public function test_get_item_by_slug() {
		$response = rest_do_request( new WP_REST_Request( 'GET', '/wporg/v1/users/slug-test-user' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( self::$user->ID, $response->get_data()['id'] );
	}

	/**
	 * An array slug in the query string, which overrides the route slug, is rejected with a 400
	 * and does not fatal when core builds the Allow header from the permission callback.
	 *
	 * @return void
	 */
	public function test_array_slug_does_not_fatal() {
		$request = new WP_REST_Request( 'GET', '/wporg/v1/users/slug-test-user' );
		$request->set_query_params( array( 'slug' => array( 'slug-test-user' ) ) );

		$response = rest_do_request( $request );
		$this->assertSame( 400, $response->get_status() );

		// Core re-runs the permission callback here with the unsanitized array.
		$response = rest_send_allow_header( $response, rest_get_server(), $request );
		$this->assertSame( 400, $response->get_status() );
		$this->assertArrayNotHasKey( 'Allow', $response->get_headers() );
	}
}
