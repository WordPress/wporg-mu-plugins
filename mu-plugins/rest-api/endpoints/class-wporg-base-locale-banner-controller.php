<?php

namespace WordPressdotorg\MU_Plugins\REST_API;

/**
 * Base_Locale_Banner_Controller
 */
abstract class Base_Locale_Banner_Controller extends \WP_REST_Controller {
	/**
	 * Register the endpoint routes used across both themes and plugins.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_response' ),
				'args' => array(
					'debug' => array(
						'type' => 'boolean',
					),
				),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<slug>[^/]+)/',
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_response_for_item' ),
				'args' => array(
					'debug' => array(
						'type' => 'boolean',
					),
					'slug' => array(
						'validate_callback' => array( $this, 'check_slug' ),
					),
				),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Check if the given slug is a valid item.
	 *
	 * Must be defined in the child class.
	 */
	abstract public function check_slug( $param );

	/**
	 * Send the response as plain text so it can be used as-is.
	 */
	public function send_plain_text( $result ) {
		header( 'Content-Type: text/text' );
		if ( $result ) {
			echo '<div>' . $result . '</div>'; // phpcs:ignore
		}

		return null;
	}

	/**
	 * The strings used within the Language Suggest endpoint.
	 *
	 * These are used by the wordpress.org/lang-suggest/ endpoint,
	 * and are included here for translation purposes.
	 */
	private function _strings_for_glotpress() {
		__( 'WordPress is also available in %s.', 'wporg' );
		__( 'Learn WordPress is also available in %s.', 'wporg' );
		__( 'WordPress support forums are also available in %s.', 'wporg' );
	}

}
