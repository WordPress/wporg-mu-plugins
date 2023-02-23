<?php
namespace WordPressdotorg\MU_Plugins\DB_User_Sessions;

add_filter( 'session_token_manager', function( $manager ) {
	if ( in_array( wp_get_environment_type(), [ 'production', 'staging' ], true ) ) {
		require_once __DIR__ . '/class-tokens.php';

		$manager = __NAMESPACE__ . '\Tokens';
	}

	return $manager;
} );
