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
 *     $conversation = FreeScout::send(
 *         array(
 *             'mailbox' => 'trademarks',
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
 * Conversations and threads are returned as the API sends them; conversations also get `url`, the link to the
 * conversation, and `tags`, its tag names.
 *
 * Agents are named by ID or email address, and messages, notes and changes are made by the current user's agent,
 * which FreeScout links to their WordPress.org account. Pass `user` where that isn't an agent: in cron jobs, or when
 * a visitor's action sends the email, like a form submission. The client doesn't check who may act: callers check
 * capabilities first.
 *
 * A `freescout_no_response` error means FreeScout may have acted anyway, so don't simply send again.
 *
 * Needs the `FREESCOUT_API_KEY` constant, and a `FREESCOUT_<MAILBOX>_MAILBOXID` constant for each mailbox named, like
 * `FREESCOUT_PLUGINS_MAILBOXID`.
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
	 * How long agent IDs are cached, in seconds.
	 *
	 * @var int
	 */
	private const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * Starts a conversation by emailing someone from a mailbox.
	 *
	 * Tags are added once the conversation exists. If that fails, the conversation is still returned, without them
	 * in `tags`, because sending it again would email the recipient twice.
	 *
	 * @param array $args {
	 *     The conversation.
	 *
	 *     @type int|string   $mailbox   Required. The mailbox's ID, or its name in its constant, like `plugins`.
	 *     @type string|array $to        Required. The recipient's email address, or an array of `email`,
	 *                                   `first_name` and `last_name`.
	 *     @type string       $subject   Required. The subject.
	 *     @type string       $body      Required. The message, as HTML.
	 *     @type string[]     $cc        Optional. Addresses to copy.
	 *     @type string[]     $tags      Optional. Tag names.
	 *     @type string       $status    Optional. `active` (default), `pending` or `closed`.
	 *     @type int|string   $assign_to Optional. The agent to assign it to.
	 *     @type int|string   $user      Optional. The sending agent. Default: the current user.
	 * }
	 * @return array|WP_Error The conversation.
	 */
	public static function send( array $args ): array|WP_Error {
		$args = wp_parse_args(
			$args,
			array(
				'mailbox'   => '',
				'to'        => '',
				'subject'   => '',
				'body'      => '',
				'cc'        => array(),
				'tags'      => array(),
				'status'    => '',
				'assign_to' => '',
				'user'      => '',
			)
		);

		foreach ( array( 'mailbox', 'to', 'subject', 'body' ) as $required ) {
			if ( empty( $args[ $required ] ) ) {
				return new WP_Error( 'freescout_invalid_argument', sprintf( 'Missing `%s`.', $required ) );
			}
		}

		$to = is_array( $args['to'] ) ? $args['to'] : array( 'email' => $args['to'] );
		if ( empty( $to['email'] ) || ! is_string( $to['email'] ) ) {
			return new WP_Error( 'freescout_invalid_argument', '`to` needs an `email`.' );
		}

		$mailbox_id = self::get_mailbox_id( $args['mailbox'] );
		if ( is_wp_error( $mailbox_id ) ) {
			return $mailbox_id;
		}

		$thread = self::build_thread( 'message', $args );
		if ( is_wp_error( $thread ) ) {
			return $thread;
		}

		$request = array(
			'type'      => 'email',
			'mailboxId' => $mailbox_id,
			'subject'   => $args['subject'],
			'customer'  => array_filter(
				array(
					'email'     => $to['email'],
					'firstName' => $to['first_name'] ?? '',
					'lastName'  => $to['last_name'] ?? '',
				)
			),
			'threads'   => array( $thread ),
		);

		if ( $args['status'] ) {
			$request['status'] = $args['status'];
		}

		if ( $args['assign_to'] ) {
			$assignee = self::get_user_id( $args['assign_to'] );
			if ( is_wp_error( $assignee ) ) {
				return $assignee;
			}

			$request['assignTo'] = $assignee;
		}

		$conversation = self::request( 'POST', 'conversations', $request );
		if ( is_wp_error( $conversation ) ) {
			return $conversation;
		}

		if ( empty( $conversation['id'] ) ) {
			return new WP_Error( 'freescout_no_response', 'FreeScout returned no conversation ID.' );
		}

		$tags = self::clean_tags( (array) $args['tags'] );

		// A new conversation has no tags yet: Workflows tag API-created conversations later.
		if ( $tags && is_wp_error( self::request( 'PUT', 'conversations/' . (int) $conversation['id'] . '/tags', array( 'tags' => $tags ) ) ) ) {
			$tags = array();
		}

		return self::prepare_conversation( $conversation, $tags );
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
	 *     @type int|string $user   The sending agent. Default: the current user.
	 * }
	 * @return array|WP_Error The new thread.
	 */
	public static function reply( int $conversation_id, string $body, array $args = array() ): array|WP_Error {
		return self::add_thread( $conversation_id, 'message', $body, $args );
	}

	/**
	 * Adds a note to a conversation, which only agents see.
	 *
	 * @param int    $conversation_id The conversation's ID.
	 * @param string $body            The note, as HTML.
	 * @param array  $args {
	 *     Optional.
	 *
	 *     @type string     $status The conversation's status after the note.
	 *     @type int|string $user   The agent writing it. Default: the current user.
	 * }
	 * @return array|WP_Error The new thread.
	 */
	public static function note( int $conversation_id, string $body, array $args = array() ): array|WP_Error {
		return self::add_thread( $conversation_id, 'note', $body, $args );
	}

	/**
	 * Changes a conversation's status, assignee or tags, without a message.
	 *
	 * Tags are added to the conversation's own. FreeScout can only replace a conversation's tags, so a tag added
	 * between reading them and writing them back is lost.
	 *
	 * @param int   $conversation_id The conversation's ID.
	 * @param array $changes {
	 *     What to change.
	 *
	 *     @type string     $status    `active`, `pending`, `closed` or `spam`.
	 *     @type int|string $assign_to The agent to assign it to, or `0` for nobody.
	 *     @type string[]   $tags      Tag names to add.
	 *     @type int|string $user      The agent making the change. Default: the current user.
	 * }
	 * @return true|WP_Error
	 */
	public static function update( int $conversation_id, array $changes ): true|WP_Error {
		$request = array();

		if ( ! empty( $changes['status'] ) ) {
			$request['status'] = $changes['status'];
		}

		if ( isset( $changes['assign_to'] ) ) {
			// FreeScout unassigns with -1.
			$assignee = in_array( $changes['assign_to'], array( 0, '0', '' ), true ) ? -1 : self::get_user_id( $changes['assign_to'] );
			if ( is_wp_error( $assignee ) ) {
				return $assignee;
			}

			$request['assignTo'] = $assignee;
		}

		if ( $request ) {
			$user_id = self::get_user_id( $changes['user'] ?? '' );
			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			$result = self::request( 'PUT', 'conversations/' . $conversation_id, array( 'byUser' => $user_id ) + $request );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$tags = self::clean_tags( (array) ( $changes['tags'] ?? array() ) );
		if ( ! $tags ) {
			return true;
		}

		$conversation = self::request( 'GET', 'conversations/' . $conversation_id . '?embed=tags' );
		if ( is_wp_error( $conversation ) ) {
			return $conversation;
		}

		$all = array_column( $conversation['_embedded']['tags'] ?? array(), 'name' );
		foreach ( $tags as $tag ) {
			if ( ! in_array( strtolower( $tag ), array_map( 'strtolower', $all ), true ) ) {
				$all[] = $tag;
			}
		}

		$result = self::request( 'PUT', 'conversations/' . $conversation_id . '/tags', array( 'tags' => $all ) );

		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Gets a conversation, with its threads and tags.
	 *
	 * @param int $conversation_id The conversation's ID.
	 * @return array|WP_Error The conversation.
	 */
	public static function get( int $conversation_id ): array|WP_Error {
		$conversation = self::request( 'GET', 'conversations/' . $conversation_id . '?embed=threads,tags' );
		if ( is_wp_error( $conversation ) ) {
			return $conversation;
		}

		return self::prepare_conversation( $conversation, array_column( $conversation['_embedded']['tags'] ?? array(), 'name' ) );
	}

	/**
	 * Adds the conversation's link and tag names to what the API returned.
	 *
	 * @param array    $conversation The API's conversation.
	 * @param string[] $tags         Tag names.
	 * @return array
	 */
	private static function prepare_conversation( array $conversation, array $tags ): array {
		$conversation['url']  = self::URL . '/conversation/' . (int) ( $conversation['id'] ?? 0 );
		$conversation['tags'] = array_values( $tags );

		return $conversation;
	}

	/**
	 * Turns tag names into a list of strings.
	 *
	 * @param array $tags Tag names.
	 * @return string[]
	 */
	private static function clean_tags( array $tags ): array {
		return array_values( array_filter( array_map( 'strval', array_filter( $tags, 'is_scalar' ) ) ) );
	}

	/**
	 * Adds a reply or note to a conversation.
	 *
	 * @param int    $conversation_id The conversation's ID.
	 * @param string $type            `message` or `note`.
	 * @param string $body            The text, as HTML.
	 * @param array  $args            `cc`, `status` and `user`, as the public methods take them.
	 * @return array|WP_Error The new thread.
	 */
	private static function add_thread( int $conversation_id, string $type, string $body, array $args ): array|WP_Error {
		$args = wp_parse_args(
			$args,
			array(
				'cc'     => array(),
				'status' => '',
				'user'   => '',
			)
		);

		$args['body'] = $body;

		$thread = self::build_thread( $type, $args );
		if ( is_wp_error( $thread ) ) {
			return $thread;
		}

		if ( $args['status'] ) {
			$thread['status'] = $args['status'];
		}

		return self::request( 'POST', 'conversations/' . $conversation_id . '/threads', $thread );
	}

	/**
	 * Builds an agent's message or note.
	 *
	 * @param string $type `message` or `note`.
	 * @param array  $args `body`, `cc` and `user`, as the public methods take them.
	 * @return array|WP_Error
	 */
	private static function build_thread( string $type, array $args ): array|WP_Error {
		$user_id = self::get_user_id( $args['user'] );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$thread = array(
			'type' => $type,
			'text' => $args['body'],
			'user' => $user_id,
		);

		if ( $args['cc'] ?? array() ) {
			$thread['cc'] = array_values( (array) $args['cc'] );
		}

		return $thread;
	}

	/**
	 * Gets a mailbox's ID from its `FREESCOUT_<MAILBOX>_MAILBOXID` constant.
	 *
	 * @param mixed $mailbox The mailbox's ID, or its name in the constant, like `plugins`.
	 * @return int|WP_Error
	 */
	private static function get_mailbox_id( mixed $mailbox ): int|WP_Error {
		if ( is_numeric( $mailbox ) ) {
			return (int) $mailbox;
		}

		$constant = is_string( $mailbox ) ? 'FREESCOUT_' . strtoupper( $mailbox ) . '_MAILBOXID' : '';
		if ( ! $constant || ! defined( $constant ) ) {
			return new WP_Error( 'freescout_not_configured', sprintf( 'No mailbox constant for `%s`.', is_string( $mailbox ) ? $mailbox : gettype( $mailbox ) ) );
		}

		return (int) constant( $constant );
	}

	/**
	 * Gets an agent's ID.
	 *
	 * @param mixed $user The agent's ID or email address. Empty for the current user.
	 * @return int|WP_Error
	 */
	private static function get_user_id( mixed $user ): int|WP_Error {
		if ( is_numeric( $user ) ) {
			return (int) $user;
		}

		if ( ! $user ) {
			$user = wp_get_current_user()->user_email;
		}

		if ( ! is_string( $user ) || ! $user ) {
			return new WP_Error( 'freescout_invalid_argument', '`user` must be an agent ID or email address, or someone must be logged in.' );
		}

		$cache_key = 'wporg_freescout_user_' . md5( strtolower( $user ) );
		$user_id   = get_site_transient( $cache_key );

		if ( is_int( $user_id ) ) {
			return $user_id;
		}

		$response = self::request( 'GET', 'users?' . http_build_query( array( 'email' => $user ) ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Never fall back to another agent if the API ever stops filtering by email.
		$agent   = $response['_embedded']['users'][0] ?? array();
		$user_id = 0 === strcasecmp( (string) ( $agent['email'] ?? '' ), $user ) ? (int) $agent['id'] : 0;
		if ( ! $user_id ) {
			return new WP_Error( 'freescout_invalid_argument', sprintf( 'No agent `%s`.', $user ) );
		}

		set_site_transient( $cache_key, $user_id, self::CACHE_TTL );

		return $user_id;
	}

	/**
	 * Makes a request to the API.
	 *
	 * @param string     $method HTTP method.
	 * @param string     $path   Path after `/api/`, with any query string.
	 * @param array|null $body   Optional. The JSON body.
	 * @return array|WP_Error The decoded response, empty when there's none.
	 */
	private static function request( string $method, string $path, ?array $body = null ): array|WP_Error {
		if ( ! defined( 'FREESCOUT_API_KEY' ) ) {
			return new WP_Error( 'freescout_not_configured', 'FREESCOUT_API_KEY is not defined.' );
		}

		$json = null === $body ? null : wp_json_encode( $body );
		if ( false === $json ) {
			return new WP_Error( 'freescout_invalid_argument', 'The request could not be encoded as JSON; check for invalid UTF-8.' );
		}

		$response = wp_remote_request(
			self::URL . '/api/' . $path,
			array(
				'method'  => $method,
				'timeout' => 15,
				'headers' => array(
					'X-FreeScout-API-Key' => FREESCOUT_API_KEY,
					'Accept'              => 'application/json',
					'Content-Type'        => 'application/json',
				),
				'body'    => $json,
			)
		);

		// FreeScout may have received the request and acted on it.
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'freescout_no_response', $response->get_error_message() );
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
