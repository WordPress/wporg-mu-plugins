<?php

namespace WordPressdotorg\MU_Plugins\REST_API;

/**
 * Base_Locale_Banner_Controller
 */
class Base_Locale_Banner_Controller extends \WP_REST_Controller {
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
						'sanitize_callback' => function( $param ) {
							return (bool) $param;
						},
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
						'sanitize_callback' => function( $param ) {
							return (bool) $param;
						},
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
	public function check_slug( $param ) {
		_doing_it_wrong(
			'Base_Locale_Banner_Controller::check_slug',
			/* translators: %s: register_routes() */
			sprintf( "Method '%s' must be overridden.", __METHOD__ ),
			'4.7.0'
		);
		return false;
	}

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
}
