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

// The key only needs to be non-empty: no request in this file leaves the process.
defined( 'FREESCOUT_API_KEY' ) || define( 'FREESCOUT_API_KEY', 'test-key' );
defined( 'FREESCOUT_TRADEMARKS_MAILBOXID' ) || define( 'FREESCOUT_TRADEMARKS_MAILBOXID', 19 );

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
	 * Decoded bodies of the requests the client sent, keyed by method and path after `/api/`.
	 *
	 * @var array
	 */
	protected array $requests = array();

	/**
	 * Answer FreeScout requests from `$responses`, as a logged-in agent.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->requests  = array();
		$this->responses = array(
			'GET users?email=agent%40example.org' => array(
				200,
				array(
					'_embedded' => array(
						'users' => array(
							array(
								'id'    => 33,
								'type'  => 'user',
								'email' => 'agent@example.org',
							),
						),
					),
				),
			),
			'POST conversations'                  => array(
				201,
				array(
					'id'        => 131241,
					'number'    => 2000123,
					'status'    => 'active',
					'subject'   => 'Trademark Infringement Notification example.org',
					'mailboxId' => 19,
				),
			),
			'GET conversations/131241?embed=tags' => array(
				200,
				array(
					'id'        => 131241,
					'_embedded' => array(
						'tags' => array(
							array(
								'id'   => 57,
								'name' => 'new-sponsor',
							),
						),
					),
				),
			),
			'PUT conversations/131241/tags'       => array( 204, null ),
			'PUT conversations/131241'            => array( 204, null ),
		);

		add_filter( 'pre_http_request', array( $this, 'answer_request' ), 10, 3 );

		wp_set_current_user( self::factory()->user->create( array( 'user_email' => 'agent@example.org' ) ) );
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
	public function answer_request( false|array|WP_Error $preempt, array $args, string $url ): false|array|WP_Error {
		if ( ! str_starts_with( $url, FreeScout::URL . '/api/' ) ) {
			return $preempt;
		}

		$key                    = $args['method'] . ' ' . substr( $url, strlen( FreeScout::URL . '/api/' ) );
		$this->requests[ $key ] = json_decode( (string) $args['body'], true );

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
	 * Sends a trademark letter.
	 *
	 * @return array|WP_Error
	 */
	protected function send(): array|WP_Error {
		return FreeScout::send(
			array(
				'mailbox' => 'trademarks',
				'to'      => 'owner@example.org',
				'subject' => 'Trademark Infringement Notification example.org',
				'body'    => '<p>Hi there,</p>',
				'cc'      => array( 'event@example.org' ),
				'tags'    => array( 'trademark' ),
			)
		);
	}

	/**
	 * The mailbox ID comes from its constant, the message goes out as the current user's agent, and the caller gets
	 * a working link back.
	 */
	public function test_send_from_the_mailbox_as_the_current_user(): void {
		$conversation = $this->send();

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
			$this->requests['POST conversations']
		);
		$this->assertSame( array( 'tags' => array( 'trademark' ) ), $this->requests['PUT conversations/131241/tags'] );
		$this->assertSame( 'https://freescout.wordpress.net/conversation/131241', $conversation['url'] );
		$this->assertSame( array( 'trademark' ), $conversation['tags'] );
	}

	/**
	 * A conversation that exists is returned even when tagging it fails, so the caller doesn't email the
	 * recipient again.
	 */
	public function test_send_returns_the_conversation_when_tagging_fails(): void {
		$this->responses['PUT conversations/131241/tags'] = array(
			400,
			array( 'message' => 'Tags module is not installed or not activated' ),
		);

		$conversation = $this->send();

		$this->assertIsArray( $conversation );
		$this->assertSame( 'https://freescout.wordpress.net/conversation/131241', $conversation['url'] );
		$this->assertSame( array(), $conversation['tags'] );
	}

	/**
	 * A refused request comes back as an error carrying the status and FreeScout's reasons.
	 */
	public function test_send_returns_freescouts_errors(): void {
		$this->responses['POST conversations'] = array(
			403,
			array(
				'message'   => 'Forbidden: Provided API key is not permitted to access this mailbox',
				'_embedded' => array( 'errors' => array() ),
			),
		);

		$conversation = $this->send();

		$this->assertWPError( $conversation );
		$this->assertSame( 'Forbidden: Provided API key is not permitted to access this mailbox', $conversation->get_error_message() );
		$this->assertSame( 403, $conversation->get_error_data()['status'] );
	}

	/**
	 * Nothing is sent when FreeScout answers with a different agent than the one asked for.
	 */
	public function test_send_refuses_an_agent_with_another_email(): void {
		$this->responses['GET users?email=agent%40example.org'][1]['_embedded']['users'][0]['email'] = 'admin@example.org';

		$conversation = $this->send();

		$this->assertWPError( $conversation );
		$this->assertArrayNotHasKey( 'POST conversations', $this->requests );
	}

	/**
	 * Closing and unassigning names the agent making the change, which FreeScout requires, and new tags join the
	 * ones the conversation already has.
	 */
	public function test_update_closes_unassigns_and_adds_tags(): void {
		$result = FreeScout::update(
			131241,
			array(
				'status'    => 'closed',
				'assign_to' => 0,
				'tags'      => array( 'approved' ),
			)
		);

		$this->assertTrue( $result );
		$this->assertSame(
			array(
				'byUser'   => 33,
				'status'   => 'closed',
				'assignTo' => -1,
			),
			$this->requests['PUT conversations/131241']
		);
		$this->assertSame( array( 'tags' => array( 'new-sponsor', 'approved' ) ), $this->requests['PUT conversations/131241/tags'] );
	}
}
