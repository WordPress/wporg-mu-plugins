<?php
/**
 * Block Name: Modal
 * Description: Display content in a modal, hidden behind a button click.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Modal;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type( __DIR__ . '/build' );
}

/**
 * Get the style declaration for a color attribute.
 *
 * The base color attribute (e.g. `backgroundColor`) is the preset color name,
 * and needs to be converted into a CSS variable.
 * The custom* color attribute (e.g. `customBackgroundColor`) is a hex value.
 *
 * In most cases, the base color attribute has a default value (see block.json),
 * so there will always be a value for that attribute. This means we can pick
 * up the custom* color attribute first, and fall back to the preset color if
 * it's not found. Also, when picked in the color picker, the custom color
 * will be prefilled with the hex value of the preset color.
 *
 * The overlayColor is a special case, it sets a default on `customOverlayColor`.
 * If only `overlayColor` is set, the custom default will be used. If coding a
 * modal by hand, make sure to set both `overlayColor` and `customColorOverlay`
 * when needed (or only `customColorOverlay`).
 *
 * @param array  $attributes The block attributes.
 * @param string $name       The name of the base attribute.
 *
 * @return string The style declaration (or empty if no colors set).
 */
function get_style_decl_from_attr( $attributes, $name ) {
	$value = false;
	if ( ! empty( $attributes[ 'custom' . ucfirst( $name ) ] ) ) {
		$value = $attributes[ 'custom' . ucfirst( $name ) ];
	} elseif ( ! empty( $attributes[ $name ] ) ) {
		$value = "var(--wp--preset--color--{$attributes[$name]})";
	}

	if ( $value ) {
		// Get the custom property name.
		$slug = _wp_to_kebab_case( str_replace( 'Color', '', $name ) );
		return "--wp--custom--wporg-modal--color--{$slug}: {$value};";
	}

	return '';
}
