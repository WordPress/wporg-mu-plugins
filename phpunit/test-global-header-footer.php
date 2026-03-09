<?php

use function WordPressdotorg\MU_Plugins\Global_Header_Footer\remove_head_alternate_links;

class Test_Global_Header_Footer extends WP_UnitTestCase {

	/**
	 * Test that RSS feed link tags are removed.
	 */
	public function test_removes_rss_feed_link() {
		$input = '<link rel="alternate" type="application/rss+xml" title="Feed" href="https://example.com/feed/" />';
		$this->assertEmpty( trim( remove_head_alternate_links( $input ) ) );
	}

	/**
	 * Test that Atom feed link tags are removed.
	 */
	public function test_removes_atom_feed_link() {
		$input = '<link rel="alternate" type="application/atom+xml" title="Atom" href="https://example.com/feed/atom/" />';
		$this->assertEmpty( trim( remove_head_alternate_links( $input ) ) );
	}

	/**
	 * Test that oEmbed discovery link tags are removed.
	 */
	public function test_removes_oembed_link() {
		$input = '<link rel="alternate" type="application/json+oembed" href="https://example.com/wp-json/oembed/1.0/embed?url=test" />';
		$this->assertEmpty( trim( remove_head_alternate_links( $input ) ) );
	}

	/**
	 * Test that hreflang alternate link tags are removed.
	 */
	public function test_removes_hreflang_link() {
		$input = '<link rel="alternate" hreflang="es" href="https://es.wordpress.org/" />';
		$this->assertEmpty( trim( remove_head_alternate_links( $input ) ) );
	}

	/**
	 * Test that alternate links with single-quoted attributes are removed.
	 */
	public function test_removes_single_quoted_alternate() {
		$input = "<link rel='alternate' type='application/rss+xml' href='https://example.com/feed/' />";
		$this->assertEmpty( trim( remove_head_alternate_links( $input ) ) );
	}

	/**
	 * Test that alternate links without self-closing slash are removed.
	 */
	public function test_removes_non_self_closing() {
		$input = '<link rel="alternate" type="application/rss+xml" href="https://example.com/feed/">';
		$this->assertEmpty( trim( remove_head_alternate_links( $input ) ) );
	}

	/**
	 * Test that compound rel values containing "alternate" are removed.
	 */
	public function test_removes_compound_rel_with_alternate() {
		$input = '<link rel="alternate stylesheet" title="Dark" href="dark.css" />';
		$this->assertEmpty( trim( remove_head_alternate_links( $input ) ) );
	}

	/**
	 * Test that stylesheet links are preserved.
	 */
	public function test_preserves_stylesheet_link() {
		$input = '<link rel="stylesheet" href="style.css">';
		$this->assertSame( $input, remove_head_alternate_links( $input ) );
	}

	/**
	 * Test that canonical links are preserved.
	 */
	public function test_preserves_canonical_link() {
		$input = '<link rel="canonical" href="https://example.com/page">';
		$this->assertSame( $input, remove_head_alternate_links( $input ) );
	}

	/**
	 * Test that preconnect links are preserved.
	 */
	public function test_preserves_preconnect_link() {
		$input = '<link rel="preconnect" href="https://fonts.googleapis.com">';
		$this->assertSame( $input, remove_head_alternate_links( $input ) );
	}

	/**
	 * Test that icon links are preserved.
	 */
	public function test_preserves_icon_link() {
		$input = '<link rel="icon" href="/favicon.ico">';
		$this->assertSame( $input, remove_head_alternate_links( $input ) );
	}

	/**
	 * Test that mixed markup keeps non-alternate links and removes alternate ones.
	 */
	public function test_mixed_markup() {
		$input = '<head>
<link rel="alternate" type="application/rss+xml" title="Feed" href="/feed/" />
<link rel="stylesheet" href="style.css">
<link rel="alternate" type="application/json+oembed" href="/oembed" />
<link rel="preconnect" href="https://fonts.googleapis.com">
</head>';

		$result = remove_head_alternate_links( $input );

		$this->assertStringContainsString( 'stylesheet', $result );
		$this->assertStringContainsString( 'preconnect', $result );
		$this->assertStringNotContainsString( 'alternate', $result );
	}

	/**
	 * Test that empty string input returns empty string.
	 */
	public function test_empty_input() {
		$this->assertSame( '', remove_head_alternate_links( '' ) );
	}

	/**
	 * Test that markup with no link tags is returned unchanged.
	 */
	public function test_no_link_tags() {
		$input = '<div class="header">Hello</div>';
		$this->assertSame( $input, remove_head_alternate_links( $input ) );
	}
}
