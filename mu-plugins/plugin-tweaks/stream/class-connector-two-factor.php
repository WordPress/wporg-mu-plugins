<?php
/**
 * Connector for Two Factor
 */

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks\Stream;
use Two_Factor_Core;
use WP_Stream\Connector;
use WildWolf\WordPress\TwoFactorWebAuthn\WebAuthn_Credential_Store;

/**
 * Class - Connector_Two_Factor
 */
class Connector_Two_Factor extends Connector {
	/**
	 * Connector slug
	 *
	 * @var string
	 */
	public $name = 'two-factor';

	/**
	 * Actions registered for this connector
	 *
	 * @var array
	 */
	public $actions = array(
		// Triggers "callback_{name}" funcs.
		'update_user_meta', // Before user meta changes
		'updated_user_meta', // After user meta changes

		'two_factor_user_authenticated', // Authenticatd via 2FA
		'wp_login_failed', // Failed login
	);

	/**
	 * Actions that need to be run early.
	 *
	 * @var array
	 */
	public $actions_early = [
		// WebAuthN.. Must be run early, see ::register().
		'wp_ajax_webauthn_register'   => 5,
		'wp_ajax_webauthn_delete_key' => 5,
		// 'wp_ajax_webauthn_rename_key' => 5, // WordPress.org doesn't support this.
	];

	/**
	 * Tracked option keys
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Record the user_meta meta_value before updates.
	 *
	 * @var array
	 */
	public $user_meta = array();

