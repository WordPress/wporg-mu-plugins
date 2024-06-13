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
			'label'        => _x( 'Prominent', 'wporg' ),
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\language_suggest_block_init' );

function language_suggest_enqueue_scripts() {
    wp_enqueue_script(
        'language-suggest-front',
        plugins_url( 'src/front.js', __FILE__ ),
        array(),
        null,
        true
    );

    wp_add_inline_script(
        'language-suggest-front',
        'var LanguageSuggestData = ' . json_encode( array( 'locale' => get_locale() ) ) . ';',
        'before'
    );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\language_suggest_enqueue_scripts' );
