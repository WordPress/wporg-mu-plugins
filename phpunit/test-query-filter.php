<?php
/**
 * Query filter selection rendering tests.
 *
 * @package WordPressdotorg\MU_Plugins
 */

declare( strict_types = 1 );

/**
 * Cover numeric option keys supplied by event filters.
 */
class Test_Query_Filter extends WP_UnitTestCase {
	/**
	 * Padded month keys match both padded and unpadded URL values.
	 *
	 * @return void
	 */
	public function test_numeric_month_selections_remain_checked(): void {
		foreach ( array( false, true ) as $multiple ) {
			foreach ( array( '05', '5', 5 ) as $selection ) {
				$filter_callback = static function () use ( $selection ): array {
					return array(
						'key'      => 'month',
						'label'    => 'Month',
						'title'    => 'Month',
						'action'   => '/',
						'options'  => array(
							'05' => 'May',
							'06' => 'June',
						),
						'selected' => array( $selection ),
					);
				};
				add_filter( 'wporg_query_filter_options_month', $filter_callback );
				ob_start();
				try {
					$attributes = array(
						'key'      => 'month',
						'multiple' => $multiple,
					);
					$block      = new WP_Block(
						array(
							'blockName' => 'wporg/query-filter',
							'attrs'     => $attributes,
						)
					);
					require WPMU_PLUGIN_DIR . '/blocks/query-filter/render.php';
					$html = ob_get_contents();
				} finally {
					ob_end_clean();
					remove_filter( 'wporg_query_filter_options_month', $filter_callback );
				}
				$this->assertMatchesRegularExpression( '/<input\s[^>]*value="05"[^>]*checked=/s', $html );
				$this->assertDoesNotMatchRegularExpression( '/<input\s[^>]*value="06"[^>]*checked=/s', $html );
			}
		}
	}
}
