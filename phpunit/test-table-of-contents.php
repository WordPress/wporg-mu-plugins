<?php
/**
 * Tests for the Table of Contents block's rendered list.
 *
 * @package wporg
 */

declare( strict_types = 1 );

/**
 * Renders the table of contents block for a post and checks the list it builds from the headings.
 */
class Test_Table_Of_Contents extends WP_UnitTestCase {

	/**
	 * Render the block for a post with the given content and return its list markup.
	 *
	 * @param string $post_content The post content to build the table of contents from.
	 *
	 * @return string The `<ul>` markup, with whitespace between tags removed.
	 */
	private function render_list( string $post_content ): string {
		$post_id = self::factory()->post->create( array( 'post_content' => $post_content ) );
		$block   = new WP_Block( array( 'blockName' => 'wporg/table-of-contents' ), array( 'postId' => $post_id ) );
		$output  = $block->render();

		preg_match( '#<ul class="wporg-table-of-contents__list">.*</ul>#s', $output, $matches );

		return preg_replace( '/>\s+</', '><', $matches[0] ?? '' );
	}

	/**
	 * Headings out of order still produce balanced lists that keep every item in the root list.
	 *
	 * @dataProvider data_heading_nesting
	 *
	 * @param string $post_content The post content.
	 * @param string $expected     The expected list items inside the root list.
	 */
	public function test_nests_headings_into_balanced_lists( string $post_content, string $expected ): void {
		$this->assertSame(
			'<ul class="wporg-table-of-contents__list">' . $expected . '</ul>',
			$this->render_list( $post_content )
		);
	}

	/**
	 * Data provider for test_nests_headings_into_balanced_lists().
	 *
	 * @return array[]
	 */
	public function data_heading_nesting(): array {
		return array(
			'first heading deeper than the rest'          => array(
				'<h3>One</h3><h2>Two</h2><h2>Three</h2>',
				'<li><a href="#one">One</a></li><li><a href="#two">Two</a></li><li><a href="#three">Three</a></li>',
			),
			'subheading between top-level headings'       => array(
				'<h2>One</h2><h3>Two</h3><h2>Three</h2>',
				'<li><a href="#one">One</a><ul><li><a href="#two">Two</a></li></ul></li><li><a href="#three">Three</a></li>',
			),
			'deeper first heading, then a nested section' => array(
				'<h3>One</h3><h2>Two</h2><h3>Three</h3><h2>Four</h2>',
				'<li><a href="#one">One</a></li><li><a href="#two">Two</a><ul><li><a href="#three">Three</a></li></ul></li><li><a href="#four">Four</a></li>',
			),
			'ends on a subheading'                        => array(
				'<h2>One</h2><h3>Two</h3>',
				'<li><a href="#one">One</a><ul><li><a href="#two">Two</a></li></ul></li>',
			),
		);
	}

	/**
	 * A post preview lists the autosaved headings, which core sets on the global post only.
	 */
	public function test_lists_headings_from_previewed_content(): void {
		$post_id = self::factory()->post->create( array( 'post_content' => '<h2>Saved one</h2><h2>Saved two</h2>' ) );

		_wp_put_post_revision(
			array_merge(
				get_post( $post_id, ARRAY_A ),
				array( 'post_content' => '<h2>Draft one</h2><h2>Draft two</h2>' )
			),
			true
		);

		// What core's `the_preview` filter does to the main query's post.
		$GLOBALS['post'] = _set_preview( get_post( $post_id ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$block = new WP_Block( array( 'blockName' => 'wporg/table-of-contents' ), array( 'postId' => $post_id ) );
		preg_match( '#<ul class="wporg-table-of-contents__list">.*</ul>#s', $block->render(), $matches );

		$this->assertSame(
			'<ul class="wporg-table-of-contents__list"><li><a href="#draft-one">Draft one</a></li><li><a href="#draft-two">Draft two</a></li></ul>',
			preg_replace( '/>\s+</', '><', $matches[0] ?? '' )
		);
	}
}
