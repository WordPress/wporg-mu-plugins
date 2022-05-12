<?php

class Test_CDN_Assets extends WP_UnitTestCase {

	/**
	 * @dataProvider dataprovider_staging
	 */
	public function test_staging_urls( $url, $expected ) {
		putenv( 'WP_ENVIRONMENT_TYPE=staging' );

		$this->assertSame( 'staging', wp_get_environment_type() );

		$this->assertSame(
			apply_filters( 'style_loader_src', $url, 'style-handle-here' ),
			$expected
		);
	}

	/**
	 * @dataProvider dataprovider_production
	 */
	public function test_production_urls( $url, $expected ) {
		putenv( 'WP_ENVIRONMENT_TYPE=production' );

		$this->assertSame( 'production', wp_get_environment_type() );

		$this->assertSame(
			apply_filters( 'style_loader_src', $url, 'style-handle-here' ),
			$expected
		);
	}

	public function dataprovider_staging() {
		$dashicons_time = filemtime( ABSPATH . WPINC . '/css/dashicons.css' );

		return [
			// WordPress files
			[
				'https://wordpress.org/wp-includes/css/dashicons.css?ver=1',
				'https://wordpress.org/wp-includes/css/dashicons.css?ver=' . $dashicons_time
			],
			// WordPress files in subdirectories
			[
				'https://wordpress.org/multisite-sub-directory/wp-includes/css/dashicons.css?ver=1',
				'https://wordpress.org/wp-includes/css/dashicons.css?ver=' . $dashicons_time
			],
			// WordPress files on subdomains + subdirs
			[
				'https://make.wordpress.org/multisite-sub-directory/wp-includes/css/dashicons.css?ver=1',
				'https://make.wordpress.org/wp-includes/css/dashicons.css?ver=' . $dashicons_time
			],

			// Not WordPress.org should remain the same.
			[
				'https://example.org/example.css',
				'https://example.org/example.css'
			],
			[
				'https://example.org/example.css?ver=1',
				'https://example.org/example.css?ver=1'
			],

			// Profiles.wordpress.org should remain untouched.
			[
				'https://profiles.wordpress.org/wp-content/themes/profiles.wordpress.org/style.css?ver=1',
				'https://profiles.wordpress.org/wp-content/themes/profiles.wordpress.org/style.css?ver=1'
			],
		];
	}

	public function dataprovider_production() {
		// Production clamps modification time to a 2 minute window.
		$window         = 2 * MINUTE_IN_SECONDS;
		$dashicons_time = filemtime( ABSPATH . WPINC . '/css/dashicons.css' );
		$dashicons_time = floor( $dashicons_time / $window ) * $window;

		return [
			// WordPress files
			[
				'https://wordpress.org/wp-includes/css/dashicons.css?ver=1',
				'https://s.w.org/wp-includes/css/dashicons.css?ver=' . $dashicons_time
			],
			// WordPress files in subdirectories
			[
				'https://wordpress.org/multisite-sub-directory/wp-includes/css/dashicons.css?ver=1',
				'https://s.w.org/wp-includes/css/dashicons.css?ver=' . $dashicons_time
			],
			// WordPress files on subdomains + subdirs
			[
				'https://make.wordpress.org/multisite-sub-directory/wp-includes/css/dashicons.css?ver=1',
				'https://s.w.org/wp-includes/css/dashicons.css?ver=' . $dashicons_time
			],

			// Not WordPress.org should remain the same.
			[
				'https://example.org/example.css',
				'https://example.org/example.css'
			],
			[
				'https://example.org/example.css?ver=1',
				'https://example.org/example.css?ver=1'
			],

			// Profiles.wordpress.org should remain untouched.
			[
				'https://profiles.wordpress.org/wp-content/themes/profiles.wordpress.org/style.css?ver=1',
				'https://profiles.wordpress.org/wp-content/themes/profiles.wordpress.org/style.css?ver=1'
			],
		];
	}

}