	/**
	 * Check if plugin dependencies are satisfied and add an admin notice if not
	 *
	 * @return bool
	 */
	public function is_dependency_satisfied() {
		if ( class_exists( 'Two_Factor_Core' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return translated connector label
	 *
	 * @return string Translated connector label
	 */
	public function get_label() {
		return esc_html__( 'Two Factor', 'wporg' );
	}

	/**
	 * Return translated action labels
	 *
	 * @return array Action label translations
	 */
	public function get_action_labels() {
		return array(
			'enabled'       => 'Enabled',
			'disabled'      => 'Disabled',
			'recovered'     => 'Recovered',
			'updated'       => 'Updated',
			'authenticated' => 'Authenticated',
		);
	}

	/**
	 * Return translated context labels
	 *
	 * @return array Context label translations
	 */
	public function get_context_labels() {
		return array(
			'settings' => esc_html_x( 'Settings', 'two-factor', 'stream' ),
		);
	}

	/**
	 * Register the connector
	 */
	public function register() {
		parent::register();

		add_filter( 'wp_stream_log_data', [ $this, 'log_override' ] );

		// Immitate Streams actions, but with added priority.
		foreach ( $this->actions_early as $hook => $priority ) {
			add_action( $hook, [ $this, 'callback_' . $hook ], $priority, 99 );
		}
	}

	/**
	 * Modify or prevent logging of some actions.
	 *
	 * @param array $data Record data.
	 *
	 * @return array|bool
	 */
	public function log_override( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		// If a login was made but no cookies are being sent (ie. hit the 2FA interstitial), don't log it.
		if (
			'users' === $data['connector'] &&
			'sessions' === $data['context'] &&
			'login' === $data['action'] &&
			Two_Factor_Core::is_user_using_two_factor( $data['user_id'] ) &&
			'set_logged_in_cookie' === current_filter() &&
			has_filter( 'send_auth_cookies', '__return_false' )
		) {
			$data = false;
		}

		return $data;
	}

	/**
	 * Callback to watch for 2FA authenticated actions.
	 *
	 * @param \WP_User $user     Authenticated user.
	 * @param object   $provider The 2FA Provider used.
	 */
	public function callback_two_factor_user_authenticated( $user, $provider ) {
		$this->log(
			'Authenticated via %s',
			array(
				'provider' => $provider->get_key(),
			),
			$user->ID,
			'two-factor',
			'authenticated',
			$user->ID
		);
	}

	/**
	 * Callback to watch for failed logins with Two Factor errors.
	 *
	 * @param string   $user_login User login.
	 * @param \WP_Error $error WP_Error object.
	 */
	public function callback_wp_login_failed( $user_login, $error ) {
		if ( ! str_starts_with( $error->get_error_code(), 'two_factor_' ) ) {
			return;
		}

		$user = get_user_by( 'login', $user_login );
		if ( ! $user && is_email( $user_login ) ) {
			$user = get_user_by( 'email', $user_login );
		}

		$this->log(
			'%s Failed 2FA: %s %s',
			array(
				'display_name' => $user->display_name,
				'code'         => $error->get_error_code(),
				'error'        => $error->get_error_message(),
			),
			$user->ID,
			'two-factor',
			'failed',
			$user->ID
		);
	}

	/**
	 * Callback to watch for user_meta changes BEFORE it's made.
	 *
	 * @param int    $meta_id        Meta ID.
	 * @param int    $user_id        User ID.
	 * @param string $meta_key       Meta key.
	 * @param mixed  $new_meta_value The NEW meta value.
	 */
	public function callback_update_user_meta( $meta_id, $user_id, $meta_key, $new_meta_value ) {
		unset( $meta_id );

		switch( $meta_key ) {
			case '_two_factor_backup_codes':
			case '_two_factor_totp_key':
			case '_two_factor_enabled_providers':
				$this->user_meta[ $user_id ][ $meta_key ] = get_user_meta( $user_id, $meta_key, true );
				break;
		}

	}

	/**
	 * Callback to watch for user_meta changes AFTER it's made.
	 *
	 * @param int    $meta_id        Meta ID.
	 * @param int    $user_id        User ID.
	 * @param string $meta_key       Meta key.
	 * @param mixed  $new_meta_value The NEW meta value.
	 */
	public function callback_updated_user_meta( $meta_id, $user_id, $meta_key, $new_meta_value ) {
		unset( $meta_id );

		$old_meta_value = $this->user_meta[ $user_id ][ $meta_key ] ?? null;
		unset( $this->user_meta[ $user_id ][ $meta_key ] );

		switch( $meta_key ) {
			case '_two_factor_backup_codes':
				$this->log(
					'Updated backup codes',
					array(),
					$user_id,
					'two-factor',
					'updated'
				);
				break;
			case '_two_factor_totp_key':
				$this->log(
					'Set TOTP secret key',
					array(),
					$user_id,
					'two-factor',
					'updated'
				);
				break;
			case '_two_factor_enabled_providers':
				$old_providers = $old_meta_value ?? [];
				$new_providers = $new_meta_value ?? [];

				$enabled_providers  = array_diff( $new_providers, $old_providers );
				$disabled_providers = array_diff( $old_providers, $new_providers );

				foreach ( $enabled_providers as $provider ) {
					$this->log(
						'Enabled provider: %s',
						array(
							'provider' => $provider,
						),
						$user_id,
						'two-factor',
						'enabled'
					);
				}

				foreach ( $disabled_providers as $provider ) {
					$this->log(
						'Disabled provider: %s',
						array(
							'provider' => $provider,
						),
						$user_id,
						'two-factor',
						'disabled'
					);
				}
				break;
		}
	}

	/**
	 * Callback to watch for WebAuthN key registrations.
	 */
	function callback_wp_ajax_webauthn_register() {
		ob_start( function( $output ) {
			$success = json_decode( $output, true )['success'] ?? false;

			if ( $success ) {
				$this->log(
					'WebAuthN key registered: %s',
					array(
						'key-name' => wp_unslash( $_REQUEST['name'] ),
					),
					get_current_user_id(),
					'webauthn',
					'added'
				);
			}

			return $output;
		} );
		
	}

	/**
	 * Callback to watch for WebAuthN key deletions.
	 */
	function callback_wp_ajax_webauthn_delete_key() {
		// Fetch the handle now, so that it's available if it's removed.
		$user = get_user_by( 'ID', $_REQUEST['user_id'] ?? 0 );
		$keys = $user ? ( new WebAuthn_Credential_Store() )->get_user_keys( $user ) : [];

		ob_start( function( $output ) use( $keys ) {
			$success = json_decode( $output, true )['success'] ?? false;

			if ( $success ) {
				$handle = wp_unslash( $_REQUEST['handle'] ?? '' );

				$key  = wp_list_filter( $keys, [ 'credential_id' => $handle ] );
				$key  = reset( $key );
				$name = $key->name ?? '';

				$this->log(
					'WebAuthN key deleted: %s',
					array(
						'key-name' => $name,
					),
					get_current_user_id(),
					'two-factor',
					'removed'
				);
			}

			return $output;
		} );
	}

}
