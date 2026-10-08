<?php

use function WordPressdotorg\MU_Plugins\Google_Map\{ clean_facets, get_limit, is_cacheable };

/**
 * @group google-map
 */
class Test_Google_Map_Event_Filters extends WP_UnitTestCase {
	/**
	 * Facets that shape the results, rather than filter them, shouldn't stop a page being cached.
	 *
	 * @dataProvider data_is_cacheable
	 */
	public function test_is_cacheable( array $facets, bool $expected ) {
		$this->assertSame( $expected, is_cacheable( clean_facets( $facets ), 1 ) );
	}

	/**
	 * Data provider for test_is_cacheable().
	 */
	public function data_is_cacheable() {
		return array(
			'no facets'                    => array( array(), true ),
			'one facet'                    => array( array( 'country' => array( 'AU' ) ), true ),
			'one facet with a limit'       => array(
				array(
					'country' => array( 'AU' ),
					'limit'   => 500,
				),
				true,
			),
			'one facet with a description' => array(
				array(
					'country'             => array( 'AU' ),
					'include_description' => true,
				),
				true,
			),
			'a limit and a description'    => array(
				array(
					'limit'               => 50,
					'include_description' => true,
				),
				true,
			),
			'two facets'                   => array(
				array(
					'country' => array( 'AU' ),
					'type'    => array( 'meetup' ),
				),
				false,
			),
			'two facets with a limit'      => array(
				array(
					'country' => array( 'AU' ),
					'type'    => array( 'meetup' ),
					'limit'   => 50,
				),
				false,
			),
			'two values in one facet'      => array(
				array(
					'country' => array( 'AU', 'NZ' ),
					'limit'   => 50,
				),
				false,
			),
			'a search'                     => array(
				array(
					'search' => 'wordcamp',
					'limit'  => 50,
				),
				false,
			),
		);
	}

	/**
	 * The limit can be lowered from the default, but never raised above it or below one.
	 *
	 * @dataProvider data_get_limit
	 */
	public function test_get_limit( array $facets, int $expected ) {
		$this->assertSame( $expected, get_limit( clean_facets( $facets ), 500 ) );
	}

	/**
	 * Data provider for test_get_limit().
	 */
	public function data_get_limit() {
		return array(
			'no limit'       => array( array(), 500 ),
			'a lower limit'  => array( array( 'limit' => 50 ), 50 ),
			'a string limit' => array( array( 'limit' => array( '25' ) ), 25 ),
			'a higher limit' => array( array( 'limit' => 100000 ), 500 ),
			'a negative one' => array( array( 'limit' => -1 ), 1 ),
			'not a number'   => array( array( 'limit' => array( 'all' ) ), 1 ),
		);
	}
}
