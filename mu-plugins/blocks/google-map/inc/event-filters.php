<?php

namespace WordPressdotorg\MU_Plugins\Google_Map;

defined( 'WPINC' ) || die();

add_action( 'prime_event_filters', __NAMESPACE__ . '\get_events', 10, 5 );
add_action( 'purge_event_filter_jobs', __NAMESPACE__ . '\purge_event_filter_jobs' );


/**
 * Schedule a cron job to update events that match the given filter/dates.
 *
 * This makes sure that there's always a fresh cache, so that users never experience a delay while waiting for a
 * stale one to be renewed.
 */
function schedule_filter_cron( string $filter_slug, string $start_date, string $end_date, array $facets = array() ): void {
	$cron_args = array( $filter_slug, $start_date, $end_date, $facets, true );

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

	if ( ! wp_next_scheduled( 'purge_event_filter_jobs' ) ) {
		wp_schedule_event(
			time() + HOUR_IN_SECONDS,
			'weekly',
			'purge_event_filter_jobs'
		);
	}
}

/**
 * Unscheduled all crons to avoid buildup
 *
 * `prime_event_filters` jobs get created with user input to ensure fast responses for common requests, but those
 * can build up over time. We don't want to eventually have hundreds of jobs running for uncommon requests, so
 * this purges them weekly.
 */
function purge_event_filter_jobs(): void {
	wp_unschedule_hook( 'prime_event_filters' );

	// Re-prime the most common filters, so that users don't experience a delay while waiting for a new cache.
	get_events( 'all-upcoming', 0, 0, array(), true );
	get_events( 'all-upcoming', 0, 0, array( 'format' => 'online' ), true );
	get_events( 'all-upcoming', 0, 0, array( 'format' => 'in-person' ), true );
	get_events( 'all-upcoming', 0, 0, array( 'type' => 'wordcamp' ), true );
	get_events( 'all-upcoming', 0, 0, array( 'type' => 'other' ), true );
	get_events( 'all-upcoming', 0, 0, array( 'type' => 'meetup' ), true );
	get_events( 'all-past', 0, 0, array(), true );
}

/**
 * Get events matching the provider filter during the given timeframe.
 */
function get_events( string $filter_slug, int $start_timestamp, int $end_timestamp, array $facets = array(), bool $force_refresh = false ) : array {
	$events    = array();
	$page      = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;
	$facets    = clean_facets( $facets );

	// Short circuit query to avoid unnecessary delay, and make it obvious that the invalid arguments don't work.
	if ( false === $facets ) {
		return array();
	}

	$cacheable = is_cacheable( $facets, $page );

	if ( $cacheable ) {
		$cache_key = get_cache_key( array_merge(
			compact( 'filter_slug', 'start_timestamp', 'end_timestamp' ),
			$facets // It's safe to include this because of the logic around `$cacheable`.
		) );

		if ( ! $force_refresh ) {
			$cached_events = get_transient( $cache_key );

			if ( $cached_events ) {
				$events = $cached_events;
			}
		}
	}

	if ( ! $events ) {
		switch ( $filter_slug ) {
			case 'all-upcoming':
				$events = get_all_upcoming_events( $facets );
				break;

			case 'all-past':
				$events = get_all_past_events( $page );
				break;

			case 'wp20':
			case 'sotw':
				$potential_matches = get_events_between_dates( $start_timestamp, $end_timestamp );
				$events            = filter_potential_events( $filter_slug, $potential_matches );
				break;

			default:
				$events = apply_filters( "google_map_event_filters_{$filter_slug}", array() );
		}

		$events = prepare_events( $events );

		// Store for a day to make sure it never expires before the priming cron job runs.
		// There's no point in caching empty values, or scheduling crons to update them.
		if ( $cacheable && $events ) {
			set_transient( $cache_key, $events, DAY_IN_SECONDS );

			// This has to be called here so that the facets match the ones used to generate the cache.
			schedule_filter_cron( $filter_slug, $start_timestamp, $end_timestamp, $facets );
		}
	}

	return $events;
}

