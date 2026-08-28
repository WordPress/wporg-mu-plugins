<?php
/**
 * Block Name: Language Suggest
 * Description: A block for use across the whole wp.org network.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Language_Suggest;

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function language_suggest_block_init() {
	register_block_type( __DIR__ . '/build' );

	register_block_style(
		'wporg/language-suggest',
		array(
			'name'         => 'prominent',
			'label'        => _x( 'Prominent', 'block style name', 'wporg' ),
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\language_suggest_block_init' );

/**
 * Returns the endpoint the front-end script should request.
 *
 * The response is rendered into the block, so the endpoint is resolved here
 * rather than read back out of the DOM: block markup lives in `post_content`,
 * where kses permits arbitrary `data-*` attributes, and any author could
 * otherwise point the fetch wherever they liked.
 *
 * Sites with a context-aware suggestion API -- the plugin directory returns a
 * per-plugin notice -- filter in their own URL. Anything that is not an HTTPS
 * wordpress.org URL falls back to the network-wide default.
 *
 * @return string The endpoint URL.
 */
function get_endpoint() {
	$default = 'https://wordpress.org/lang-guess/lang-guess-ajax.php';

	/**
	 * Filters the language suggestion endpoint for the current site.
	 *
	 * @param string $endpoint Endpoint URL. Must be an HTTPS wordpress.org URL.
	 */
	$endpoint = apply_filters( 'wporg_language_suggest_endpoint', $default );
	$host     = wp_parse_url( $endpoint, PHP_URL_HOST );

	// A backslash is a path separator to the browser, so it and PHP can disagree on the host.
	if ( str_contains( $endpoint, '\\' ) ) {
		return $default;
	}

	if ( 'https' !== wp_parse_url( $endpoint, PHP_URL_SCHEME ) || ! $host ) {
		return $default;
	}

	if ( 'wordpress.org' !== $host && ! str_ends_with( $host, '.wordpress.org' ) ) {
		return $default;
	}

	return $endpoint;
}

/**
 * Inject the locale data for use in viewScript.
 */
function add_locale_data() {
	$data = array(
		'locale'   => get_locale(),
		'endpoint' => get_endpoint(),
	);

	wp_add_inline_script(
		'wporg-language-suggest-view-script',
		'var languageSuggestData = ' . wp_json_encode( $data ) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\add_locale_data' );
