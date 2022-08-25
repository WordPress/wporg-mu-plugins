<?php
/**
 * Plugin Name: WordPress.org Fonts
 * Description: Registers a stylesheet to load Inter and EB Garamond for use in other themes/plugins.
 */

namespace WordPressdotorg\MU_Plugins\Global_Fonts;

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
 * @param array|string $font_faces The font(s) to preload.
 * @return bool If the font will be preloaded.
 */
function preload_font( $font_faces ) {
	$style = wp_styles()->query( 'wporg-global-fonts' );
	if ( ! $style ) {
		return false;
	}

	if ( ! is_array( $font_faces ) ) {
		$font_faces = [ $font_faces ];
	}

	$preload = $style->extra['preload'] ?? [];
	$preload = array_merge( $preload, $font_faces );
	$preload = array_unique( $preload );

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

	foreach ( (array) $style->extra['preload'] as $font_face ) {
		$font = get_font_details( $font_face );
		if ( ! $font ) {
			continue;
		}

		$preload[] = [
			'href'        => $font['url'],
			'as'          => 'font',
			'crossorigin' => 'crossorigin',
			'type'        => $font['type'],
		];
	}

	return $preload;
}

/**
 * Return the details about a specific font face.
 */
function get_font_details( $font ) {
	switch ( $font ) {
		case 'Inter':
			return [
				'url' => plugins_url( 'Inter/Inter.woff2?v=3.19', __FILE__ ),
				'type' => 'font/woff2'
			];
		case 'Inter italic':
			return [
				'url' => plugins_url( 'Inter/Inter-Italic.woff2?v=3.19', __FILE__ ),
				'type' => 'font/woff2'
			];
		case 'EB Garamond':
			return [
				'url' => plugins_url( 'EB-Garamond/EB-Garamond.woff2?v=0.017', __FILE__ ),
				'type' => 'font/woff2'
			];
		case 'EB Garamond italic':
			return [
				'url' => plugins_url( 'EB-Garamond/EB-Garamond-Italic.woff2?v=0.017', __FILE__ ),
				'type' => 'font/woff2'
			];
	}

	return false;
}
