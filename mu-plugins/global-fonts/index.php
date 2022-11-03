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
		if ( str_contains( $style['css'], './Inter' ) || str_contains( $style['css'], './EB-Garamond' ) ) {
			$url = plugins_url( '', __FILE__ );
			$style['css'] = str_replace( 'url(./', "url($url/", $style['css'] );
			$editor_settings['styles'][ $i ] = $style;
		}
	}

	return $editor_settings;
}

/**
 * Specify a font to be preloaded.
 *
 * @param string $fonts The font(s) to preload.
 * @param string $subsets The subset(s) to preload.
 * @return bool If the font will be preloaded.
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
	$lower_font = strtolower( trim( $font ) );
	$lower_subset = strtolower( trim( $subset ) );

	switch ( $lower_font ) {
		case 'inter':
			$font_folder = 'Inter/';
			$font_file_name = 'Inter-';
			return plugins_url( $font_folder . $font_file_name . $lower_subset . '.woff2', __FILE__ );
		case 'eb garamond':
			$font_folder = 'EB-Garamond/';
			$font_file_name = 'EBGaramond-';
			return plugins_url( $font_folder . $font_file_name . $lower_subset . '.woff2', __FILE__ );
		case 'eb garamond italic':
			$font_folder = 'EB-Garamond/';
			$font_file_name = 'EBGaramond-Italic-';
			return plugins_url( $font_folder . $font_file_name . $lower_subset . '.woff2', __FILE__ );
	}

	return false;
}