/**
 * Clean the provided facets and notify if any are invalid.
 *
 * @return array|false False if invalid parameters are passed. On success, an array of clean facets.
 */
function clean_facets( array $facets ) {
	foreach ( $facets as $key => & $facet ) {
		if ( is_array( $facet ) ) {
			$facet = array_filter( $facet ); // Remove empty.
		} else {
			if ( 'search' === $key ) {
				$facet = sanitize_text_field( strval( $facet ) );
			} else {
				$facet = array( $facet );
			}
		}
	}

	$facets = array_filter( $facets ); // Remove empty.

	$valid_formats = array( 'online', 'in-person' );
	$valid_types   = array( 'wordcamp', 'other', 'meetup' );
	$valid_months  = range( 1, 12 );

	if ( isset( $facets['format'] ) && array_diff( $facets['format'], $valid_formats ) ) {
		return false;
	}

	if ( isset( $facets['type'] ) && array_diff( $facets['type'], $valid_types ) ) {
		return false;
	}

	if ( isset ( $facets['month'] ) ) {
		$facets['month'] = array_map( 'absint', $facets['month'] );

		if ( array_diff( $facets['month'], $valid_months ) ) {
			return false;
		}
	}

	if ( isset( $facets['country'] ) ) {
		$facets['country'] = array_map( 'sanitize_text_field', $facets['country'] );

		foreach ( $facets['country'] as $country ) {
			if ( ! ctype_alpha( $country ) || 2 !== strlen( $country ) ) {
				return false;
			}
		}
	}

	return $facets;
}

/**
 * Determine if the given request is cacheable.
 */
function is_cacheable( array $facets, int $page ): bool {
	$cacheable = true;
	$facets    = array_filter( $facets ); // Remove empty so that `count()` below is accurate.

	// Only cache regularly visited pages.
	// Search terms vary so much that caching them probably wouldn't result in a significant degree of
	// cache hits, but it would generate a lot of extra transients. With memcached, that could push
	// more useful values out of the cache. Old pages and multi-facet requests are similar.
	if ( ! empty( $facets['search'] ) || count( $facets ) > 1 || $page !== 1 ) {
		$cacheable = false;
	} else {
		foreach ( $facets as $facet ) {
			if ( is_array( $facet ) && count( $facet ) > 1 ) {
				$cacheable = false;
				break;
			}
		}
	}

	return $cacheable;
}

/**
 * Get the cache key for a given set of events.
 *
 * Customizing the key is sometimes needed when using the `google_map_event_filters_{$filter_slug}` filter.
 * See WordCamp's `themes/wporg-events-2023/inc/city-landing-pages.php` for an example.
 */
function get_cache_key( array $parts ): string {
	$parts = array_filter( $parts ); // Remove empty so that cache key is normalized.
	$items = apply_filters( 'google_map_event_filters_cache_key_parts', $parts );
	ksort( $items ); // Normalize top-level items.

	$key = 'google-map-event-filters-' . md5( wp_json_encode( $items ) );

	return $key;
}

/**
 * Get a list of all upcoming events across all sites.
 */
function get_all_upcoming_events( array $facets = array() ): array {
	global $wpdb;

	$where = get_where_clauses( $facets );

	$query = "
		SELECT
			id, `type`, title, url, meetup, location, latitude, longitude, date_utc,
			date_utc_offset AS tz_offset
		FROM `wporg_events`
		WHERE
			status = 'scheduled' AND
			date_utc >= NOW() AND
			{$where['clauses']}
		ORDER BY date_utc ASC
		LIMIT 500"
	;

	if ( $where['values'] ) {
		$query = $wpdb->prepare( $query, $where['values'] );
	}

	if ( 'latin1' === DB_CHARSET ) {
		$events = $wpdb->get_results( $query );
	} else {
		$events = get_latin1_results_with_prepared_query( $query );
	}

	return $events;
}

