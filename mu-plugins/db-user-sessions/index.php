<?php
namespace WordPressdotorg\MU_Plugins\DB_User_Sessions;

add_filter( 'session_token_manager', function( $manager ) {
	if ( in_array( wp_get_environment_type(), [ 'production', 'staging' ], true ) ) {
		$manager = __NAMESPACE__ . '\Tokens';

		// The user sesions are global, not per-site.
		wp_cache_add_global_groups( 'user_sessions' );
	}

	return $manager;
} );
