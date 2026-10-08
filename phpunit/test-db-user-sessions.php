<?php
/**
 * Tests for targeted session deletion.
 *
 * @package WordPressdotorg\MU_Plugins
 */

declare( strict_types = 1 );

use WordPressdotorg\MU_Plugins\DB_User_Sessions\Tokens;

/**
 * Verify session writes against an isolated temporary table.
 */
class Test_DB_User_Sessions extends WP_UnitTestCase {

	/**
	 * Deleting selected verifiers preserves other sessions and clears their caches.
	 *
	 * @return void
	 */
	public function test_delete_verifiers_is_scoped_to_user_and_values(): void {
		global $wpdb;

		require_once WPMU_PLUGIN_DIR . '/db-user-sessions/class-tokens.php';
		$wpdb->query( 'CREATE TEMPORARY TABLE wporg_user_sessions (user_id bigint NOT NULL, verifier varchar(64) NOT NULL)' );
		$verifiers = array( 'first', "quote' OR 1=1 --" );
		try {
			foreach ( array( 123, 456 ) as $user_id ) {
				foreach ( array_merge( $verifiers, array( 'preserved' ) ) as $verifier ) {
					$wpdb->insert(
						'wporg_user_sessions',
						array(
							'user_id'  => $user_id,
							'verifier' => $verifier,
						),
						array( '%d', '%s' )
					);
					wp_cache_set( $user_id . '__' . $verifier, array( 'expiration' => time() + HOUR_IN_SECONDS ), 'user_sessions' );
				}
			}

			$reflection = new ReflectionClass( Tokens::class );
			$manager    = $reflection->newInstanceWithoutConstructor();
			$user_id    = $reflection->getProperty( 'user_id' );
			$user_id->setAccessible( true );
			$user_id->setValue( $manager, 123 );
			$delete = $reflection->getMethod( 'delete_sessions_by_verifiers' );
			$delete->setAccessible( true );
			$delete->invoke( $manager, $verifiers );

			$this->assertSame( array( 'preserved' ), $wpdb->get_col( 'SELECT verifier FROM wporg_user_sessions WHERE user_id = 123' ) );
			$this->assertSame( '3', $wpdb->get_var( 'SELECT COUNT(*) FROM wporg_user_sessions WHERE user_id = 456' ) );
			foreach ( $verifiers as $verifier ) {
				$this->assertFalse( wp_cache_get( '123__' . $verifier, 'user_sessions' ) );
				$this->assertIsArray( wp_cache_get( '456__' . $verifier, 'user_sessions' ) );
			}
		} finally {
			$wpdb->query( 'DROP TEMPORARY TABLE wporg_user_sessions' );
			foreach ( array( 123, 456 ) as $user_id ) {
				foreach ( array_merge( $verifiers, array( 'preserved' ) ) as $verifier ) {
					wp_cache_delete( $user_id . '__' . $verifier, 'user_sessions' );
				}
			}
		}
	}
}