/**
 * Get the `WHERE` clauses/values for a given set of facets.
 */
function get_where_clauses( array $facets ): array {
	$clauses = '1=1';
	$values  = array();

	if ( ! empty( $facets['search'] ) ) {
		$clauses .= ' AND ( title LIKE "%%%s%%" OR description LIKE "%%%s%%" OR meetup LIKE "%%%s%%" OR location LIKE "%%%s%%" )';
		$values[] = $facets['search'];
		$values[] = $facets['search'];
		$values[] = $facets['search'];
		$values[] = $facets['search'];
	}

	if ( ! empty( $facets['type'] ) ) {
		$type_clauses = array();

		foreach ( (array) $facets['type'] as $type ) {
			switch ( $type ) {
				// Traditional WordCamps are hosted on wordcamp.org.
				case 'wordcamp':
					$type_clauses[] = " ( 'wordcamp' = type AND url REGEXP 'https?://(.*)wordcamp\.org' ) ";
					break;

				// NextGen WordCamps are hosted on events.wordpress.org.
				case 'other':
					$type_clauses[] = " ( 'wordcamp' = type AND url REGEXP 'https?://events\.wordpress\.org' ) ";
					break;

				case 'meetup':
					$type_clauses[] = " 'meetup' = type";
					break;
			}
		}

		if ( $type_clauses ) {
			$clauses .= ' AND ( ' . implode( ' OR ', $type_clauses ) . ' )';
		}
	}

	if ( ! empty( $facets['month'] ) ) {
		$placeholders = implode( ', ', array_fill( 0, count( $facets['month'] ), '%d' ) );
		$clauses .= " AND MONTH( date_utc ) IN ( $placeholders )";
		$values = array_merge( $values, $facets['month'] );
	}

	// If both valid formats are selected, don't filter by format at all.
	if ( ! empty( $facets['format'] ) && 1 === count( $facets['format'] ) ) {
		if ( 'online' === $facets['format'][0] ) {
			$clauses .= ' AND location = "online" ';
		} else if ( 'in-person' === $facets['format'][0] ) {
			$clauses .= ' AND location != "online" ';
		}
	}

	if ( ! empty( $facets['country'] ) ) {
		$placeholders = implode( ', ', array_fill( 0, count( $facets['country'] ), '%s' ) );
		$clauses .= " AND LOWER( country ) IN ( $placeholders )";
		$values = array_merge( $values, array_map( 'strtolower', $facets['country'] ) );
	}

	return compact( 'clauses', 'values' );
}
/**
 * Get a list of all upcoming events across all sites.
 */
function get_all_past_events( int $page ): array {
	global $wpdb;

	$limit  = 50;
	$offset = ( $page - 1 ) * $limit;

	// wporg_events.status doesn't have a separate value for "completed", it's just scheduled events that have
	// a date in the past.
	$query = $wpdb->prepare( '
		SELECT
			id, `type`, title, url, meetup, location, latitude, longitude, date_utc,
			date_utc_offset AS tz_offset
		FROM `wporg_events`
		WHERE
			status = "scheduled" AND
			date_utc < NOW()
		ORDER BY date_utc DESC
		LIMIT %d, %d',
		$offset,
		$limit
	);

	if ( 'latin1' === DB_CHARSET ) {
		$events = $wpdb->get_results( $query );
	} else {
		$events = get_latin1_results_with_prepared_query( $query );
	}

	return $events;
}

/**
 * Get the total number of past events.
 */
function get_all_past_events_count(): int {
	global $wpdb;

	$transient_key = 'google_map_event_filters_past_events_count';
	$count         = get_transient( $transient_key );

	if ( ! $count ) {
		$count = $wpdb->get_var( '
			SELECT COUNT( id ) as found_events
			FROM `wporg_events`
			WHERE
				status = "scheduled" AND
				date_utc < NOW()'
		);

		set_transient( $transient_key, $count, HOUR_IN_SECONDS );
	}

	return $count;
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
