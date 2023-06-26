<?php
namespace WordPressdotorg\MU_Plugins\Admin;

// Delay loading until admin_init.
add_action( 'admin_init', function() {
	require __DIR__ . '/users.php';
}, 1 );