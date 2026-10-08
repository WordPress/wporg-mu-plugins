<?php
/**
 * Tests for the `wporg_export` REST context.
 *
 * @package WordPressdotorg\MU_Plugins
 */

declare( strict_types = 1 );

use WordPressdotorg\MU_Plugins\REST_API\Export_Context;

/**
 * Verify that the `content_raw` export field never discloses the body of a
 * password-protected post, while still exporting ordinary public content.
 */
class Test_WPORG_Export_Context extends WP_UnitTestCase {

	/**
	 * The export context instance under test.
	 *
	 * @var Export_Context
	 */
	protected ?Export_Context $context = null;

	/**
	 * Opt the built-in `post` type into the export context and register its
	 * REST field so the tests can exercise the real route and callback.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		add_filter( 'wporg_export_context_post_types', array( $this, 'add_post_to_export' ) );

		/*
		 * Boot the REST API so the plugin's own `initialize_rest_contexts()` loads the
		 * Export_Context class and registers the `content_raw` field for `post`. The class
		 * lives outside the autoloader's path, so it is only available once that runs.
		 */
		do_action( 'rest_api_init' );

		$this->context = new Export_Context();
	}

	/**
	 * Filter callback: add the `post` type to the export context.
	 *
	 * @param array $post_types Post types opted into the export context.
	 * @return array Filtered post types.
	 */
	public function add_post_to_export( array $post_types ): array {
		$post_types[] = 'post';

		return $post_types;
	}

	/**
	 * The raw content field must return the body for an ordinary public post.
	 *
	 * @return void
	 */
	public function test_public_post_content_is_exported(): void {
		$content = '<!-- wp:paragraph --><p>PUBLIC-BODY</p><!-- /wp:paragraph -->';
		$post_id = self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => $content,
			)
		);

		$raw = $this->context->show_post_content_raw( array( 'id' => $post_id ), 'content_raw', null );

		$this->assertSame( get_post( $post_id )->post_content, $raw );
		$this->assertStringContainsString( 'PUBLIC-BODY', $raw );
	}

	/**
	 * The raw content field must never return the body of a password-protected post.
	 *
	 * @return void
	 */
	public function test_password_protected_post_content_is_withheld(): void {
		$secret  = '<!-- wp:paragraph --><p>CONFIDENTIAL-MARKER</p><!-- /wp:paragraph -->';
		$post_id = self::factory()->post->create(
			array(
				'post_status'   => 'publish',
				'post_password' => 'correct-horse',
				'post_content'  => $secret,
			)
		);

		$raw = $this->context->show_post_content_raw( array( 'id' => $post_id ), 'content_raw', null );

		$this->assertStringNotContainsString( 'CONFIDENTIAL-MARKER', $raw );
		$this->assertSame( '', $raw );
	}

	/**
	 * An anonymous REST item request in the export context must not leak the
	 * protected body, while still returning it empty rather than erroring.
	 *
	 * @return void
	 */
	public function test_rest_item_export_withholds_protected_body(): void {
		wp_set_current_user( 0 );

		$post_id = self::factory()->post->create(
			array(
				'post_status'   => 'publish',
				'post_password' => 'correct-horse',
				'post_content'  => '<!-- wp:paragraph --><p>CONFIDENTIAL-MARKER</p><!-- /wp:paragraph -->',
			)
		);

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id );
		$request->set_param( 'context', 'wporg_export' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $post_id, $data['id'] );
		$this->assertArrayHasKey( 'content_raw', $data );
		$this->assertSame( '', $data['content_raw'] );
	}

	/**
	 * An anonymous REST collection request in the export context must not leak
	 * a protected body alongside public items.
	 *
	 * @return void
	 */
	public function test_rest_collection_export_withholds_protected_body(): void {
		wp_set_current_user( 0 );

		self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:paragraph --><p>PUBLIC-BODY</p><!-- /wp:paragraph -->',
			)
		);
		$protected_id = self::factory()->post->create(
			array(
				'post_status'   => 'publish',
				'post_password' => 'correct-horse',
				'post_content'  => '<!-- wp:paragraph --><p>CONFIDENTIAL-MARKER</p><!-- /wp:paragraph -->',
			)
		);

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'context', 'wporg_export' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();
		$body     = wp_json_encode( $data );

		$this->assertSame( 200, $response->get_status() );
		// The protected post must actually be in the collection, otherwise withholding is never exercised.
		$this->assertContains( $protected_id, wp_list_pluck( $data, 'id' ) );
		$this->assertStringContainsString( 'PUBLIC-BODY', $body );
		$this->assertStringNotContainsString( 'CONFIDENTIAL-MARKER', $body );
	}
}
