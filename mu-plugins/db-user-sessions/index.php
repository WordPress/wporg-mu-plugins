<?php
namespace WordPressdotorg\MU_Plugins\DB_User_Sessions;

add_filter( 'session_token_manager', function() {
	require_once __DIR__ . '/class-tokens.php';

	return __NAMESPACE__ . '\Tokens';
} );
