<?php

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks\Gutenberg;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_filter( 'render_block_core/post-title', __NAMESPACE__ . '\swap_h0_for_paragraph', 20 );
add_filter( 'render_block_core/query-title', __NAMESPACE__ . '\swap_h0_for_paragraph', 20 );
add_filter( 'wp_script_attributes', __NAMESPACE__ . '\inject_module_cachebuster' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\fix_rtl_style_includes', 1 );

/**
 * Replace invalid `h0` tags with paragraphs.
 *
 * Setting the `level` to 0 technically works for site-title, post-title,
 * and query-title, but the latter two don't do any validation before outputting
 * `<h{level}>`, so we end up with the invalid `h0` when trying to remove
 * heading semantics.
 *
 * @param string $block_content The block content.
 *
 * @return string The updated block content.
 */
function swap_h0_for_paragraph( $block_content ) {
	return str_replace(
		array( '<h0', '</h0>' ),
		array( '<p', '</p>' ),
		$block_content
	);
}

/**
 * Add a custom cachebuster to the module scripts.
 *
 * See https://a8c.slack.com/archives/C0393K4ADM3/p1709930043067369
 *
 * @param array $attributes Key-value pairs representing `<script>` tag attributes.
 *
 * @return array
 */
function inject_module_cachebuster( $attributes ) {
	if ( ! isset( $attributes['src'], $attributes['type'] ) ) {
		return $attributes;
	}

	if ( 'module' !== $attributes['type'] ) {
		return $attributes;
	}

	$cachebuster = '20240308';

	$source = $attributes['src'];
	wp_parse_str( wp_parse_url( $source, PHP_URL_QUERY ), $source_query );
	$version = $source_query['ver'] ?? '';

	$source = str_replace(
		"ver={$version}",
		"ver={$version}-{$cachebuster}",
		$source
	);

	$attributes['src'] = $source;
	return $attributes;
}

/**
 * Remove the suffix from RTL files.
 *
 * CSS registered from block.json are incorrectly configured with a suffix for
 * RTL sites, but the files don't use a suffix. This prevents the files from
 * being replaced correctly, causing visual issues on RTL sites.
 *
 * See https://core.trac.wordpress.org/ticket/61625.
 */
function fix_rtl_style_includes() {
	$wp_styles = wp_styles();

	foreach ( $wp_styles->registered as $handle => $data ) {
		// Filter out the wporg-* styles, and only adjust styles with rtl data.
		if ( \str_starts_with( $handle, 'wporg-' ) && isset( $data->extra['rtl'] ) ) {
			// Remove the suffix data.
			wp_style_add_data( $handle, 'suffix', '' );
		}
	}
}

/*
 * Until Gutenberg 23.5.0 is released, this workaround avoids a fatal with incompatible
 * core WP code, where GB expects `filePath`, but core has `file_path`.
 *
 */
function wporg_icons_filepath_shim() {
	if ( ! class_exists( 'WP_Icons_Registry', false ) ) {
		return;
	}

	// The active singleton may be core's WP_Icons_Registry OR the plugin's
	// WP_Icons_Registry_Gutenberg (swapped in at init:1). Patch whichever is live.
	$registry = WP_Icons_Registry::get_instance();

	try {
		// `registered_icons` is declared protected on the base class, so reflect the
		// base class even when the live instance is the Gutenberg subclass.
		// NB: do NOT use $registry->get_registered_i
		// get_content() and would throw the exact fatal we're fixing.
		$prop = new ReflectionProperty( 'WP_Icons_Registry', 'registered_icons' );
	} catch ( ReflectionException $e ) {
		return; // Core changed shape; nothing to do.
	}

	$icons   = $prop->getValue( $registry );
	$patched = 0;

	foreach ( $icons as $name => $icon ) {
		// THE CONDITIONAL: only bridge entries that still carry the old camelCase key
		// without the new snake_case one. Once Gutente
		// `file_path` (core #64847), `file_path` is already set (or `filePath` is gone),
		// this is false for every icon, and the shim
		if ( empty( $icon['file_path'] ) && ! empty( $icon['filePath'] ) ) {
			$icons[ $name ]['file_path'] = $icon['filePath'];
			++$patched;
		}
	}

	if ( 0 === $patched ) {
		return; // Already compatible: core is live, or the Gutenberg fix has shipped.
	}

	$prop->setValue( $registry, $icons );
}
