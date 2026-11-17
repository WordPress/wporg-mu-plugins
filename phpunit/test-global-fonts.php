<?php
/**
 * Tests for the Global Fonts stylesheet registration.
 *
 * @package wporg
 */

declare( strict_types = 1 );

use function WordPressdotorg\MU_Plugins\Global_Fonts\{get_font_url, maybe_preload_font, preload_font, register_style, relative_to_absolute_urls};

/**
 * Checks that the Noto Serif stylesheets only load for the locales that use them.
 */
class Test_Global_Fonts extends WP_UnitTestCase {

	/**
	 * The locale to report from `get_locale()`.
	 *
	 * @var string
	 */
	private $locale = 'en_US';

	/**
	 * Report the test's locale from `get_locale()`.
	 */
	public function set_up(): void {
		parent::set_up();
		add_filter( 'locale', array( $this, 'filter_locale' ) );
	}

	/**
	 * Restore the locale and the original registration.
	 */
	public function tear_down(): void {
		remove_filter( 'locale', array( $this, 'filter_locale' ) );
		$this->register_for_locale( 'en_US' );
		parent::tear_down();
	}

	/**
	 * Filter callback for `locale`.
	 *
	 * @return string
	 */
	public function filter_locale(): string {
		return $this->locale;
	}

	/**
	 * Register `wporg-global-fonts` again as if the site used the given locale.
	 *
	 * @param string $locale The site locale.
	 */
	private function register_for_locale( string $locale ): void {
		$this->locale = $locale;
		wp_deregister_style( 'wporg-global-fonts' );
		register_style();
	}

	/**
	 * The main stylesheet no longer imports the CJK stylesheets.
	 */
	public function test_main_stylesheet_has_no_imports(): void {
		$css = file_get_contents( WPMU_PLUGIN_DIR . '/global-fonts/style.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.

		$this->assertStringNotContainsString( '@import', $css );
	}

	/**
	 * Each Noto Serif stylesheet is registered as its own handle.
	 */
	public function test_registers_noto_serif_handles(): void {
		foreach ( array( 'JP', 'KR', 'SC' ) as $region ) {
			$style = wp_styles()->query( 'wporg-global-fonts-noto-serif-' . strtolower( $region ) );

			$this->assertInstanceOf( _WP_Dependency::class, $style );
			$this->assertStringEndsWith( "/global-fonts/NotoSerif/NotoSerif$region/style.css", $style->src );
		}
	}

	/**
	 * Only the CJK locales make `wporg-global-fonts` depend on their Noto Serif stylesheet.
	 *
	 * @dataProvider data_locale_dependencies
	 *
	 * @param string   $locale   The site locale.
	 * @param string[] $expected The expected dependencies of `wporg-global-fonts`.
	 */
	public function test_locale_dependencies( string $locale, array $expected ): void {
		$this->register_for_locale( $locale );

		$this->assertSame( $expected, wp_styles()->query( 'wporg-global-fonts' )->deps );
	}

	/**
	 * Data provider for test_locale_dependencies().
	 *
	 * @return array[]
	 */
	public function data_locale_dependencies(): array {
		return array(
			'English'             => array( 'en_US', array() ),
			'Central Kurdish'     => array( 'ckb', array() ),
			'Traditional Chinese' => array( 'zh_TW', array() ),
			'Japanese'            => array( 'ja', array( 'wporg-global-fonts-noto-serif-jp' ) ),
			'Korean'              => array( 'ko_KR', array( 'wporg-global-fonts-noto-serif-kr' ) ),
			'Simplified Chinese'  => array( 'zh_CN', array( 'wporg-global-fonts-noto-serif-sc' ) ),
		);
	}

	/**
	 * Get the editor settings with the font stylesheet as WordPress fetches it for `add_editor_style()`.
	 *
	 * @return array
	 */
	private function get_editor_settings(): array {
		return array(
			'styles' => array(
				array(
					'css'            => file_get_contents( WPMU_PLUGIN_DIR . '/global-fonts/style.css' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
					'__unstableType' => 'theme',
					'isGlobalStyles' => false,
				),
				array(
					'css'            => 'body { color: red; }',
					'__unstableType' => 'theme',
					'isGlobalStyles' => false,
				),
			),
		);
	}

	/**
	 * The editor copy of the font stylesheet imports the locale's Noto Serif stylesheet by absolute URL.
	 */
	public function test_editor_styles_import_locale_stylesheet(): void {
		$this->register_for_locale( 'ja' );

		$settings = relative_to_absolute_urls( $this->get_editor_settings() );
		$css      = $settings['styles'][0]['css'];

		$this->assertMatchesRegularExpression( '#^@import url\("https?://[^"]+/global-fonts/NotoSerif/NotoSerifJP/style\.css\?ver=\d+"\);#', $css );
		$this->assertSame( 1, substr_count( $css, '@import' ) );
		$this->assertSame( 'body { color: red; }', $settings['styles'][1]['css'] );
	}

	/**
	 * Other locales get no import in the editor.
	 */
	public function test_editor_styles_skip_import_for_other_locales(): void {
		$this->register_for_locale( 'en_US' );

		$settings = relative_to_absolute_urls( $this->get_editor_settings() );

		$this->assertStringNotContainsString( '@import', $settings['styles'][0]['css'] );
		$this->assertStringNotContainsString( 'url(./', $settings['styles'][0]['css'] );
	}

	/**
	 * Preloading a Noto Serif `cjk` subset still resolves to a font file.
	 */
	public function test_noto_serif_cjk_preload_still_resolves(): void {
		$this->assertStringEndsWith( '/global-fonts/NotoSerif/NotoSerifJP-cjk.woff2', (string) get_font_url( 'Noto Serif JP', 'cjk' ) );
		$this->assertTrue( preload_font( 'Noto Serif JP', 'cjk' ) );

		$hrefs = wp_list_pluck( maybe_preload_font( array() ), 'href' );

		$this->assertCount( 1, $hrefs );
		$this->assertStringEndsWith( '/global-fonts/NotoSerif/NotoSerifJP-cjk.woff2', $hrefs[0] );
	}
}
