<?php
namespace WordPressdotorg\MU_Plugins\Utilities;

use DateTimeInterface, DateTimeImmutable, DateTimeZone, DateInterval;
use WP_Error;

defined( 'WPINC' ) || die();

/**
 * Class Meetup_Client
 *
 * ⚠️ This class and its dependency classes are used in multiple locations in the WordPress/WordCamp ecosystem. If
 * you make changes to this file, make sure they are tested everywhere.
 */
class Meetup_Client extends API_Client {

	/**
	 * @var int The Venue ID for online events.
	 */
	const ONLINE_VENUE_ID = 26906060;

	/**
	 * @var string The URL for the API endpoints.
	 */
	protected $api_url = 'https://api.meetup.com/gql-ext';

	/**
	 * @var string The GraphQL field that must be present for pagination to work.
	 */
	public $pagination = 'pageInfo { hasNextPage endCursor }';

	/**
	 * @var Meetup_OAuth2_Client|null
	 */
	protected $oauth_client = null;

	/**
	 * @var bool If true, the client will fetch fewer results, for faster debugging.
	 */
	protected $debug = false;

	/**
	 * Meetup_Client constructor.
	 *
	 * @param array $settings {
	 *     Optional. Settings for the client.
	 *
	 *     @type bool $debug If true, the client will fetch fewer results, for faster debugging.
	 * }
	 */
	public function __construct( array $settings = array() ) {
		// Define the OAuth client first, such that it can be used in the parent constructor callbacks.
		$this->oauth_client = new Meetup_OAuth2_Client;

		parent::__construct( array(
			/*
			 * Response codes that should break the request loop.
			 *
			 * See https://www.meetup.com/meetup_api/docs/#errors.
			 *
			 * `200` (ok) is not in the list, because it needs to be handled conditionally.
			 *  See API_Client::tenacious_remote_request.
			 *
			 * `400` (bad request) is not in the list, even though it seems like it _should_ indicate an unrecoverable
			 * error. In practice we've observed that it's common for a seemingly valid request to be rejected with
			 * a `400` response, but then get a `200` response if that exact same request is retried.
			 */
			'breaking_response_codes' => array(
				// TODO: NOTE: These headers are not returned from the GraphQL API, every request is 200 even if throttled.
				401, // Unauthorized (invalid key).
				429, // Too many requests (rate-limited).
				404, // Unable to find group.
				503, // Timeout between API cache & GraphQL Server.
			),
			// NOTE: GraphQL does not expose the Quota Headers.
			'throttle_callback'       => array( __CLASS__, 'throttle' ),
		) );

		$settings = wp_parse_args(
			$settings,
			array(
				'debug' => false,
			)
		);

		$this->debug = $settings['debug'];

		if ( $this->debug ) {
			self::cli_message( 'Meetup Client debug is on. Results will be truncated.' );
		}

		add_action( 'api_client_tenacious_remote_request_attempt', array( $this, 'maybe_reset_oauth_token' ) );
		add_action( 'api_client_handle_error_response', array( $this, 'maybe_reset_oauth_token' ) );

		if ( ! empty( $this->oauth_client->error->get_error_messages() ) ) {
			$this->error = $this->merge_errors( $this->error, $this->oauth_client->error );
		}
	}

	/**
	 * Attempt to fix authorization errors before they permanently fail.
	 *
	 * Hooked to `api_client_tenacious_remote_request_attempt` so that a request that has failed due to an invalid
	 * oauth token can be retried after resetting the token.
	 *
	 * @param array $response
	 *
	 * @return void
	 */
	public function maybe_reset_oauth_token( $response ) {
		static $resetting = false;
		// Avoid recursive calls.
		if ( $resetting ) {
			return;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$parsed_error = $this->parse_error( $body );

		if (
			( 400 === $code && $parsed_error->get_error_message( 'invalid_grant' ) )
			|| ( 401 === $code && $parsed_error->get_error_message( 'auth_fail' ) )
		) {
			$resetting = true;

			$this->oauth_client->reset_oauth_token();

			if ( ! empty( $this->oauth_client->error->get_error_messages() ) ) {
				$this->error = $this->merge_errors( $this->error, $this->oauth_client->error );
			}

			// Reset the request headers, so that they include the new oauth token.
			$this->current_request_args = $this->get_request_args();

			$resetting = false;
		}
	}

	/**
	 * Send a paginated request to the Meetup API and return the aggregated response.
	 *
	 * This automatically paginates requests and will repeat requests to ensure all results are retrieved.
	 * For pagination to work, $this->pagination must be present within the string, and a 'cursor' variable defined.
	 *
	 * @param string $query     The API endpoint URL to send the request to.
	 * @param array  $variables The Query variables used in the query.
	 *
	 * @return array|WP_Error The results of the request.
	 */
	public function send_paginated_request( $query, $variables = null ) {
		$data = array();

		$has_next_page        = false;
		$is_paginated_request = ! empty( $variables ) &&
			array_key_exists( 'cursor', $variables ) &&
			false !== stripos( $query, $this->pagination );

		do {
			$request_args = $this->get_request_args( $query, $variables );
			$response     = $this->tenacious_remote_post( $this->api_url, $request_args );

			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$this->handle_error_response( $response, $this->api_url, $request_args );
				break;
			}

			$new_data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $new_data['error'] ) ) {
				$this->handle_error_response( $response, $this->api_url, $request_args );
				break;
			}

