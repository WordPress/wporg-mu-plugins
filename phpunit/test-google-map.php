<?php
/**
 * Tests for the Google Map block's rendered output.
 *
 * @package wporg
 */

declare( strict_types = 1 );

/**
 * Renders the Google Map block through `do_blocks()` and checks the state it hands to the browser.
 */
class Test_Google_Map extends WP_UnitTestCase {

	/**
	 * Render a Google Map block and return the inline script it attaches to the view script.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string The inline script printed before the block's view script.
	 */
	private function render_map( array $attributes ): string {
		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'wporg/google-map' );
		$handle     = $block_type->view_script_handles[0];

		wp_scripts()->add_data( $handle, 'before', array() );

		do_blocks( '<!-- wp:wporg/google-map ' . wp_json_encode( $attributes ) . ' /-->' );

		return implode( '', array_filter( (array) wp_scripts()->get_data( $handle, 'before' ) ) );
	}

	/**
	 * A form action that cannot leave this site reaches the browser unchanged.
	 *
	 * @dataProvider data_allowed_search_form_actions
	 *
	 * @param string $action The form action to keep.
	 */
	public function test_keeps_allowed_search_form_actions( string $action ): void {
		$script = $this->render_map(
			array(
				'id'               => 'safe',
				'searchFormAction' => $action,
			)
		);

		$this->assertStringContainsString( '"searchFormAction":' . wp_json_encode( $action ), $script, $action );
	}

	/**
	 * Data provider for test_keeps_allowed_search_form_actions.
	 *
	 * @return array[]
	 */
	public function data_allowed_search_form_actions(): array {
		return array(
			'this site' => array( home_url( '/events/' ) ),
			'relative'  => array( '/events/' ),
		);
	}

	/**
	 * A `javascript:` form action never reaches the browser, because the block's search form
	 * assigns it to a form action that submitting would execute.
	 */
	public function test_drops_a_javascript_search_form_action(): void {
		$script = $this->render_map(
			array(
				'id'               => 'attack',
				'searchFormAction' => "javascript:top.alert('XSS');void(0)",
			)
		);

		$this->assertStringContainsString( '"searchFormAction":""', $script );
		$this->assertStringNotContainsString( 'top.alert', $script );
	}

	/**
	 * Active schemes and hosts off this site are dropped.
	 *
	 * @dataProvider data_unsafe_search_form_actions
	 *
	 * @param string $action The form action to reject.
	 */
	public function test_drops_unsafe_search_form_actions( string $action ): void {
		$script = $this->render_map(
			array(
				'id'               => 'attack',
				'searchFormAction' => $action,
			)
		);

		$this->assertStringContainsString( '"searchFormAction":""', $script, $action );
		$this->assertStringNotContainsString( 'evil.example', $script, $action );
	}

	/**
	 * Data provider for test_drops_unsafe_search_form_actions.
	 *
	 * @return array[]
	 */
	public function data_unsafe_search_form_actions(): array {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		return array(
			'data'                     => array( 'data:text/html,<script>alert(1)</script>' ),
			'vbscript'                 => array( 'vbscript:alert(1)' ),
			'mixed case javascript'    => array( 'JaVaScRiPt:alert(1)' ),
			'off-site https'           => array( 'https://evil.example/collect' ),
			'protocol relative'        => array( '//evil.example/collect' ),
			'escaped protocol slashes' => array( '\/\/evil.example/collect' ),
			// A lookalike domain is its own host, not this one with something appended.
			'suffix lookalike'         => array( 'https://' . $host . '.evil.example/collect' ),
			// Userinfo before the `@` is not the host, however much it looks like one.
			'userinfo'                 => array( 'https://' . $host . '@evil.example/collect' ),
			// parse_url() gives up on these, but browsers resolve them to a host.
			'extra slash'              => array( 'https:///evil.example/collect' ),
			'many extra slashes'       => array( 'https:////evil.example/collect' ),
			'scheme without authority' => array( 'https:/evil.example/collect' ),
		);
	}

	/**
	 * An action on another host falls back to live search, even a harmless one.
	 *
	 * `wp_validate_redirect()` matches the host exactly, so neither a sibling site on the
	 * network nor a differently cased spelling of this host is kept. Both are safe places to
	 * submit to; the block just searches the current page instead of going there.
	 *
	 * @dataProvider data_search_form_actions_off_this_host
	 *
	 * @param string $action The form action to discard.
	 */
	public function test_drops_search_form_actions_off_this_host( string $action ): void {
		$script = $this->render_map(
			array(
				'id'               => 'other-host',
				'searchFormAction' => $action,
			)
		);

		$this->assertStringContainsString( '"searchFormAction":""', $script, $action );
	}

	/**
	 * Data provider for test_drops_search_form_actions_off_this_host.
	 *
	 * @return array[]
	 */
	public function data_search_form_actions_off_this_host(): array {
		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );

		return array(
			'subdomain'       => array( 'https://sub.' . $host . '/events/' ),
			'mixed case host' => array( 'https://' . strtoupper( $host ) . '/events/' ),
		);
	}
}
