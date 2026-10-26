<?php
/**
 * A client for the WordPress.org helpdesk at freescout.wordpress.net.
 *
 * @package WordPressdotorg\MU_Plugins\Utilities
 */

declare( strict_types = 1 );

namespace WordPressdotorg\MU_Plugins\Utilities;

use WP_Error;

defined( 'WPINC' ) || die();

/**
 * Class FreeScout
 *
 * Sends email as FreeScout conversations, so replies come back to the mailbox and the conversation can be linked
 * from wherever it started.
 *
 *     $freescout    = new FreeScout( WPORG_FREESCOUT_API_KEY );
 *     $conversation = $freescout->create_conversation(
 *         array(
 *             'mailbox' => 'trademarks@wordpressfoundation.org',
 *             'to'      => 'owner@example.org',
 *             'subject' => 'Trademark Infringement Notification example.org',
 *             'body'    => '<p>Hi there,</p>',
 *             'tags'    => array( 'trademark' ),
 *         )
 *     );
 *
 *     if ( ! is_wp_error( $conversation ) ) {
 *         update_post_meta( $post_id, '_freescout_url', $conversation['url'] );
 *     }
 *
 * Conversations are returned as the API sends them, plus `url`, the link to the conversation, and `tags`, its tag
 * names.
 *
 * A personal API key (FreeScout: API & Webhooks » My API keys) sends as the agent who owns it, and can be limited
 * to some mailboxes. The main key sends as whichever agent `user` names.
 *
 * ⚠️ This class is used in multiple locations in the WordPress/WordCamp ecosystem. If you make changes to this
 * file, make sure they are tested everywhere.
 *
 * @see https://api-docs.freescout.net/
 */
final class FreeScout {
	/**
	 * The helpdesk's address.
	 *
	 * @var string
	 */
	const URL = 'https://freescout.wordpress.net';