			if ( ! is_array( $new_data ) || ! isset( $new_data['data'] ) ) {
				$this->error->add(
					'unexpected_response_data',
					'The API response did not provide the expected data format.',
					$response
				);
				break;
			}

			// Merge the data, overwriting scalar values (they should be the same), and merging arrays.
			$data = ! $data ? $new_data : $this->array_merge_recursive_numeric_arrays(
				$data,
				$new_data
			);

			// Pagination - Find the values inside the 'pageInfo' key.
			if ( $is_paginated_request ) {
				$has_next_page = false;
				$end_cursor    = null;

				// Flatten the data array to a set of [ $key => $value ] pairs for LEAF nodes,
				// $value will never be an array, and $key will never be set to 'pageInfo' where
				// the targetted values are living.
				array_walk_recursive(
					$new_data,
					function ( $value, $key ) use ( &$has_next_page, &$end_cursor ) {
						// NOTE: This will be truthful and present on the final page causing paged
						// requests to always make an additional request to a final empty page.
						if ( 'hasNextPage' === $key ) {
							$has_next_page = $value;
						} elseif ( 'endCursor' === $key ) {
							$end_cursor = $value;
						}
					}
				);

				// Do not iterate if the cursor was what we just made the request with.
				// This should never happen, but protects against an infinite loop otherwise.
				if ( ! $end_cursor || $end_cursor === $variables['cursor'] ) {
					$has_next_page = false;
					$end_cursor    = false;
				}

				$variables['cursor'] = $end_cursor;
			}

