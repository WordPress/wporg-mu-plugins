<?php

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks\Stream;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_filter( 'wp_stream_record_array', __NAMESPACE__ . '\include_user_name_in_creation_log' );
add_filter( 'wp_stream_is_record_excluded', __NAMESPACE__ . '\exclude_profile_updates_as_part_of_user_creation', 10, 2 );

/**
 * Stream by default logs new user registrations as 'New user registration' which doesn't come up in search-by-username.
 *
 * Suffix the message with the user_login.
 *
 * @param array $record
 *
 * @return array
 */
function include_user_name_in_creation_log( $record ) {
	if (
		'users' === $record['connector'] &&
		'users' === $record['context'] &&
		'created' === $record['action']
	) {
		$user = get_user_by( 'id', $record['object_id'] );
		if ( $user && ! str_contains( $record['summary'], $user->user_login ) ) {
			$record['summary'] .= ': ' . $user->user_login;
		}
	}

	return $record;
}

/**
 * Stream records 'profile updated' events during user registration, as we call `wp_update_user(). Avoid these.
 *
 * @param bool $exclude If this record should be excluded.
 * @param array $record The record to insert.
 * @return bool
 */
function exclude_profile_updates_as_part_of_user_creation( $exclude, $record ) {
	if (
		// Users are not logged in as part of registration.
		! is_user_logged_in() &&
		doing_action( 'profile_update' ) &&
		defined( 'WPORG_LOGIN_REGISTER_BLOGID' ) &&
		WPORG_LOGIN_REGISTER_BLOGID === get_current_blog_id() &&
		'users' === $record['connector'] &&
		'profiles' === $record['context'] &&
		'updated' === $record['action']
	) {
		$exclude = true;
	}

	return $exclude;
}
