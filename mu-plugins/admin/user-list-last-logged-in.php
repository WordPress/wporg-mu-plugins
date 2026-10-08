<?php
namespace WordPressdotorg\MU_Plugins\Admin\Users\Last_Logged_In;

add_filter( 'manage_users_columns', __NAMESPACE__ . '\manage_users_columns' );
add_filter( 'manage_users_sortable_columns', __NAMESPACE__ . '\manage_users_sortable_columns' );
add_action( 'pre_get_users', __NAMESPACE__ . '\pre_get_users' );
add_filter( 'manage_users_custom_column', __NAMESPACE__ . '\manage_users_custom_column', 10, 3 );

/**
 * Add the last-login column.
 *
 * @param array $columns User list columns.
 * @return array Updated columns.
 */
function manage_users_columns( $columns ) {
	$columns['last-logged-in'] = 'Last Logged In';

	return $columns;
}

/**
 * Register the last-login sort key.
 *
 * @param array $columns Sortable columns.
 * @return array Updated sortable columns.
 */
function manage_users_sortable_columns( $columns ) {
	$columns['last-logged-in'] = 'last-logged-in';

	return $columns;
}

/**
 * Sort the user list by its recorded last-login date.
 *
 * @param \WP_User_Query $query User list query.
 * @return void
 */
function pre_get_users( $query ) {
	if ( ! is_admin() || 'last-logged-in' !== $query->get( 'orderby' ) || ! current_user_can( 'list_users' ) ) {
		return;
	}

	// Must use a meta query to account for users who have never logged in.
	$meta_query = [
		'relation'             => 'OR',
		'last_logged_in_never' => [
			'key'     => 'last_logged_in',
			'compare' => 'NOT EXISTS',
		],
		'last_logged_in'       => [
			'key'  => 'last_logged_in',
			'type' => 'DATE',
		],
	];

	$query->set( 'orderby', 'last_logged_in' );
	$query->set( 'meta_query', $meta_query );
}

/**
 * Render the last-login column.
 *
 * @param string $value   Existing column markup.
 * @param string $column  Column identifier.
 * @param int    $user_id User identifier.
 * @return string Column markup.
 */
function manage_users_custom_column( $value, $column, $user_id ) {
	if ( 'last-logged-in' !== $column || ! current_user_can( 'list_users' ) ) {
		return $value;
	}

	// https://meta.trac.wordpress.org/changeset/11329 added last login date logging, 2021-11-17
	$user            = get_user_by( 'id', $user_id );
	$user_registered = strtotime( $user->user_registered );
	$last_login      = strtotime( $user->last_logged_in );

	// Disregard the login if it happened within 24hrs of registering.
	if ( $last_login && ( $last_login - $user_registered ) < DAY_IN_SECONDS ) {
		$last_login = $user_registered;
	}

	$text = '';
	if ( $last_login && $last_login > $user_registered ) {
		if ( $last_login >= strtotime( '-1 month' ) ) {
			// Login sessions are two weeks.
			$text = '<em>Within the last month</em>';
		} else {
			$text = gmdate( 'F Y', $last_login );
		}
	} elseif ( $user_registered > strtotime( '2021-11-17' ) ) {
		$text = '<em title="Has never logged in, other than during registration">Never since registering</em>';
	} else {
		$text = '<em title="Sometime before Nov 17th, 2021">Unknown</em>';
	}

	return $text;
}
