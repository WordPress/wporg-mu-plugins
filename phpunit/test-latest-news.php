<?php
/**
 * Tests for the Latest News block's post query.
 *
 * @package wporg
 */

declare( strict_types = 1 );

/**
 * Renders the Latest News block and checks which posts it lists.
 */
class Test_Latest_News extends WP_UnitTestCase {

	/**
	 * Register a second post type, like the podcast type on wordpress.org/news.
	 */
	public function set_up(): void {
		parent::set_up();
		register_post_type( 'podcast', array( 'public' => true ) );
	}

	/**
	 * Remove the post type registered for the test.
	 */
	public function tear_down(): void {
		unregister_post_type( 'podcast' );
		parent::tear_down();
	}

	/**
	 * Render the block with the given attributes.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string The rendered block.
	 */
	private function render( array $attributes ): string {
		$block = new WP_Block(
			array(
				'blockName' => 'wporg/latest-news',
				'attrs'     => $attributes,
			)
		);

		return $block->render();
	}

	/**
	 * By default the block lists only posts.
	 */
	public function test_lists_only_posts_by_default(): void {
		self::factory()->post->create( array( 'post_title' => 'A blog post' ) );
		self::factory()->post->create(
			array(
				'post_title' => 'A podcast episode',
				'post_type'  => 'podcast',
			)
		);

		$output = $this->render( array() );

		$this->assertStringContainsString( 'A blog post', $output );
		$this->assertStringNotContainsString( 'A podcast episode', $output );
	}

	/**
	 * The postTypes attribute adds other post types to the list.
	 */
	public function test_lists_the_given_post_types(): void {
		self::factory()->post->create( array( 'post_title' => 'A blog post' ) );
		self::factory()->post->create(
			array(
				'post_title' => 'A podcast episode',
				'post_type'  => 'podcast',
			)
		);

		// Render the default first, so a shared cache would leak into the second render.
		$this->render( array() );
		$output = $this->render( array( 'postTypes' => array( 'post', 'podcast' ) ) );

		$this->assertStringContainsString( 'A blog post', $output );
		$this->assertStringContainsString( 'A podcast episode', $output );
	}

	/**
	 * A post type not registered in this request, as on another site, links by ID.
	 */
	public function test_links_unregistered_post_types_by_id(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_title' => 'A podcast episode',
				'post_type'  => 'podcast',
			)
		);
		unregister_post_type( 'podcast' );

		$output = $this->render( array( 'postTypes' => array( 'podcast' ) ) );

		$this->assertStringContainsString( 'href="' . esc_url( home_url( '?p=' . $post_id ) ) . '"', $output );
	}
}
