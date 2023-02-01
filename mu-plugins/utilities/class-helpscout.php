<?php
namespace WordPressdotorg\MU_Plugins\Utilities;

/**
 * Simple HelpScout client.
 *
 * @package WordPressdotorg\MU_Plugins\Utilities
 */
class HelpScout {
	const API_BASE        = 'https://api.helpscout.net';
	const DEFAULT_VERSION = 2;

	/**
	 * The HTTP timeout for the HelpScout API.
	 *
	 * @var int
	 */
	public $timeout = 15;

	protected $app_id     = '';
	protected $app_secret = '';

	/**
	 * Fetch an instance of the HelpScout API.
	 */
	public function instance( $app_id = false, $secret = false ) {
		static $instances = [];

		if ( ! $app_id && ! $secret ) {
			$app_id = 'wordpress';
		}

		if ( $app_id && ! $secret ) {
			if ( 'wordpress' === $app_id && defined( 'HELPSCOUT_APP_ID' ) ) {
				$app_id = HELPSCOUT_APP_ID;
				$secret = HELPSCOUT_APP_SECRET;
			} elseif ( 'foundation' === $app_id && defined( 'HELPSCOUT_FOUNDATION_APP_ID' ) ) {
				$app_id = HELPSCOUT_FOUNDATION_APP_ID;
				$secret = HELPSCOUT_FOUNDATION_APP_SECRET;
			} else {
				$app_id = false;
			}
		}

		if ( ! $app_id || ! $secret ) {
			return false;
		}

		return $instances[ $app_id ] ?? ( $instances[ $app_id ] = new self( $app_id, $secret ) );
	}

	protected function __construct( $app_id, $secret ) {
		$this->app_id     = $app_id;
		$this->app_secret = $secret;
	}

	/**
	 * Retrieve the mailbox ID for an inbox.
	 *
	 * @param string $mailbox The mailbox. Accepts 'plugins', 'data', 'jobs', 'openverse', 'photos', 'themes', etc.
	 * @return int The numeric mailbox ID.
	 */
	public static function get_mailbox_id( $mailbox ) {
		$define = 'HELPSCOUT_' . strtoupper( $mailbox ) . '_MAILBOXID';
		if ( ! defined( $define ) ) {
			return false;
		}

		return constant( $define );
	}

	/**
	 * Call a HelpScout API endpoint.
	 *
	 * @param string $url    The API endpoint to request.
	 * @param array  $args   Any parameters to pass to the API.
	 * @param string $method The HTTP method for the request. 'GET' or 'POST'. Default 'GET'.
	 */
	public static function api( $url, $args = null, $method = 'GET' ) {
		// Support static calls for back-compat.
		if ( ! isset( $this ) ) {
			return self::instance()->api( $url, $args, $method ) ?? false;
		}

		// Prepend API URL host-less URLs.
		if ( ! str_starts_with( $url, self::API_BASE ) ) {
			// Prepend API version when not specified.
			if ( ! preg_match( '!^/v\d{1}!', $url ) ) {
				$url = '/v' . self::DEFAULT_VERSION . '/' . ltrim( $url, '/' );
			}

			$url = self::API_BASE . '/' . ltrim( $url, '/' );
		}

		// $args passed as GET paramters.
		if ( 'GET' === $method && $args ) {
			$url = add_query_arg( $args, $url );
		}

		$request = wp_remote_request(
			$url,
			array(
				'method'  => $method,
				'headers' => [
					'Accept'        => 'application/json',
					'Authorization' => $this->get_auth_string(),
				],
				'timeout' => $this->timeout,
				'body'    => ( 'POST' === $method && $args ) ? $args : null,
			)
		);

		return json_decode( wp_remote_retrieve_body( $request ) );
	}

	/**
	 * Fetch an Authorization token for accessing HelpScout Resources.
	 */
	protected function get_auth_string() {
		$cache_key = __CLASS__ . $this->app_id . 'get_auth_token';
		$token     = get_site_transient( $cache_key );
		if ( $token && is_array( $token ) && $token['exp'] > time() ) {
			return 'BEARER ' . $token['token'];
		}

		$request = wp_remote_post(
			self::API_BASE . '/v2/oauth2/token',
			array(
				'timeout' => $this->timeout,
				'body'    => array(
					'grant_type'    => 'client_credentials',
					'client_id'     => $this->app_id,
					'client_secret' => $this->app_secret,
				)
			)
		);

		$response = is_wp_error( $request ) ? false : json_decode( wp_remote_retrieve_body( $request ) );

		if ( ! $response || empty( $response->access_token ) ) {
			return false;
		}

		// Cache the token for 1 minute less than what it's valid for.
		$token  = $response->access_token;
		$expiry = $response->expires_in - MINUTE_IN_SECONDS;

		set_site_transient(
			$cache_key,
			[
				'exp' => time() + $expiry,
				'token' => $token
			],
			$expiry
		);

		return 'BEARER ' . $token;
	}

}
