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
		! $exclude &&
		doing_action( 'profile_update' ) &&
		defined( 'WPORG_LOGIN_REGISTER_BLOGID' ) &&
		WPORG_LOGIN_REGISTER_BLOGID == $record['blog_id'] &&
		'profiles' === $record['context'] &&
		'updated' === $record['action'] &&
		// This is a sanity check, the user is stored in 'object_id', and 'user_id' is the user updating the data.
		// If a user_id is set, this was not during a user registration.
		! $record['user_id']
	) {
		$exclude = true;
	}

	return $exclude;
}
