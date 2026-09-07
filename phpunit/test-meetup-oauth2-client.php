<?php

use WordPressdotorg\MU_Plugins\Utilities\Meetup_OAuth2_Client;

/*
 * The client reads these at class-load time and nothing else in this repo defines them. The values only need
 * to be non-empty -- no request in this file leaves the process.
 */
foreach ( array(
	'MEETUP_OAUTH_CONSUMER_KEY'          => 'test-consumer-key',
	'MEETUP_OAUTH_CONSUMER_SECRET'       => 'test-consumer-secret',
	'MEETUP_OAUTH_CONSUMER_REDIRECT_URI' => 'https://meetup-client.test/wp-admin/',
	'MEETUP_USER_EMAIL'                  => 'meetup-client@example.org',
	'MEETUP_USER_PASSWORD'               => 'test-password',
) as $meetup_constant => $meetup_value ) {
	defined( $meetup_constant ) || define( $meetup_constant, $meetup_value );
}

/*
 * `utilities/` isn't loaded by the mu-plugin loader, since it isn't used globally. `WPMU_PLUGIN_DIR` rather
 * than a path relative to this file, because wp-env mounts the two directories in different places.
 */
require_once WPMU_PLUGIN_DIR . '/utilities/class-api-client.php';
require_once WPMU_PLUGIN_DIR . '/utilities/class-meetup-oauth2-client.php';

/**
 * Tests for where `Meetup_OAuth2_Client` takes an authorization code from.
 *
 * @group meetup
 */
class Test_Meetup_OAuth2_Client extends WP_UnitTestCase {
	/**
	 * The `code` on each token request the client tried to send.
	 *
	 * @var array
	 */
	protected $requested_codes = array();

	/**
	 * Intercept token requests instead of letting them reach Meetup.
	 */
	public function set_up() {
		parent::set_up();

		$this->requested_codes = array();

		add_filter( 'pre_http_request', array( $this, 'intercept_token_request' ), 10, 3 );
	}

	/**
	 * Clear everything the client reads or writes between tests.
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', array( $this, 'intercept_token_request' ), 10 );

		$_GET = array();

		delete_site_option( 'meetup_oauth_authorization' );
		delete_site_option( 'meetup_access_token' );

		parent::tear_down();
	}

	/**
	 * Record the code a token request carried, and hand back a usable token.
	 *
	 * @param false|array|WP_Error $preempt
	 * @param array                $args
	 * @param string               $url
	 *
	 * @return false|array|WP_Error
	 */
	public function intercept_token_request( $preempt, $args, $url ) {
		if ( false === strpos( $url, 'secure.meetup.com' ) ) {
			return $preempt;
		}

		$this->requested_codes[] = isset( $args['body']['code'] ) ? $args['body']['code'] : null;

		return array(
			'headers'  => array(),
			'cookies'  => array(),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'body'     => wp_json_encode( array(
				'access_token'  => 'ACCESS',
				'refresh_token' => 'REFRESH',
				'expires_in'    => HOUR_IN_SECONDS,
			) ),
		);
	}

	/**
	 * A client with nothing cached, ready for a test to drive `get_oauth_token()` itself.
	 *
	 * The constructor pre-caches a token, and raises a warning if it can't get one, so it's given a code to
	 * spend and then everything it left behind is cleared away.
	 *
	 * @return Meetup_OAuth2_Client
	 */
	protected function get_reset_client() {
		update_site_option( 'meetup_oauth_authorization', 'FROM_CONSTRUCTOR' );

		$client = new Meetup_OAuth2_Client();

		$oauth_token = new ReflectionProperty( Meetup_OAuth2_Client::class, 'oauth_token' );
		$oauth_token->setAccessible( true );
		$oauth_token->setValue( $client, array() );

		delete_site_option( 'meetup_access_token' );
		delete_site_option( 'meetup_oauth_authorization' );

		$this->requested_codes = array();

		return $client;
	}

	/**
	 * A code in the request is never what gets spent, whatever else is on the request.
	 *
	 * @covers Meetup_OAuth2_Client::get_oauth_token
	 */
	public function test_authorization_code_is_not_taken_from_the_request() {
		$client = $this->get_reset_client();

		$_GET = array(
			'code'  => 'FROM_REQUEST',
			'state' => 'meetup-oauth',
		);
		update_site_option( 'meetup_oauth_authorization', 'FROM_OPTION' );

		$client->get_oauth_token();

		$this->assertSame( array( 'FROM_OPTION' ), $this->requested_codes );
	}

	/**
	 * A caller that has vouched for a code can hand it over directly.
	 *
	 * @covers Meetup_OAuth2_Client::get_oauth_token
	 */
	public function test_authorization_code_is_taken_from_the_caller() {
		$client = $this->get_reset_client();

		$_GET = array(
			'code'  => 'FROM_REQUEST',
			'state' => 'meetup-oauth',
		);

		$client->get_oauth_token( 'FROM_CALLER' );

		$this->assertSame( array( 'FROM_CALLER' ), $this->requested_codes );
	}

	/**
	 * The caller's code wins over one left in the site option by an earlier attempt.
	 *
	 * @covers Meetup_OAuth2_Client::get_oauth_token
	 */
	public function test_caller_code_takes_precedence_over_the_stored_one() {
		$client = $this->get_reset_client();

		update_site_option( 'meetup_oauth_authorization', 'FROM_OPTION' );

		$client->get_oauth_token( 'FROM_CALLER' );

		$this->assertSame( array( 'FROM_CALLER' ), $this->requested_codes );
	}

	/**
	 * With no code from the caller, the site option is still the fallback it always was.
	 *
	 * @covers Meetup_OAuth2_Client::get_oauth_token
	 */
	public function test_stored_code_is_still_the_fallback() {
		$client = $this->get_reset_client();

		update_site_option( 'meetup_oauth_authorization', 'FROM_OPTION' );

		$this->assertSame( 'ACCESS', $client->get_oauth_token() );
		$this->assertSame( array( 'FROM_OPTION' ), $this->requested_codes );

		// The client clears the option once it has spent what was in it.
		$this->assertFalse( get_site_option( 'meetup_oauth_authorization', false ) );
	}
}
