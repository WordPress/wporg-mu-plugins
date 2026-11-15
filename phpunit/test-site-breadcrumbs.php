<?php
/**
 * Tests for the Site Breadcrumbs block's rendered output.
 *
 * @package wporg
 */

/**
 * Renders the breadcrumbs block through `do_blocks()` and checks its list semantics.
 */
class Test_Site_Breadcrumbs extends WP_UnitTestCase {

	/**
	 * Breadcrumbs the tests render, through the block's filter.
	 *
	 * @return array
	 */
	public static function breadcrumbs() {
		return array(
			array(
				'url'   => 'https://example.org/',
				'title' => 'Home',
			),
			array(
				'url'   => 'https://example.org/courses/',
				'title' => 'Courses',
			),
			array(
				'url'   => false,
				'title' => 'Current Lesson',
			),
		);
	}

	/**
	 * Use the test breadcrumbs.
	 */
	public function set_up() {
		parent::set_up();

		add_filter( 'wporg_block_site_breadcrumbs', array( __CLASS__, 'breadcrumbs' ) );
	}

	/**
	 * Remove the test breadcrumbs.
	 */
	public function tear_down() {
		remove_filter( 'wporg_block_site_breadcrumbs', array( __CLASS__, 'breadcrumbs' ) );

		parent::tear_down();
	}

	/**
	 * Each crumb is a list item in a list, so screen readers can step through them one at a time.
	 */
	public function test_renders_crumbs_as_list_items() {
		$output = do_blocks( '<!-- wp:wporg/site-breadcrumbs /-->' );

		$processor = new WP_HTML_Tag_Processor( $output );
		$this->assertTrue( $processor->next_tag( array( 'class_name' => 'wporg-site-breadcrumbs__wrapper' ) ) );
		$this->assertSame( 'list', $processor->get_attribute( 'role' ) );

		$items   = array();
		$current = array();
		while ( $processor->next_tag( 'span' ) ) {
			$items[]   = $processor->get_attribute( 'role' );
			$current[] = $processor->get_attribute( 'aria-current' );
		}

		$this->assertSame( array( 'listitem', 'listitem', 'listitem' ), $items );
		$this->assertSame( array( null, null, 'page' ), $current );
	}
}
