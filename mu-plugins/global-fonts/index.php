<?php
/**
 * Plugin Name: WordPress.org Fonts
 * Description: Registers a stylesheet to load Inter and EB Garamond for use in other themes/plugins.
 */

namespace WordPressdotorg\MU_Plugins\Global_Fonts;

// Include helper functions that exist in global-scope.
include __DIR__ . '/helper-functions.php';

add_filter( 'init', __NAMESPACE__ . '\register_style', 1 );
add_filter( 'block_editor_settings_all', __NAMESPACE__ . '\relative_to_absolute_urls' );
add_filter( 'wp_preload_resources', __NAMESPACE__ . '\maybe_preload_font' );

/**
 * Register stylesheet with font-family declarations.
 */
function register_style() {
	$version = filemtime( __DIR__ . '/style.css' );
	wp_register_style( 'wporg-global-fonts', get_font_stylesheet_url(), array(), $version );
}

/**
 * Get a stylesheet for the fonts for enqueuing manually as needed, for ex, in `add_editor_style`.
 */
function get_font_stylesheet_url() {
	return plugins_url( 'style.css', __FILE__ );
}

/**
 * Filter the styles added by editor settings to inject the full URL to the font files.
 *
 * Once inlined in wp-admin, the relative URLs don't match the correct file paths.
 *
 * @param array $editor_settings Default editor settings.
 */
function relative_to_absolute_urls( $editor_settings ) {
	if ( ! isset( $editor_settings['styles'] ) ) {
		return $editor_settings;
	}

	foreach ( $editor_settings['styles'] as $i => $style ) {
		if (
			str_contains( $style['css'], './Inter' ) ||
			str_contains( $style['css'], './EB-Garamond' ) ||
			str_contains( $style['css'], './CourierPrime' ) ||
			str_contains( $style['css'], './IBMPlexMono' )
		) {
			$style['css'] = preg_replace_callback(
				'!url\(./(?P<path>[^)]+)\)!i',
				function( $m ) {
					return "url(" . plugins_url( $m['path'], __FILE__ ) . ")";
				}
			);

			$editor_settings['styles'][ $i ] = $style;
		}
	}

	return $editor_settings;
}

/**
 * Specify a font to be preloaded.
 *
 * This adds the font name (optionally with style and weight) and subset to the
 * preload list. No validation is done at this point, so this won't tell you if
 * the font or subset is invalid. That check is done in `maybe_preload_font` by
 * the `get_font_url` call.
 *
 * @param string $fonts   The font(s) to preload, comma-separated.
 * @param string $subsets The subset(s) to preload, comma-separated.
 *
 * @return bool If the font has been added to the preload list.
 */
function preload_font( $fonts, $subsets ) {
	$style = wp_styles()->query( 'wporg-global-fonts' );
	if ( ! $style || empty( $fonts ) || empty( $subsets ) ) {
		return false;
	}

	$fonts = explode( ',', $fonts );
	$subsets = explode( ',', $subsets );

	$preload = $style->extra['preload'] ?? [];

	foreach ( $fonts as $font ) {
		$new_preload = [
			$font => $subsets,
		];
		$preload = array_merge_recursive( $preload, $new_preload );
	}

	wp_style_add_data( 'wporg-global-fonts', 'preload', $preload );

	return true;
}

/**
 * Add any fonts specified for preloading to the WordPress preload stack.
 */
function maybe_preload_font( $preload ) {
	$style = wp_styles()->query( 'wporg-global-fonts' );

	if ( ! $style || empty( $style->extra['preload'] ) ) {
		return $preload;
	}

	foreach ( (array) $style->extra['preload'] as $font => $subsets ) {
		if ( empty( $font ) || empty( $subsets ) ) {
			continue;
		}

		$subsets = array_unique( $subsets );
		foreach ( $subsets as $subset ) {
			if ( empty( $subset ) ) {
				continue;
			}

			$font_url = get_font_url( $font, $subset );
			if ( ! $font_url ) {
				continue;
			}

			$preload[] = [
				'href'        => $font_url,
				'as'          => 'font',
				'crossorigin' => 'crossorigin',
				'type'        => 'font/woff2',
			];
		}
	}

	return $preload;
}

/**
 * Return the details about a specific font face.
 */
