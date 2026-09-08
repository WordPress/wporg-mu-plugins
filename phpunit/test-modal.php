<?php
/**
 * Tests for the Modal block's rendered output.
 *
 * @package wporg
 */

/**
 * Renders the modal block through `do_blocks()` and checks the markup it produces.
 */
class Test_Modal extends WP_UnitTestCase {

	/**
	 * Register the shortcodes the tests put in block attributes.
	 */
	public function set_up() {
		parent::set_up();

		add_shortcode( 'modal_test_version', fn() => '7.1' );
		add_shortcode( 'modal_test_link', fn() => 'https://wordpress.org/latest.zip' );
		add_shortcode( 'modal_test_emits_shortcode', fn() => 'Get [modal_test_version]' );
	}

	/**
	 * Remove the test shortcodes.
	 */
	public function tear_down() {
		remove_shortcode( 'modal_test_version' );
		remove_shortcode( 'modal_test_link' );
		remove_shortcode( 'modal_test_emits_shortcode' );

		parent::tear_down();
	}

	/**
	 * Render a modal block with the given attributes.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string The rendered block.
	 */
	private function render_modal( array $attributes ): string {
		return do_blocks(
			'<!-- wp:wporg/modal ' . wp_json_encode( $attributes ) . ' -->' .
			'<!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph -->' .
			'<!-- /wp:wporg/modal -->'
		);
	}

	/**
	 * A shortcode in the label expands in the button text and the dialog's aria-label.
	 */
	public function test_expands_shortcodes_in_label_and_href() {
		$output = $this->render_modal(
			array(
				'href'  => '[modal_test_link]',
				'label' => 'Download WordPress [modal_test_version]',
			)
		);

		$this->assertStringContainsString( '>Download WordPress 7.1</a>', $output );
		$this->assertStringContainsString( 'aria-label="Download WordPress 7.1"', $output );
		$this->assertStringContainsString( 'href="https://wordpress.org/latest.zip"', $output );
		$this->assertStringNotContainsString( '[modal_test_', $output );
	}

	/**
	 * Without an href the label renders in a button, expanded the same way.
	 */
	public function test_expands_shortcodes_in_button_label() {
		$output = $this->render_modal( array( 'label' => 'Version [modal_test_version]' ) );

		$this->assertStringContainsString( '>Version 7.1</button>', $output );
		$this->assertStringNotContainsString( '<a', $output );
	}

	/**
	 * Markup in the label is still filtered after the shortcode pass.
	 */
	public function test_label_markup_is_filtered_after_expansion() {
		$output = $this->render_modal(
			array( 'label' => 'Open <script>alert(1)</script><strong>[modal_test_version]</strong>' )
		);

		$this->assertStringContainsString( '<strong>7.1</strong>', $output );
		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( 'aria-label="Open 7.1"', $output );
	}

	/**
	 * A shortcode the expansion emits is dropped rather than left for a later pass.
	 */
	public function test_shortcode_emitted_by_expansion_is_stripped() {
		$output = $this->render_modal( array( 'label' => '[modal_test_emits_shortcode]' ) );

		$this->assertStringContainsString( '>Get </button>', $output );
		$this->assertStringNotContainsString( '[modal_test_version]', $output );
	}
}
