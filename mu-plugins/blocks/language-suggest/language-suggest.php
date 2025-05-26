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
 * Inject the locale data for use in viewScript.
 */
function add_locale_data() {
	wp_add_inline_script(
		'wporg-language-suggest-view-script',
		'var languageSuggestData = ' . wp_json_encode( array( 'locale' => get_locale() ) ) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\add_locale_data' );

/**
 * The strings used within the Language Suggest endpoint, for translation purposes.
 */
function strings_for_glotpress() {
	throw new \Exception( 'This function should not be called. This exists for translation purposes only.' );

	// The Language suggest header strings:
	__( 'WordPress is also available in %s.', 'wporg' );
	__( 'Learn WordPress is also available in %s.', 'wporg' );
	__( 'WordPress support forums are also available in %s.', 'wporg' );

	// The wp_sprintf_l() strings, copied to WordPress.org so the translations are translated in conjunction with the above.
	array(
		/* translators: Used to join items in a list with more than 2 items. */
		'between'          => __( '%1$s, %2$s', 'wporg' ),
		/* translators: Used to join last two items in a list with more than 2 times. */
		'between_last_two' => __( '%1$s, and %2$s', 'wporg' ),
		/* translators: Used to join items in a list with only 2 items. */
		'between_only_two' => __( '%1$s and %2$s', 'wporg' ),
	);
}
