<?php

namespace WordPressdotorg\MU_Plugins\Google_Map;

defined( 'WPINC' ) || die();

add_action( 'prime_event_filters', __NAMESPACE__ . '\get_events', 10, 4 );


/**
 * Schedule a cron job to update events that match the given filter/dates.
 */
function schedule_filter_cron( string $filter_slug, string $start_date, string $end_date ): void {
	$cron_args = array( $filter_slug, $start_date, $end_date, true );

	// Some custom filter slugs using `google_map_event_filters_{$filter_slug}` to pass data may need to run their
	// own cron to prime the cache.
	// See WordCamp's `themes/wporg-events-2023/inc/city-landing-pages.php` for an example.
	$register_cron = apply_filters( 'google-map-event-filters-register-cron', true, $filter_slug );

	if ( $register_cron && ! wp_next_scheduled( 'prime_event_filters', $cron_args ) ) {
		wp_schedule_event(
			time() + HOUR_IN_SECONDS,
			'hourly',
			'prime_event_filters',
			$cron_args
		);
	}
}

/**
 * Get events matching the provider filter during the given timeframe.
 */
function get_events( string $filter_slug, int $start_timestamp, int $end_timestamp, bool $force_refresh = false ) : array {
	$events        = array();
	$cache_key     = get_cache_key( compact( 'filter_slug', 'start_timestamp', 'end_timestamp' ) );
	$cached_events = get_transient( $cache_key );

	if ( $cached_events && ! $force_refresh ) {
		$events = $cached_events;

	} else {
		switch ( $filter_slug ) {
			case 'all-upcoming':
				$events = get_all_upcoming_events();
				break;

			case 'wp20':
			case 'sotw':
				$potential_matches = get_events_between_dates( $start_timestamp, $end_timestamp );
				$events            = filter_potential_events( $filter_slug, $potential_matches );
				break;

			default:
				$events = apply_filters( "google_map_event_filters_{$filter_slug}", array() );
		}

		// Store for a day to make sure it never expires before the priming cron job runs.
		set_transient( $cache_key, $events, DAY_IN_SECONDS );
	}

	return $events;
}

/**
 * Get the cache key for a given set of events.
 *
 * Customizing the key is sometimes needed when using the `google_map_event_filters_{$filter_slug}` filter.
 * See WordCamp's `themes/wporg-events-2023/inc/city-landing-pages.php` for an example.
 */
function get_cache_key( array $parts ): string {
	$items = apply_filters( 'google_map_event_filters_cache_key_parts', $parts );
	$key   = 'google-map-event-filters-' . md5( wp_json_encode( implode( '|', $items ) ) );

	return $key;
}

/**
 * Get a list of all upcoming events across all sites.
 */
function get_all_upcoming_events(): array {
	global $wpdb;

	$query = '
		SELECT
			id, `type`, title, url, meetup, location, latitude, longitude, date_utc,
			date_utc_offset AS tz_offset
		FROM `wporg_events`
		WHERE
			status = "scheduled" AND
			(
				( "wordcamp" = type AND date_utc BETWEEN NOW() AND ADDDATE( NOW(), 180 ) ) OR
				( "meetup" = type AND date_utc BETWEEN NOW() AND ADDDATE( NOW(), 30 ) )
			)
		ORDER BY date_utc ASC
		LIMIT 400'
	;

	if ( 'latin1' === DB_CHARSET ) {
		$events = $wpdb->get_results( $query );
	} else {
		$events = get_latin1_results_with_prepared_query( $query );
	}

	$events = prepare_events( $events );

	return $events;
}

/**
 * Get a list of all events during a given timeframe.
 */
function get_events_between_dates( int $start_timestamp, int $end_timestamp ) : array {
	global $wpdb;

	$query = $wpdb->prepare( '
		SELECT
			id, `type`, source_id, title, url, description, meetup, location, latitude, longitude, date_utc,
			date_utc_offset AS tz_offset
		FROM `wporg_events`
		WHERE
			status = "scheduled" AND
			date_utc BETWEEN FROM_UNIXTIME( %d ) AND FROM_UNIXTIME( %d )
		ORDER BY date_utc ASC
		LIMIT 1000',
		$start_timestamp,
		$end_timestamp
	);

	if ( 'latin1' === DB_CHARSET ) {
		$events = $wpdb->get_results( $query );
	} else {
		$events = get_latin1_results_with_prepared_query( $query );
	}

	$events = prepare_events( $events );

	return $events;
}

