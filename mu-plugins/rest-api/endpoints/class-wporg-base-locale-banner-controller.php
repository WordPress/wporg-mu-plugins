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
	 * Use specific translations for `wp_sprintf_l()`, to keep the strings colocated with those below.
	 *
	 * Use the Core strings as a fallback in the event of non-translation, if other strings are translated.
	 */
	protected function namespace_wp_sprintf_l_strings( $string, $args = array() ) {
		add_filter(
			'wp_sprintf_l',
			static function ( $core_strings ) {
				$custom_strings = array(
					/* translators: Used to join items in a list with more than 2 items. */
					'between'          => sprintf( __( '%1$s, %2$s', 'wporg' ), '', '' ),
					/* translators: Used to join last two items in a list with more than 2 times. */
					'between_last_two' => sprintf( __( '%1$s, and %2$s', 'wporg' ), '', '' ),
					/* translators: Used to join items in a list with only 2 items. */
					'between_only_two' => sprintf( __( '%1$s and %2$s', 'wporg' ), '', '' ),
				);

				if (
					// The conjunctions are not translated
					'%1$s, and %2$s' === __( '%1$s, and %2$s', 'wporg' ) &&
					// But the available in string is..
					'WordPress is also available in %s.' !== __( 'WordPress is also available in %s.', 'wporg' )
				) {
					// Then use the core strings as a fallback, hoping they're translated.
					return $core_strings;
				}

				return $custom_strings;
			}
		);

		// The wp_sprintf_l() strings, copied to WordPress.org so the translations are translated in conjunction with the above.
	}

	/**
	 * The strings used within the Language Suggest endpoint. Included here for translation purposes.
	 */
	private function _strings_for_glotpress() {
		// The Language suggest header strings:
		__( 'WordPress is also available in %s.', 'wporg' );
		__( 'Learn WordPress is also available in %s.', 'wporg' );
		__( 'WordPress support forums are also available in %s.', 'wporg' );
	}

}
