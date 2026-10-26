<?php
/**
 * Tests for the FreeScout client.
 *
 * @package WordPressdotorg\MU_Plugins
 */

declare( strict_types = 1 );

use WordPressdotorg\MU_Plugins\Utilities\FreeScout;

/*
 * `utilities/` isn't loaded by the mu-plugin loader, since it isn't used globally. `WPMU_PLUGIN_DIR` rather
 * than a path relative to this file, because wp-env mounts the two directories in different places.
 */
require_once WPMU_PLUGIN_DIR . '/utilities/class-freescout.php';

/**
 * Tests for `FreeScout`.
 *
 * Requests to freescout.wordpress.net are answered through `pre_http_request`, with responses shaped like the
 * API's (https://api-docs.freescout.net/), so no test reaches the live helpdesk.
 *
 * @group freescout
 */
class Test_FreeScout extends WP_UnitTestCase {
	/**
	 * Responses to hand back, keyed by method and path after `/api/`.
	 *
	 * @var array
	 */
	protected array $responses = array();

	/**
	 * Requests the client sent, as `array( method, path, decoded body )`.
	 *
	 * @var array
	 */
	protected array $requests = array();

	/**
	 * Answer FreeScout requests from `$responses`.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->requests  = array();
		$this->responses = array(
			'GET mailboxes?pageSize=100&page=1' => array(
				200,
				array(
					'_embedded' => array(
						'mailboxes' => array(
							array(
								'id'    => 12,
								'name'  => 'WordPress Community Team',
								'email' => 'support@wordcamp.org',
							),
							array(
								'id'    => 19,
								'name'  => 'WordPress Trademarks',
								'email' => 'trademarks@wordpressfoundation.org',
							),
						),
					),
					'page'      => array(
						'size'          => 100,
						'totalElements' => 2,
						'totalPages'    => 1,
						'number'        => 1,
					),
				),
			),
			'GET users/me'                      => array(
				200,
				array(
					'id'    => 33,
					'type'  => 'user',
					'email' => 'trademarks-bot@wordpress.org',
				),
			),
			'POST conversations'                => array(
				201,
				array(
					'id'        => 131241,
					'number'    => 2000123,
					'status'    => 'active',
					'subject'   => 'Trademark Infringement Notification example.org',
					'mailboxId' => 19,
				),
			),
			'PUT conversations/131241/tags'     => array( 204, null ),
		);

		add_filter( 'pre_http_request', array( $this, 'answer_request' ), 10, 3 );
	}

	/**
	 * Stop answering requests.
	 */
	public function tear_down(): void {
		remove_filter( 'pre_http_request', array( $this, 'answer_request' ), 10 );

		parent::tear_down();
	}

	/**
	 * Records a FreeScout request and returns its canned response.
	 *
	 * @param false|array|WP_Error $preempt Whether to preempt the request.
	 * @param array                $args    Request arguments.
	 * @param string               $url     Request URL.
	 * @return false|array|WP_Error
	 */
	public function answer_request( $preempt, $args, $url ) {
		if ( ! str_starts_with( $url, FreeScout::URL . '/api/' ) ) {
			return $preempt;
		}

		$key              = $args['method'] . ' ' . substr( $url, strlen( FreeScout::URL . '/api/' ) );
		$this->requests[] = array( $key, json_decode( (string) $args['body'], true ) );

		if ( ! isset( $this->responses[ $key ] ) ) {
			return new WP_Error( 'unexpected_request', $key );
		}

		list( $code, $body ) = $this->responses[ $key ];

		return array(
			'headers'  => array(),
			'body'     => null === $body ? '' : wp_json_encode( $body ),
			'response' => array(
				'code'    => $code,
				'message' => '',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Starts a conversation the way the trademark letters do.
	 *
	 * @return array|WP_Error
	 */
	protected function create_conversation(): array|WP_Error {
		return ( new FreeScout( 'test-key' ) )->create_conversation(
			array(
				'mailbox' => 'Trademarks@WordPressFoundation.org',
				'to'      => 'owner@example.org',
				'subject' => 'Trademark Infringement Notification example.org',
				'body'    => '<p>Hi there,</p>',
				'cc'      => array( 'event@example.org' ),
				'tags'    => array( 'trademark' ),
			)
		);
	}

	/**
	 * The mailbox is found by its address, the message goes out as the key's owner, and the caller gets a
	 * working link back.
	 */
	public function test_create_conversation_sends_from_the_mailbox_as_the_key_owner(): void {
		$conversation = $this->create_conversation();

		$this->assertSame(
			array(
				'type'      => 'email',
				'mailboxId' => 19,
				'subject'   => 'Trademark Infringement Notification example.org',
				'customer'  => array( 'email' => 'owner@example.org' ),
				'threads'   => array(
					array(
						'type' => 'message',
						'text' => '<p>Hi there,</p>',
						'user' => 33,
						'cc'   => array( 'event@example.org' ),
					),
				),
			),
			$this->requests[2][1]
		);
		$this->assertSame( array( 'PUT conversations/131241/tags', array( 'tags' => array( 'trademark' ) ) ), $this->requests[3] );
		$this->assertSame( 'https://freescout.wordpress.net/conversation/131241', $conversation['url'] );
		$this->assertSame( array( 'trademark' ), $conversation['tags'] );
	}

	/**
	 * A conversation that exists is returned even when tagging it fails, so the caller doesn't email the
	 * recipient again.
	 */
	public function test_create_conversation_returns_the_conversation_when_tagging_fails(): void {
		$this->responses['PUT conversations/131241/tags'] = array(
			400,
			array( 'message' => 'Tags module is not installed or not activated' ),
		);

		$conversation = $this->create_conversation();

		$this->assertIsArray( $conversation );
		$this->assertSame( 'https://freescout.wordpress.net/conversation/131241', $conversation['url'] );
		$this->assertSame( array(), $conversation['tags'] );
	}

	/**
	 * A refused request comes back as an error carrying the status and FreeScout's reasons.
	 */
	public function test_create_conversation_returns_freescouts_errors(): void {
		$this->responses['POST conversations'] = array(
			403,
			array(
				'message'   => 'Forbidden: Provided API key is not permitted to access this mailbox',
				'_embedded' => array( 'errors' => array() ),
			),
		);

		$conversation = $this->create_conversation();

		$this->assertWPError( $conversation );
		$this->assertSame( 'Forbidden: Provided API key is not permitted to access this mailbox', $conversation->get_error_message() );
		$this->assertSame( 403, $conversation->get_error_data()['status'] );
	}
}