/**
 * Clean up events
 */
function prepare_events( array $events ): array {
	foreach ( $events as $event ) {
		// `capital_P_dangit()` won't work here because the current filter isn't `the_title` and there isn't a safelisted prefix before `$text`.
		$event->title = str_replace( 'Wordpress', 'WordPress', $event->title );

		// `date_utc` is a misnomer, the value is actually in the local timezone of the event. So, convert to a true Unix timestamp (UTC).
		// Can't do this reliably in the query because MySQL converts it to the server timezone.
		$event->timestamp = strtotime( $event->date_utc ) - $event->tz_offset;

		unset( $event->date_utc );
	}

	return $events;
}

/**
 * Query a table that's encoded with the `latin1` charset.
 *
 * wordpress.org uses a `DB_CHARSET` of `latin1` for legacy reasons, but wordcamp.org and others use `utf8mb4`.
 * `wporg_events` uses `latin1`, so querying it with `utf8mb4` will produce Mojibake.
 *
 * @param string $prepared_query ⚠️ This must have already be ran through `$wpdb->prepare()` if needed.
 *
 * @return object|null
 */
function get_latin1_results_with_prepared_query( string $prepared_query ) {
	global $wpdb;

	// Local environments don't always use HyperDB, but production does.
	$db_handle = is_a( $wpdb, 'hyperdb' ) ? $wpdb->db_connect( $prepared_query ) : $wpdb->dbh;
	$wpdb->set_charset( $db_handle, 'latin1', 'latin1_swedish_ci' );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- This function doesn't have the context to prepare it, the caller must.
	$results = $wpdb->get_results( $prepared_query );

	// Revert to the default charset to avoid affecting other queries.
	$wpdb->set_charset( $db_handle, DB_CHARSET, DB_COLLATE );

	return $results;
}

/**
 * Extract the desired events from an array of potential events.
 */
function filter_potential_events( string $filter_slug, array $potential_events ) : array {
	$matched_events = array();
	$other_events   = array();

	switch ( $filter_slug ) {
		case 'sotw':
			$false_positives = array();
			$keywords        = array(
				'sotw', 'state of the word',
			);
			break;

		case 'wp20':
			$false_positives = array( "292525625", "293437294" );
			$keywords        = array(
				'wp20', '20 year', '20 ano', '20 año', '20 candeline', '20 jaar', 'wordt 20', '20 yaşında',
				'anniversary', 'aniversário', 'aniversario', 'birthday', 'cumpleaños', 'celebrate',
				'Tanti auguri',
			);
			break;

		default:
			return array();
	}

	foreach ( $potential_events as $event ) {
		$match = false;

		// Have to use `source_id` because `id` is rotated by `REPLACE INTO` when table is updated.
		if ( in_array( $event->source_id, $false_positives, true ) ) {
			$other_events[] = $event;
			continue;
		}

		foreach ( $keywords as $keyword ) {
			if ( false !== stripos( $event->description, $keyword ) || false !== stripos( $event->title, $keyword ) ) {
				// These are no longer needed, so remove it to save space in the database.
				unset( $event->description );
				unset( $event->source_id );
				$matched_events[] = $event;
				continue 2;
			}
		}

		if ( ! $match ) {
			$other_events[] = $event;
		}
	}

	print_results( $filter_slug, $matched_events, $other_events );

	return $matched_events;
}

/**
 * Print the matched/unmatched events for manual review.
 *
 * Run `wp cron event run prime_event_filters` to see this.
 */
function print_results( string $filter, array $matched_events, array $other_events ) : void {
	if ( 'cli' !== php_sapi_name() ) {
		return;
	}

	$matched_names = wp_list_pluck( $matched_events, 'title' );
	$other_names   = wp_list_pluck( $other_events, 'title' );

	sort( $matched_names );
	sort( $other_names );

	printf( "\n\n============================== \nResults for $filter: \n==============================\n" );

	echo "\nIgnored these events. Double check for false-negatives.\n\n";
	print_r( $other_names );

	echo "\nIncluded these events. Double check for false-positives.\n\n";
	print_r( $matched_names );
}