function get_font_url( $font, $subset ) {
	$lower_font   = strtolower( trim( $font ) );
	$lower_subset = strtolower( trim( $subset ) );

	$valid_subsets = array( 'arrows', 'cyrillic-ext', 'cyrillic', 'greek-ext', 'greek', 'latin-ext', 'latin', 'vietnamese' );
	if ( ! in_array( $lower_subset, $valid_subsets ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			trigger_error( sprintf( 'Requested font subset %s does not exist.', esc_html( $lower_subset ) ), E_USER_WARNING );
		}
		return false;
	}

	switch ( $lower_font ) {
		case 'courier prime':
			$font_versions  = [ 'cyrillic-ext' => '4ecb2879668a', 'cyrillic' => '4ecb2879668a', 'greek-ext' => '4ecb2879668a', 'greek' => 'eba1f8ff4da0', 'vietnamese' => 'da75b869896c', 'latin-ext' => '2aad680866fd', 'latin' => '7b9aa715ce49' ];
			$font_folder    = 'CourierPrime/';
			$font_file_name = 'CourierPrime-Regular-';
			break;
		case 'courier prime bold':
			$font_versions  = [ 'cyrillic-ext' => 'c11c7999d78e', 'cyrillic' => 'c11c7999d78e', 'greek-ext' => 'c11c7999d78e', 'greek' => 'ba79634e2cd8', 'vietnamese' => '180aa42496b5', 'latin-ext' => '94527d576afd', 'latin' => '01c037d9e0a1' ];
			$font_folder    = 'CourierPrime/';
			$font_file_name = 'CourierPrime-Bold-';
			break;
		case 'inter':
			$font_versions  = [ 'cyrillic-ext' => '870d267d091f', 'cyrillic' => '2e1f0e6a6eda', 'greek-ext' => 'f1fb719a6429', 'greek' => '8fb4801a481e', 'vietnamese' => 'ec198636627a', 'latin-ext' => '802f9ad48332', 'latin' => 'b07a289bdf69', 'arrows' => '63555c379a62' ];
			$font_folder    = 'Inter/';
			$font_file_name = 'Inter-';
			break;
		case 'eb garamond':
			$font_versions  = [ 'cyrillic-ext' => '8176ef6e06a1', 'cyrillic' => '1af56bf0af64', 'greek-ext' => 'd3bd499d027a', 'greek' => 'ec834832b30b', 'vietnamese' => '30eaef521e8a', 'latin-ext' => 'a08861311328', 'latin' => '2c7bc866cb03', 'arrows' => '943d7defaf99' ];
			$font_folder    = 'EB-Garamond/';
			$font_file_name = 'EBGaramond-';
			break;
		case 'eb garamond italic':
			$font_versions  = [ 'cyrillic-ext' => '7808b24a969c', 'cyrillic' => '766320cf480d', 'greek-ext' => '5c9c07959839', 'greek' => '9cdc7745aab9', 'vietnamese' => '47ae16ca8ebd', 'latin-ext' => 'cdaf0905fb1c', 'latin' => 'b6b388074e82' ];
			$font_folder    = 'EB-Garamond/';
			$font_file_name = 'EBGaramond-Italic-';
			break;
		case 'ibm plex mono extralight':
			$font_versions  = [ 'cyrillic-ext' => '99488ab1f1b4', 'cyrillic' => '370513b21aa8', 'vietnamese' => '04a172643c2d', 'latin-ext' => '1214d6939808', 'latin' => '075de20a9833' ];
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-ExtraLight-';
			break;
		case 'ibm plex mono extralight italic':
			$font_versions  = [ 'cyrillic-ext' => 'f67b80c3648c', 'cyrillic' => '51c4ca00cdb0', 'vietnamese' => '632a48e3ba8e', 'latin-ext' => '454bc3623abc', 'latin' => '38cfc7b0cee7' ];
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-ExtraLightItalic-';
			break;
		case 'ibm plex mono':
			$font_versions  = [ 'cyrillic-ext' => 'd5b4785e465e', 'cyrillic' => 'dbae9f565349', 'vietnamese' => '0aa3dc1ec47b', 'latin-ext' => '139e6cd03358', 'latin' => '8ddc86b0ebf4' ];
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-Regular-';
			break;
		case 'ibm plex mono italic':
			$font_versions  = [ 'cyrillic-ext' => 'c415eab9d09e', 'cyrillic' => 'c586ce96eb42', 'vietnamese' => '0b8d65223f8f', 'latin-ext' => 'bb87d332affb', 'latin' => 'b8d4f3563cf8' ];
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-Italic-';
			break;
		case 'ibm plex mono medium':
			$font_versions  = [ 'cyrillic-ext' => '431d255a3afa', 'cyrillic' => 'beb561f0d2e7', 'greek-ext' => '49bf3255c571', 'greek' => '6152b28764d6', 'vietnamese' => 'c58ce4527d80', 'latin-ext' => '4ace19a3c116', 'latin' => '44150b50e9d4', 'arrows' => 'e9f571cc6a63' ];
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-Medium-';
			break;
		case 'ibm plex mono bold':
			$font_versions  = [ 'cyrillic-ext' => 'd20781be5b07', 'cyrillic' => 'c6178ab7258f', 'vietnamese' => '5e8388bb91ff', 'latin-ext' => 'ac5ae799cb8f', 'latin' => '59cad49c271e' ];
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-Bold-';
			break;
		case 'ibm plex mono bold italic':
			$font_versions  = [ 'cyrillic-ext' => 'acd7ea8d0d94', 'cyrillic' => '20d5b885400b', 'vietnamese' => 'a005afe26029', 'latin-ext' => '580700b45e3a', 'latin' => 'a96c20e7edc2' ];
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-BoldItalic-';
			break;
	}

	$filepath = $font_folder . $font_file_name . $lower_subset . '.woff2';
	if ( ! file_exists( __DIR__ . '/' . $filepath ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			trigger_error( sprintf( 'Requested font file %s does not exist.', esc_html( $filepath ) ), E_USER_WARNING );
		}
		return false;
	}

	$font_version = $font_versions[ $lower_subset ] ?? '';

	return plugins_url( "{$filepath}?ver={$font_version}", __FILE__ );
}
