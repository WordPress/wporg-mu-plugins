<?php
/**
 * Tests for browser-bound Meetup OAuth authorization.
 *
 * @package WordPressdotorg\MU_Plugins
 */

declare( strict_types = 1 );

use WordPressdotorg\MU_Plugins\Utilities\Meetup_OAuth2_Client;

/**
 * Exercise the real callback and token exchange with mocked HTTP responses.
 */
class Test_Meetup_OAuth_State extends WP_UnitTestCase {
	/**
	 * Original query parameters.
	 *
	 * @var array
	 */
	private array $original_get = array();

	/**
	 * Configure an administrator and isolated token storage.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve the test runner request for teardown.
		$_GET               = array();
		$user_id            = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$user               = wp_set_current_user( $user_id );
		$user->add_cap( 'manage_network_options' );
		set_current_screen( 'dashboard' );

		foreach ( array(
			'MEETUP_OAUTH_CONSUMER_KEY'          => 'test-client',
			'MEETUP_OAUTH_CONSUMER_SECRET'       => 'test-secret',
			'MEETUP_OAUTH_CONSUMER_REDIRECT_URI' => admin_url( '/' ),
			'MEETUP_USER_EMAIL'                  => 'test@example.org',
		) as $name => $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		require_once WPMU_PLUGIN_DIR . '/utilities/class-api-client.php';
		require_once WPMU_PLUGIN_DIR . '/utilities/class-meetup-oauth2-client.php';
		delete_site_option( Meetup_OAuth2_Client::SITE_OPTION_KEY_OAUTH );
		delete_site_option( Meetup_OAuth2_Client::SITE_OPTION_KEY_AUTHORIZATION );
	}

	/**
	 * Restore request and screen state.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$_GET = $this->original_get;
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * Capture token requests and expected expiration notices.
	 *
	 * @return array Token requests and warning messages.
	 */
	private function run_client(): array {
		$requests = array();
		$warnings = array();
		$http     = static function ( bool $response, array $args, string $url ) use ( &$requests ): array {
			$requests[] = array( $url, $args['body'] );
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'access_token'  => 'test-access-token',
						'refresh_token' => 'test-refresh-token',
						'expires_in'    => 3600,
					)
				),
			);
		};
		$warning  = static function ( int $level, string $message ) use ( &$warnings ): bool {
			if ( E_USER_WARNING !== $level ) {
				return false;
			}
			$warnings[] = $message;
			return true;
		};

		add_filter( 'pre_http_request', $http, 10, 3 );
		set_error_handler( $warning, E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Capture the expected token-expiration warning.
		ob_start();
		try {
			new Meetup_OAuth2_Client();
		} finally {
			ob_end_clean();
			restore_error_handler();
			remove_filter( 'pre_http_request', $http, 10 );
		}
		return array( $requests, $warnings );
	}

	/**
	 * The generated authorization link carries a nonce accepted on return.
	 *
	 * @return void
	 */
	public function test_generated_state_allows_authorized_callback(): void {
		list( $requests, $warnings ) = $this->run_client();
		$this->assertSame( array(), $requests );
		preg_match( '/state=([a-z0-9]+)/', $warnings[0], $matches );
		$this->assertNotEmpty( $matches[1] );
		$_GET = array(
			'code'  => 'returned-code',
			'state' => $matches[1],
		);

		list( $requests, $warnings ) = $this->run_client();
		$this->assertCount( 1, $requests );
		$this->assertSame( 'returned-code', $requests[0][1]['code'] );
		$this->assertSame( array(), $warnings );
		$this->assertSame( 'test-access-token', get_site_option( Meetup_OAuth2_Client::SITE_OPTION_KEY_OAUTH )['access_token'] );
	}

	/**
	 * Empty, fixed, expired, and another user's state cannot initiate token exchange.
	 *
	 * @return void
	 */
	public function test_unbound_states_are_rejected(): void {
		$original_user = get_current_user_id();
		wp_set_current_user( self::factory()->user->create() );
		$other_state = wp_create_nonce( 'meetup-oauth' );
		wp_set_current_user( $original_user );
		$expired_state = substr( wp_hash( ( wp_nonce_tick() - 2 ) . '|meetup-oauth|' . $original_user . '|' . wp_get_session_token(), 'nonce' ), -12, 10 );
		foreach ( array( '', 'meetup-oauth', $other_state, $expired_state ) as $state ) {
			$_GET = array(
				'code'  => 'untrusted-code',
				'state' => $state,
			);

			list( $requests ) = $this->run_client();
			$this->assertSame( array(), $requests );
			$this->assertFalse( get_site_option( Meetup_OAuth2_Client::SITE_OPTION_KEY_OAUTH ) );
		}
	}

	/**
	 * A valid nonce still requires the administrator capability and admin context.
	 *
	 * @return void
	 */
	public function test_state_does_not_replace_authorization(): void {
		$_GET = array(
			'code'  => 'returned-code',
			'state' => wp_create_nonce( 'meetup-oauth' ),
		);
		set_current_screen( 'front' );
		list( $requests ) = $this->run_client();
		$this->assertSame( array(), $requests );
		set_current_screen( 'dashboard' );
		wp_get_current_user()->remove_cap( 'manage_network_options' );
		list( $requests ) = $this->run_client();
		$this->assertSame( array(), $requests );
	}

	/**
	 * Codes explicitly imported through WP-CLI still bypass the browser callback.
	 *
	 * @return void
	 */
	public function test_stored_authorization_code_still_works(): void {
		set_current_screen( 'front' );
		update_site_option( Meetup_OAuth2_Client::SITE_OPTION_KEY_AUTHORIZATION, 'imported-code' );
		list( $requests ) = $this->run_client();
		$this->assertCount( 1, $requests );
		$this->assertSame( 'imported-code', $requests[0][1]['code'] );
		$this->assertFalse( get_site_option( Meetup_OAuth2_Client::SITE_OPTION_KEY_AUTHORIZATION ) );
	}
}