			if ( $has_next_page && $this->debug ) {
				if ( 'cli' === php_sapi_name() ) {
					echo "\nDebug mode: Skipping future paginated requests";
				}

				break;
			}
		} while ( $has_next_page );

		$errors = implode( '. ', $this->error->get_error_messages() );
		if ( ! empty( $errors ) ) {
			trigger_error( "Request error(s): $errors", E_USER_WARNING );

			return $this->error;
		}

		return $data['data'];
	}

	/**
	 * Similar to array_merge_recursive(), but only merges numeric arrays with one another, overwriting associative elements.
	 *
	 * Based on https://www.php.net/manual/en/function.array-merge-recursive.php#92195
	 */
	private function array_merge_recursive_numeric_arrays( array &$array1, array &$array2 ) {
		$merged = $array1;

		foreach ( $array2 as $key => &$value ) {
			// Merge numeric arrays.
			if ( is_array( $value ) && wp_is_numeric_array( $value ) && isset( $merged[ $key ] ) ) {
				$merged[ $key ] = array_merge( $merged[ $key ], $value );
			} elseif ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = $this->array_merge_recursive_numeric_arrays( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * Generate headers to use in a request.
	 *
	 * @return array
	 */
	protected function get_request_args( $query, $variables = null ) {
		$oauth_token = $this->oauth_client->get_oauth_token();

		if ( ! empty( $this->oauth_client->error->get_error_messages() ) ) {
			$this->error = $this->merge_errors( $this->error, $this->oauth_client->error );
		}

		// Previous GraphQL took this as a json string encoded into json below, but now we need an array.
		if ( is_string( $variables ) && json_decode( $variables ) ) {
			$variables = json_decode( $variables, true );
		}

		return array(
			'timeout' => 60,
			'headers' => array(
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
				'Authorization' => "Bearer $oauth_token",
			),
			'body' => wp_json_encode( compact( 'query', 'variables' ) ),
		);
	}

	/**
	 * Check the rate limit status in an API response and delay further execution if necessary.
	 *
	 * @param array $response
	 *
	 * @return void
	 */
	protected static function throttle( $response ) {
		$headers = wp_remote_retrieve_headers( $response );

		/*
		 * NOTE: This is not in use, as GraphQL API doesn't return rate limit headers,
		 *       but does throttle requests & fail if you exceed it.
		 */

		if ( ! isset( $headers['x-ratelimit-remaining'], $headers['x-ratelimit-reset'] ) ) {
			return;
		}

		$remaining = absint( $headers['x-ratelimit-remaining'] );
		$period    = absint( $headers['x-ratelimit-reset'] );

		/**
		 * Don't throttle if we have sufficient requests remaining.
		 *
		 * We don't let this number get to 0, though, because there are scenarios where multiple processes are using
		 * the API at the same time, and there's no way for them to be aware of each other.
		 */
		if ( $remaining > 3 ) {
			return;
		}

		// Pause for longer than we need to, just to be safe.
		if ( $period < 2 ) {
			$period = 2;
		}

		self::cli_message( "Pausing for $period seconds to avoid rate-limiting." );

		sleep( $period );
	}

	/**
	 * Convert a timestamp to an ISO8601 date string.
	 *
	 * @param mixed $input A timestamp, DateTime object, or a string that can be parsed as a date.
	 * @return string The date in ISO8601 format (Y-m-d\TH:i:sP).
	 */
	public function date_as_iso( $input ) {
		$input = $this->datetime_to_time( $input ) ?: $input;

		return gmdate( 'Y-m-d\TH:i:sP', $input );
	}

	/**
	 * Convert any timestamp such as a ISO8601-ish DateTime returned from the API to a epoch timestamp.
	 *
	 * Handles timestamps in two main formats:
	 *  - 2021-11-20T17:00+05:30
	 *  - 2021-11-20T06:30-05:00[US/Eastern]
	 *  - Seconds since epoch
	 *  - Milliseconds since epoch
	 *  - DateTime objects
	 *
	 * Some extra compat formats are included, just incase Meetup.com decides to return in other similar formats,
	 * or with different timezone formats, etc.
	 *
	 * @param mixed $datetime A DateTime string returned by the API, a DateTime instance, or a numeric epoch with or without milliseconds.
	 * @return int The UTC epoch timestamp.
	 */
	public function datetime_to_time( $datetime ) {
		if ( is_numeric( $datetime ) && $datetime > 4102444800 /* 2100-01-01 */ ) {
			$datetime /= 1000;
			return (int) $datetime;
		} elseif ( is_numeric( $datetime ) ) {
			return (int) $datetime;
		}

		// Handle DateTime objects.
		if ( $datetime instanceof DateTimeInterface ) {
			return $datetime->getTimestamp();
		}

		$datetime_formats = array(
			'Y-m-d\TH:iP',   // '2021-11-20T17:00+05:30'.
			'Y-m-d\TH:i:sP', // '2021-11-20T17:00:00+05:30'.
			// DateTime::createFromFormat() doesn't handle the final `]` character in the following timezone format.
			'Y-m-d\TH:i\[e', // '2021-11-20T06:30[US/Eastern]'.
			'c',             // ISO8601, just incase the above don't cover it.
			'Y-m-d\TH:i:s',  // timezoneless '2021-11-20T17:00:00'.
			'Y-m-d\TH:i',    // timezoneless '2021-11-20T17:00'.
		);

		// See above, just keep one timezone if the timezone format is `P\[e\]`. Simpler matching, assume the timezones are the same.
		$datetime = preg_replace( '/([-+][0-9:]+)[[].+[]]$/', '$1', $datetime );

		// See above..
		$datetime = rtrim( $datetime, ']' );

		// Just being hopeful.
		$time = strtotime( $datetime );
		if ( $time ) {
			return $time;
		}

		// Try each of the timezone formats.
		foreach ( $datetime_formats as $format ) {
			$time = DateTimeImmutable::createFromFormat( $format, $datetime );
			if ( $time ) {
				break;
			}
		}

		if ( ! $time ) {
			return false;
		}

		return (int) $time->format( 'U' );
	}

	/**
	 * Extract error information from an API response and add it to our error handler.
	 *
	 * Make sure you don't include the full $response in the error as data, as that could expose sensitive information
	 * from the request payload.
	 *
	 * @param array|WP_Error $response     The response or error generated from the request.
	 * @param string         $request_url  Optional.
	 * @param array          $request_args Optional.
	 *
	 * @return void
	 */
	public function handle_error_response( $response, $request_url = '', $request_args = array() ) {
		if ( parent::handle_error_response( $response, $request_url, $request_args ) ) {
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$data          = json_decode( wp_remote_retrieve_body( $response ), true );

		$parsed_error = $this->parse_error( $data, $response_code );

		if ( ! empty( $parsed_error->get_error_messages() ) ) {
			$this->error = self::merge_errors( $this->error, $parsed_error );
		} else {
			$this->error->add(
				'unknown_error',
				'There was an unknown error.'
			);
		}
	}

	/**
	 * Attempt to extract codes and messages from a suspected error response.
	 *
	 * @param null|array $data          The data in the response body, parsed as an array. May be null for HTTP errors such as 404's.
	 * @param int        $response_code Optional. The HTTP status code from the response.
	 *
	 * @return WP_Error
	 */
	protected function parse_error( $data, $response_code = 0 ) {
		$error = new WP_Error();

		if ( isset( $data['errors'] ) ) {
			foreach ( $data['errors'] as $details ) {
				$error->add(
					$details['extensions']['code'],
					$details['message'],
					$details['locations'] ?? '' // TODO This isn't being passed through to the final error?
				);
			}
		} elseif ( isset( $data['error'], $data['error_description'] ) ) {
			$error->add(
				$data['error'],
				$data['error_description']
			);
		} elseif ( isset( $data['code'], $data['details'] ) ) {
			$error->add(
				$data['code'],
				$data['details']
			);
		} elseif ( $response_code ) {
			$error->add(
				'http_response_code',
				sprintf( 'HTTP Status: %d', absint( $response_code ) )
			);
		}

		return $error;
	}

	/**
	 * Retrieve data about groups in the Chapter program.
	 *
	 * @param array $args Optional. 'fields' and 'filters' may be defined.
	 *
	 * @return array|WP_Error
	 */
	public function get_groups( array $args = array() ) {
		$filters = array();
		$fields  = $this->get_default_fields( 'group' );

		if ( ! empty( $args['fields'] ) && is_array( $args['fields'] ) ) {
			$fields = array_merge( $fields, $args['fields'] );
		}

		/*
		 *  See https://www.meetup.com/api/schema/#GroupAnalyticsFilter for valid filters.
		 */
		if ( isset( $args['pro_join_date_max'] ) ) {
			$filters['proJoinDateMax'] = 'proJoinDateMax: "' . $this->date_as_iso( $args['pro_join_date_max'] ) . '"';
		}
		if ( isset( $args['last_event_min'] ) ) {
			$filters['lastEventMin'] = 'lastEventMin: "' . $this->date_as_iso( $args['last_event_min'] ) . '"';
		}

		if ( isset( $args['filters'] ) ) {
			foreach ( $args['filters'] as $key => $value ) {
				$filters[ $key ] = "{$key}: \"{$value}\"";
			}
		}

		$variables = array(
			'urlname' => 'wordpress',
			'perPage' => 500,
			'cursor'  => null,
		);

		$query = '
		query ($urlname: ID, $perPage: Int!, $cursor: String ) {
			proNetwork( urlname: $urlname ) {
				groupsSearch( input: { first: $perPage, after: $cursor, filter: { ' . implode( ', ', $filters ) . '} } ) {
					totalCount
					' . $this->pagination . '
					edges {
						node {
							' . implode( ' ', $fields ) . '
						}
					}
				}
			}
		}';

		$result = $this->send_paginated_request( $query, $variables );

		if ( is_wp_error( $result ) || ! array_key_exists( 'groupsSearch', $result['proNetwork'] ) ) {
			return $result;
		}

		$groups = array_column(
			$result['proNetwork']['groupsSearch']['edges'],
			'node'
		);

		$groups = $this->apply_backcompat_fields( 'groups', $groups );

		return $groups;
	}

	/**
	 * Retrieve data about events associated with a set of groups.
	 *
	 * Because of the way that the Meetup API v3 endpoints are structured, we unfortunately have to make one request
	 * (or more, if there's pagination) for each group that we want events for. When there are hundreds of groups, and
	 * we are throttling to make sure we don't get rate-limited, this process can literally take several minutes.
	 *
	 * So, when building the array for the $group_slugs parameter, it's important to filter out groups that you know
	 * will not provide relevant results. For example, if you want all events during a date range in the past, you can
	 * filter out groups that didn't join the chapter program until after your date range.
	 *
	 * Note that when using date/time related parameters in the $args array, unlike other endpoints and fields in the
	 * Meetup API which use an epoch timestamp in milliseconds, this one requires a date/time string formatted in
	 * ISO 8601, without the timezone part. Because consistency is overrated.
	 *
	 * @param array $group_slugs The URL slugs of each group to retrieve events for. Also known as `urlname`.
	 * @param array $args        Optional.  'fields' and 'filters' may be defined.
	 *
	 * @return array|WP_Error
	 */
	public function get_events( array $group_slugs, array $args = array() ) {
		$events = array();

		// See get_network_events(), which should be preferred for most cases.
		// This is kept for back-compat.

		if ( $this->debug ) {
			$chunked     = array_chunk( $group_slugs, 10 );
			$group_slugs = $chunked[0];
		}

		foreach ( $group_slugs as $group_slug ) {
			$response = $this->get_group_events( $group_slug, $args );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$events = array_merge( $events, $response );
		}

		return $events;
	}

	/**
	 * Retrieve Event Details
	 *
	 * @param string $event_id The Event ID.
	 * @return array
	 */
	public function get_event_details( $event_id ) {
		$fields = $this->get_default_fields( 'event' );

		// Accepts, slug / id / slugId as the query-by fields.
		$query     = '
		query ( $eventId: ID! ) {
			event( id: $eventId ) {
				' . implode( ' ', $fields ) . '
			}
		}';
		$variables = array(
			'eventId' => $event_id,
		);

		$result = $this->send_paginated_request( $query, $variables );

		if ( is_wp_error( $result ) || ! array_key_exists( 'event', $result ) ) {
			return $result;
		}

		$event = $result['event'] ?: false;

		if ( $event ) {
			$event = $this->apply_backcompat_fields( 'event',  $event );
		}

		return $event;
	}

	/**
	 * Retrieve the event Status for a range of given IDs.
	 *
	 * @param array $event_ids An array of [ id => MeetupID, id2 => MeetupID2 ] to query for.
	 * @return array Array of Event Statuses if events is found, null values if MeetupID doesn't exist.
	 */
	public function get_events_status( $event_ids ) {
		/* $events = [ id => $meetupID, id2 => $meetupID2 ] */

		$return = array();
		$chunks = array_chunk( $event_ids, 250, true );

		foreach ( $chunks as $chunked_events ) {
			$keys  = array();
			$query = '';

			foreach ( $chunked_events as $id => $event_id ) {
				$key          = 'e' . md5( $id );
				$keys[ $key ] = $id;

				$query .= sprintf(
					'%s: event( id: "%s" ) { id status }' . "\n",
					$key,
					esc_attr( $event_id )
				);
			}

			$result = $this->send_paginated_request( "query { $query }" );

			if ( is_wp_error( $result ) || ! isset( $result ) ) {
				return $result;
			}

			// Unwrap it.
			foreach ( $result as $id => $data ) {
				$return[ $keys[ $id ] ] = $data;
			}
		}

		return $return;
	}

	/**
	 * Retrieve details about a group.
	 *
	 * @param string $group_slug The slug/urlname of a group.
	 * @param array  $args       Optional. 'fields' and 'event_fields' may be defined.
	 *
	 * @return array|WP_Error
	 */
	public function get_group_details( $group_slug, $args = array() ) {
		$fields = $this->get_default_fields( 'group' );

		$events_fields = array(
			'dateTime',
			'rsvps { yesCount }',
		);

		if ( ! empty( $args['fields'] ) && is_array( $args['fields'] ) ) {
			$fields = array_merge( $fields, $args['fields'] );
		}
		if ( ! empty( $args['events_fields'] ) && is_array( $args['events_fields'] ) ) {
			$events_fields = array_merge( $events_fields, $args['events_fields'] );
		} elseif ( ! empty( $args['events_fields'] ) && true === $args['events_fields'] ) {
			$events_fields = array_merge( $events_fields, $this->get_default_fields( 'events' ) );
		}

		$query     = '
		query ( $urlname: String! ) {
			groupByUrlname( urlname: $urlname ) {
				' . implode( ' ', $fields ) . '
				events ( first: 1, status: PAST, sort: DESC ) {
					totalCount
					edges {
						node {
							' . implode( ' ', $events_fields ) . '
						}
					}
				}
			}
		}';

		$variables = array(
			'urlname' => $group_slug,
		);

		$result = $this->send_paginated_request( $query, $variables );

		if ( is_wp_error( $result ) || ! isset( $result['groupByUrlname'] ) ) {
			return $result;
		}

		// Format it similar to previous response payload.
		$group = $this->apply_backcompat_fields( 'group', $result['groupByUrlname'] );

		return $group;
	}

	/**
	 * Retrieve details about group members.
	 *
	 * @param string $group_slug The slug/urlname of a group.
	 * @param array  $args       Optional. 'fields' and 'filters' may be defined.
	 *
	 * @return array|WP_Error
	 */
	public function get_group_members( $group_slug, $args = array() ) {
		$fields = $this->get_default_fields( 'memberships' );

		if ( ! empty( $args['fields'] ) && is_array( $args['fields'] ) ) {
			$fields = array_merge(
				$fields,
				$args['fields']
			);
		}

		// Filters.
		$filters = array();
		if ( isset( $args['role'] ) && 'leads' === $args['role'] ) {
			// See https://www.meetup.com/api/schema/#MembershipStatus for valid statuses.
			$filters[] = 'status: LEADER';
		}

		if ( isset( $args['filters'] ) ) {
			foreach ( $args['filters'] as $key => $value ) {
				$filters[] = "{$key}: {$value}";
			}
		}

		// 'memberships' => 'GroupUserConnection' not documented.
		$query     = '
		query ( $urlname: String!, $perPage: Int!, $cursor: String ) {
			groupByUrlname( urlname: $urlname ) {
				memberships ( first: $perPage, after: $cursor, filter: { ' . implode( ', ', $filters ) . ' } ) {
					' . $this->pagination . '
					edges {
						node {
							' . implode( ' ', $fields ) . '
						}
					}
				}
			}
		}';
		$variables = array(
			'urlname' => $group_slug,
			'perPage' => 200,
			'cursor'  => null,
		);

		$results = $this->send_paginated_request( $query, $variables );
		if ( is_wp_error( $results ) || ! isset( $results['groupByUrlname'] ) ) {
			return $results;
		}

		// Select memberships.edges[*].node.
		$results = array_column(
			$results['groupByUrlname']['memberships']['edges'],
			'node'
		);

		return $results;
	}

	/**
	 * Query all events from the Network.
	 */
	public function get_network_events( array $args = array() ) {
		$defaults = array(
			'filters'        => array(),
			'max_event_date' => time() + YEAR_IN_SECONDS,
			'min_event_date' => false,
			'online_events'  => null, // true: only online events, false: only IRL events.
			'status'         => 'upcoming', // UPCOMING, PAST, CANCELLED.
			'sort'           => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		$fields = $this->get_default_fields( 'event' );

		// See https://www.meetup.com/api/schema/#ProNetworkEventsFilter.
		$filters = array();

		if ( $args['min_event_date'] ) {
			$filters['eventDateMin'] = 'eventDateMin: "' . $this->date_as_iso( $args['min_event_date'] ) . '"';
		}
		if ( $args['max_event_date'] ) {
			$filters['eventDateMax'] = 'eventDateMax: "' . $this->date_as_iso( $args['max_event_date'] ) . '"';
		}

		if ( ! is_null( $args['online_events'] ) ) {
			$filters['isOnlineEvent'] = 'isOnlineEvent: ' . ( $args['online_events'] ? 'true' : 'false' );
		}

		// See https://www.meetup.com/api/schema/#ProNetworkEventStatus.
		if ( $args['status'] && in_array( $args['status'], array( 'cancelled', 'upcoming', 'past' ) ) ) {
			// Elsewhere in the API this is a constant enum, and 'upcoming = ACTIVE', but not here.
			$filters['status'] = 'status: "' . strtoupper( $args['status'] ) . '"';
		}

		if ( $args['filters'] ) {
			foreach ( $args['filters'] as $key => $filter ) {
				$filters[ $key ] = "{$key}: {$filter}";
			}
		}

		$query     = '
		query ( $urlname: ID, $perPage: Int!, $cursor: String ) {
			proNetwork( urlname: $urlname ) {
				eventsSearch ( input: { first: $perPage, after: $cursor, filter: { ' . implode( ', ', $filters )  . ' } } ) {
					' . $this->pagination . '
					edges {
						node {
							' . implode( ' ', $fields ) . '
						}
					}
				}
			}
		}';

		$variables = array(
			'urlname' => 'wordpress',
			'perPage' => 1000, // More per-page to avoid hitting request limits.
			'cursor'  => null,
		);

		$results = $this->send_paginated_request( $query, $variables );

		if ( is_wp_error( $results ) || ! array_key_exists( 'eventsSearch', $results['proNetwork'] ) ) {
			return $results;
		}

		if ( empty( $results['proNetwork']['eventsSearch'] ) ) {
			return array();
		}

		// Select edges[*].node.
		$events = array_column(
			$results['proNetwork']['eventsSearch']['edges'],
			'node'
		);

		$events = $this->apply_backcompat_fields( 'events', $events );

		return $events;

	}

	/**
	 * Retrieve data about events associated with one particular group.
	 *
	 * @param string $group_slug The slug/urlname of a group.
	 * @param array  $args       Optional. 'status', 'fields' and 'filters' may be defined.
	 *
	 * @return array|WP_Error
	 */
	public function get_group_events( $group_slug, array $args = array() ) {
		$defaults = array(
			'status'          => 'upcoming',
			'no_earlier_than' => '',
			'no_later_than'   => '',
			'fields'          => array(),
		);
		$args     = wp_parse_args( $args, $defaults );
		$fields   = $this->get_default_fields( 'event' );

		if ( ! empty( $args['fields'] ) && is_array( $args['fields'] ) ) {
			$fields = array_merge(
				$fields,
				$args['fields']
			);
		}

		$filters = [];
		if ( $args['no_earlier_than'] ) {
			$filters['afterDateTime'] = 'afterDateTime: "' . $this->date_as_iso( $args['no_earlier_than'] ) . '"';
		}
		if ( $args['no_later_than'] ) {
			$filters['beforeDateTime'] = 'beforeDateTime: "' . $this->date_as_iso( $args['no_later_than'] ) . '"';
		}

		$status_map = [
			'upcoming'  => 'ACTIVE',
			'past'      => 'PAST',
			'draft'     => 'DRAFT',
			'cancelled' => 'CANCELLED',
		];
		if ( $args['status'] ) {
			$statuses         = [];
			$requested_status = is_array( $args['status'] ) ? $args['status'] : array_map( 'trim', explode(',', $args['status'] ) );
			foreach ( $requested_status as $s ) {
				if ( ! isset( $status_map[ $s ] ) ) {
					return new WP_Error(
						'invalid_status',
						sprintf( 'Invalid status: %s', esc_html( $s ) )
					);
				}
				$statuses[] = $status_map[ $s ];
			}

			if ( count( $statuses ) > 1 ) {
				$status = '[' . implode( ', ', $statuses ) . ']';
			} else {
				$status = $statuses[0];
			}

			$filters['status'] = 'status: ' . $status;
		}

		$query     = '
		query ( $urlname: String!, $perPage: Int!, $cursor: String ) {
			groupByUrlname( urlname: $urlname ) {
				events (
					first: $perPage, 
					after: $cursor,
					filter: {' . implode( ', ', $filters ) . '},
					sort: DESC
				) {
					' . $this->pagination . '
					edges {
						node {
							' . implode( ' ', $fields ) . '
						}
					}
				}
			}
		}';

		$variables = array(
			'urlname' => $group_slug,
			'perPage' => 500,
			'cursor'  => null,
		);

		$results = $this->send_paginated_request( $query, $variables );
		if ( is_wp_error( $results ) || ! isset( $results['groupByUrlname'] ) ) {
			return $results;
		}

		// Select {$event_field}.edges[*].node.
		$events = array_column(
			$results['groupByUrlname']['events']['edges'],
			'node'
		);

		$events = $this->apply_backcompat_fields( 'events', $events );

		return $events;
	}

	/**
	 * Find out how many results are available for a particular request.
	 *
	 * @param string $route The Meetup.com API route to send a request to.
	 * @param array  $args  Optional.  'pro_join_date_max', 'pro_join_date_min', and 'filters' may be defined.
	 *
	 * @return int|WP_Error
	 */
	public function get_result_count( $route, array $args = array() ) {
		$result  = false;
		$filters = array();

		// Number of groups in the Pro Network.
		if ( 'pro/wordpress/groups' !== $route ) {
			return false;
		}

		// https://www.meetup.com/api/schema/#GroupAnalyticsFilter.
		if ( ! empty( $args['pro_join_date_max'] ) ) {
			$filters['proJoinDateMax'] = 'proJoinDateMax: "' . $this->date_as_iso( $args['pro_join_date_max'] ) . '"';
		}
		if ( ! empty( $args['pro_join_date_min'] ) ) {
			$filters['proJoinDateMin'] = 'proJoinDateMin: "' . $this->date_as_iso( $args['pro_join_date_min'] ) . '"';
		}

		if ( isset( $args['filters'] ) ) {
			foreach ( $args['filters'] as $key => $value ) {
				$filters[ $key ] = "{$key}: {$value}";
			}
		}

		$query = '
		query {
			proNetwork( urlname: "WordPress" ) {
				groupsSearch( input: { filter: { ' .  implode( ', ', $filters ) . ' } } ) {
					totalCount
				}
			}
		}';

		$results = $this->send_paginated_request( $query );
		if ( is_wp_error( $results ) ) {
			return $results;
		}

		return (int) $results['proNetwork']['groupsSearch']['totalCount'];
	}

	/**
	 * Get the default fields for each object type.
	 *
	 * @param string $type The Object type.
	 * @return array Fields to query.
	 */
	protected function get_default_fields( $type ) {
		if ( 'event' === $type ) {
			// See https://www.meetup.com/api/schema/#Event for valid fields.
			return array(
				'id',
				'title',
				'description',
				'eventUrl',
				'status',
				'dateTime',
				'endTime',
				'duration',
				'createdTime',
				'eventType',
				'rsvps { attendedCount noCount totalCount yesCount }',
				'group {
					' . implode( ' ', $this->get_default_fields( 'group' ) ) . '
				}',
				'venues {
					' . implode( ' ', $this->get_default_fields( 'venues' ) ) . '
				}',
			);
		} elseif ( 'memberships' === $type ) {
			// See https://www.meetup.com/api/schema/#User for valid fields.
			return array(
				'id',
				'name',
				'email',
			);
		} elseif ( 'group' === $type ) {
			return array(
				'id',
				'name',
				'urlname',
				'link',
				'city',
				'state',
				'country',
				'groupAnalytics {
					totalPastEvents,
					totalMembers,
					lastEventDate,
				}',
				'foundedDate',
				'proJoinDate',
				'lat',
				'lon',
			);
		} elseif ( 'venue' === $type || 'venues' == $type ) {
			return array(
				'id',
				'lat',
				'lon',
				'name',
				'city',
				'state',
				'country',
			);
		}
	}

	/**
	 * Apply back-compat fields/filters for previous uses of the client.
	 *
	 * Can be removed once all uses of the library have migrated over.
	 *
	 * @param string $type   The type of result object.
	 * @param array  $result The result to back-compat.
	 *
	 * @return The $result with back-compat.
	 */
	protected function apply_backcompat_fields( $type, $result ) {
		if ( 'event' === $type ) {

			$result['name'] = $result['title'];

			$result['isOnline']  ??= ( 'ONLINE' === ( $result['eventType'] ?? '' ) );
			$result['going']     ??= $result['rsvps']['yesCount'] ?? 0;
			$result['createdAt'] ??= $this->datetime_to_time( $result['createdTime'] );

			if ( ! empty( $result['dateTime'] ) ) {
				// Required for utc_offset below.
				$result['time'] = $this->datetime_to_time( $result['dateTime'] );
			}

			// Parse an ISO DateInterval into seconds.
			$now                = time();
			$result['duration'] = ( DateTimeImmutable::createFromFormat( 'U', $now ) )->add( new DateInterval( $result['duration'] ) )->getTimestamp() - $now;

			$result['utc_offset'] = 0;
			if ( ! empty( $result['timezone'] ) && isset( $result['time'] ) ) {
				$result['utc_offset'] = (
					new DateTimeImmutable(
						// $result['time'] is back-compat above.
						gmdate( 'Y-m-d H:i:s', $result['time'] ),
						new DateTimeZone( $result['timezone'] )
					)
				)->getOffset();
			}

			// Remove Lat/Lon for online events.
			$result['venues'] ??= [];
			foreach ( $result['venues'] as &$venue ) {
				if ( is_numeric( $venue['id'] ) ) {
					$venue['id'] = (int) $venue['id'];
				}

				if ( self::ONLINE_VENUE_ID === $venue['id'] ) {
					$venue['lat'] = '';
					$venue['lon'] = '';
				} elseif ( empty( $result['venue'] ) ) {
					// Default to first non-online venue if there's multiple.
					$result['venue'] = $venue;
				}
			}

			// If we didn't find a venue above, but there's venues, use the first one even if online.
			if ( empty( $result['venue'] ) && ! empty( $result['venues'] ) ) {
				$result['venue'] = $result['venues'][0];
			}

			if ( ! empty( $result['venue'] ) ) {
				$result['venue']['localized_location']     = $this->localise_location( $result['venue'] );
				$result['venue']['localized_country_name'] = $this->localised_country_name( $result['venue']['country'] );
			}

			if ( ! empty( $result['group'] ) ) {
				$result['group'] = $this->apply_backcompat_fields( 'group', $result['group'] );
			}

			$result['status'] = strtolower( $result['status'] );
			if ( in_array( $result['status'], array( 'published', 'past', 'active', 'autosched' ) ) ) {
				$result['status'] = 'upcoming'; // Right, past is upcoming in this context.
			}

			$result['yes_rsvp_count'] = $result['going'];
			$result['link']           = $result['eventUrl'];
		}

		if ( 'events' === $type ) {
			foreach ( $result as &$event ) {
				$event = $this->apply_backcompat_fields( 'event', $event );
			}
		}

		if ( 'group' === $type ) {
			// Stub in the fields that are different.
			$result['founded_date']           = $this->datetime_to_time( $result['foundedDate'] );
			$result['created']                = $result['founded_date'];
			$result['localized_location']     = $this->localise_location( $result );
			$result['localized_country_name'] = $this->localised_country_name( $result['country'] );
			$result['members']                = $result['groupAnalytics']['totalMembers'] ?? 0;
			$result['member_count']           = $result['members'];

			$result['latitude']  ??= $result['lat'] ?? '';
			$result['longitude'] ??= $result['lon'] ?? '';

			if ( ! empty( $result['proJoinDate'] ) ) {
				$result['pro_join_date'] = $this->datetime_to_time( $result['proJoinDate'] );
			}

			// get_group_details() triggers this branch:
			if ( ! empty( $result['events']['edges'] ) ) {
				$result['last_event']       = array(
					'time'           => $this->datetime_to_time( end( $result['events']['edges'] )['node']['dateTime'] ),
					'yes_rsvp_count' => end( $result['events']['edges'] )['node']['rsvps']['yesCount'] ?? 0,
				);
				$result['past_event_count'] = $result['events']['totalCount'] ?? 0;
			} elseif ( ! empty( $result['groupAnalytics']['lastEventDate'] ) ) {
				// NOTE: last_event here vs above differs intentionally.
				$result['last_event']       = $this->datetime_to_time( $result['groupAnalytics']['lastEventDate'] );
				$result['past_event_count'] = $result['groupAnalytics']['totalPastEvents'];
			}

			$result['lat'] ??= $result['latitude'];
			$result['lon'] ??= $result['longitude'];
		}
		if ( 'groups' === $type ) {
			foreach ( $result as &$group ) {
				$group = $this->apply_backcompat_fields( 'group', $group );
			}
		}

		return $result;
	}

	/**
	 * Generate a localised location name.
	 *
	 * For the US this is 'City, ST, USA'.
	 * For Canada this is 'City, ST, Canada'.
	 * For the rest of world, this is 'City, CountryName'.
	 */
	protected function localise_location( $args = array() ) {
		// Hard-code the Online event location.
		if ( ! empty( $args['id'] ) && self::ONLINE_VENUE_ID == $args['id'] ) {
			return 'online';
		}

		$country = $args['country'] ?? '';
		$state   = $args['state']   ?? '';
		$city    = $args['city']    ?? '';
		$country = strtoupper( $country );

		// Only the USA & Canada have valid states in the response. Others have states, but are incorrect.
		if ( 'US' === $country || 'CA' === $country ) {
			$state = strtoupper( $state );
		} else {
			$state = '';
		}

		// Set countries to USA, AU, or Australia in that order.
		$country = $this->localised_country_name( $country );

		return implode( ', ',  array_filter( array( $city, $state, $country ) ) ) ?: false;
	}

	/**
	 * Localise a country code to a country name using WP-CLDR if present.
	 *
	 * @param string $country Country Code.
	 * @return Country Name, or country code upon failure.
	 */
	public function localised_country_name( $country ) {
		$localised_country = '';
		$country           = strtoupper( $country );

		// Shortcut, CLDR isn't always what we expect here.
		$shortcut = array(
			'US' => 'USA',
			'HK' => 'Hong Kong',
			'SG' => 'Singapore',
		);
		if ( ! empty( $shortcut[ $country ] ) ) {
			return $shortcut[ $country ];
		}

		if ( ! class_exists( '\WP_CLDR' ) && file_exists( WP_PLUGIN_DIR . '/wp-cldr/class-wp-cldr.php' ) ) {
			require WP_PLUGIN_DIR . '/wp-cldr/class-wp-cldr.php';
		}

		if ( class_exists( '\WP_CLDR' ) ) {
			$cldr = new \WP_CLDR();

			$localised_country = $cldr->get_territory_name( $country );
		}

		return $localised_country ?: $country;
	}
}