	/**
	 * How long mailbox and agent IDs are cached, in seconds.
	 *
	 * @var int
	 */
	private const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * The API key requests are made with.
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Constructor.
	 *
	 * @param string $api_key A FreeScout API key.
	 */
	public function __construct( string $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Starts a conversation by emailing someone from a mailbox.
	 *
	 * Tags are added once the conversation exists. If that fails, the conversation is still returned, without them
	 * in `tags`, because sending it again would email the recipient twice.
	 *
	 * @param array $args {
	 *     The conversation.
	 *
	 *     @type int|string $mailbox Required. The mailbox's ID or email address.
	 *     @type string     $to      Required. The recipient's email address.
	 *     @type string     $subject Required. The subject.
	 *     @type string     $body    Required. The message, as HTML.
	 *     @type string[]   $cc      Optional. Addresses to copy.
	 *     @type string[]   $tags    Optional. Tag names.
	 *     @type string     $status  Optional. `active` (default), `pending` or `closed`.
	 *     @type int|string $user    Optional. The sending agent's ID or email address, which only the main key can
	 *                               choose. Default: the key's owner.
	 * }
	 * @return array|WP_Error The conversation.
	 */
	public function create_conversation( array $args ): array|WP_Error {
		$args = wp_parse_args(
			$args,
			array(
				'mailbox' => '',
				'to'      => '',
				'subject' => '',
				'body'    => '',
				'cc'      => array(),
				'tags'    => array(),
				'status'  => '',
				'user'    => 'me',
			)
		);

		foreach ( array( 'mailbox', 'to', 'subject', 'body' ) as $required ) {
			if ( empty( $args[ $required ] ) ) {
				return new WP_Error( 'freescout_invalid_argument', sprintf( 'Missing `%s`.', $required ) );
			}
		}

		$mailbox_id = $this->get_mailbox_id( $args['mailbox'] );
		if ( is_wp_error( $mailbox_id ) ) {
			return $mailbox_id;
		}

		$thread = $this->build_thread( $args );
		if ( is_wp_error( $thread ) ) {
			return $thread;
		}

		$request = array(
			'type'      => 'email',
			'mailboxId' => $mailbox_id,
			'subject'   => $args['subject'],
			'customer'  => array( 'email' => $args['to'] ),
			'threads'   => array( $thread ),
		);

		if ( $args['status'] ) {
			$request['status'] = $args['status'];
		}

		$conversation = $this->request( 'POST', 'conversations', $request );
		if ( is_wp_error( $conversation ) ) {
			return $conversation;
		}

		$tags = array();
		if ( $args['tags'] ) {
			$tagged = $this->request( 'PUT', 'conversations/' . (int) $conversation['id'] . '/tags', array( 'tags' => array_values( $args['tags'] ) ) );

			if ( ! is_wp_error( $tagged ) ) {
				$tags = array_values( $args['tags'] );
			}
		}

		return $this->prepare_conversation( $conversation, $tags );
	}

	/**
	 * Replies to a conversation, emailing its customer.
	 *
	 * @param int    $conversation_id The conversation's ID.
	 * @param string $body            The message, as HTML.
	 * @param array  $args {
	 *     Optional.
	 *
	 *     @type string[]   $cc     Addresses to copy.
	 *     @type string     $status The conversation's status after the reply. Default: FreeScout's, `pending`.
	 *     @type int|string $user   The sending agent's ID or email address, which only the main key can choose.
	 *                              Default: the key's owner.
	 * }
	 * @return true|WP_Error
	 */
	public function reply( int $conversation_id, string $body, array $args = array() ): true|WP_Error {
		$args = wp_parse_args(
			$args,
			array(
				'cc'     => array(),
				'status' => '',
				'user'   => 'me',
			)
		);

		$args['body'] = $body;

		$thread = $this->build_thread( $args );
		if ( is_wp_error( $thread ) ) {
			return $thread;
		}

		if ( $args['status'] ) {
			$thread['status'] = $args['status'];
		}

		$result = $this->request( 'POST', 'conversations/' . $conversation_id . '/threads', $thread );

		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Gets a conversation, with its threads and tags.
	 *
	 * @param int $conversation_id The conversation's ID.
	 * @return array|WP_Error The conversation.
	 */
	public function get_conversation( int $conversation_id ): array|WP_Error {
		$conversation = $this->request( 'GET', 'conversations/' . $conversation_id . '?embed=threads,tags' );
		if ( is_wp_error( $conversation ) ) {
			return $conversation;
		}

		return $this->prepare_conversation( $conversation, array_column( $conversation['_embedded']['tags'] ?? array(), 'name' ) );
	}

	/**
	 * Adds the conversation's link and tag names to what the API returned.
	 *
	 * @param array    $conversation The API's conversation.
	 * @param string[] $tags         Tag names.
	 * @return array
	 */
	private function prepare_conversation( array $conversation, array $tags ): array {
		$conversation['url']  = self::URL . '/conversation/' . (int) ( $conversation['id'] ?? 0 );
		$conversation['tags'] = $tags;

		return $conversation;
	}

	/**
	 * Builds the agent's message for a new conversation or a reply.
	 *
	 * @param array $args `body`, `cc` and `user`, as the public methods take them.
	 * @return array|WP_Error
	 */
	private function build_thread( array $args ): array|WP_Error {
		$user_id = $this->get_user_id( $args['user'] );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$thread = array(
			'type' => 'message',
			'text' => $args['body'],
			'user' => $user_id,
		);

		if ( $args['cc'] ) {
			$thread['cc'] = array_values( (array) $args['cc'] );
		}

		return $thread;
	}

	/**
	 * Gets a mailbox's ID from its email address.
	 *
	 * @param int|string $mailbox The mailbox's ID or email address.
	 * @return int|WP_Error
	 */
	private function get_mailbox_id( int|string $mailbox ): int|WP_Error {
		if ( is_int( $mailbox ) ) {
			return $mailbox;
		}

		$email     = strtolower( $mailbox );
		$mailboxes = get_site_transient( $this->cache_key( 'mailboxes' ) );

		// A mailbox added since the list was cached isn't in it yet.
		if ( ! is_array( $mailboxes ) || ! isset( $mailboxes[ $email ] ) ) {
			$mailboxes = $this->fetch_mailboxes();
			if ( is_wp_error( $mailboxes ) ) {
				return $mailboxes;
			}

			set_site_transient( $this->cache_key( 'mailboxes' ), $mailboxes, self::CACHE_TTL );
		}

		if ( ! isset( $mailboxes[ $email ] ) ) {
			return new WP_Error( 'freescout_invalid_argument', sprintf( 'No mailbox `%s` this key can use.', $mailbox ) );
		}

		return $mailboxes[ $email ];
	}

	/**
	 * Lists the mailboxes the key can use.
	 *
	 * @return array|WP_Error Mailbox IDs, keyed by lowercase email address.
	 */
	private function fetch_mailboxes(): array|WP_Error {
		$mailboxes = array();
		$page      = 1;

		do {
			$response = $this->request( 'GET', 'mailboxes?pageSize=100&page=' . $page );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			foreach ( $response['_embedded']['mailboxes'] ?? array() as $mailbox ) {
				$mailboxes[ strtolower( (string) $mailbox['email'] ) ] = (int) $mailbox['id'];
			}
		} while ( $page++ < (int) ( $response['page']['totalPages'] ?? 1 ) );

		return $mailboxes;
	}

	/**
	 * Gets an agent's ID.
	 *
	 * @param int|string $user The agent's ID or email address, or `me` for the key's owner.
	 * @return int|WP_Error
	 */
	private function get_user_id( int|string $user ): int|WP_Error {
		if ( is_int( $user ) ) {
			return $user;
		}

		$cache_key = $this->cache_key( 'user:' . strtolower( $user ) );
		$user_id   = get_site_transient( $cache_key );

		if ( is_int( $user_id ) ) {
			return $user_id;
		}

		if ( 'me' === $user ) {
			$response = $this->request( 'GET', 'users/me' );
			$user_id  = is_wp_error( $response ) ? $response : (int) ( $response['id'] ?? 0 );
		} else {
			$response = $this->request( 'GET', 'users?' . http_build_query( array( 'email' => $user ) ) );
			$user_id  = is_wp_error( $response ) ? $response : (int) ( $response['_embedded']['users'][0]['id'] ?? 0 );
		}

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		if ( ! $user_id ) {
			return new WP_Error( 'freescout_invalid_argument', sprintf( 'No agent `%s`.', $user ) );
		}

		set_site_transient( $cache_key, $user_id, self::CACHE_TTL );

		return $user_id;
	}

	/**
	 * Builds a cache key that is unique to the API key, since keys can see different mailboxes.
	 *
	 * @param string $name What is cached.
	 * @return string
	 */
	private function cache_key( string $name ): string {
		return 'wporg_freescout_' . md5( $this->api_key . '|' . $name );
	}

	/**
	 * Makes a request to the API.
	 *
	 * @param string     $method HTTP method.
	 * @param string     $path   Path after `/api/`, with any query string.
	 * @param array|null $body   Optional. The JSON body.
	 * @return array|WP_Error The decoded response, empty when there's none.
	 */
	private function request( string $method, string $path, ?array $body = null ): array|WP_Error {
		$response = wp_remote_request(
			self::URL . '/api/' . $path,
			array(
				'method'  => $method,
				'timeout' => 15,
				'headers' => array(
					'X-FreeScout-API-Key' => $this->api_key,
					'Accept'              => 'application/json',
					'Content-Type'        => 'application/json',
				),
				'body'    => null === $body ? null : wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'freescout_error', $response->get_error_message() );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status >= 200 && $status < 300 ) {
			return is_array( $data ) ? $data : array();
		}

		return new WP_Error(
			'freescout_error',
			(string) ( $data['message'] ?? 'FreeScout returned HTTP ' . $status . '.' ),
			array(
				'status' => $status,
				'errors' => $data['_embedded']['errors'] ?? array(),
			)
		);
	}
}
