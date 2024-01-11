<?php
/**
 * Code Syntax Block Tweaks
 *
 * Adds support for the shortcodes provided by SyntaxHighlighter Evolved.
 * This allows the current content (with shortcodes) to use the new design's
 * syntax highlighting style & behavior.
 *
 * Also injects the stylesheet with our custom styles.
 */

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks\CodeSyntaxBlock;

use function WordPressdotorg\MU_Plugins\Helpers\is_plugin_active;

add_action( 'init', __NAMESPACE__ . '\maybe_init' );
add_filter( 'mkaz_code_syntax_force_loading', '__return_true' );
add_filter( 'mkaz_prism_css_url', __NAMESPACE__ . '\update_prism_css_url' );

/**
 * Initialize the shortcode replacements only if the Code Syntax Block is active.
 */
function maybe_init() {
	if ( ! is_plugin_active( 'code-syntax-block/index.php' ) ) {
		return;
	}

	add_shortcode( 'php', __NAMESPACE__ . '\do_shortcode_php' );
	add_shortcode( 'js', __NAMESPACE__ . '\do_shortcode_js' );
	add_shortcode( 'css', __NAMESPACE__ . '\do_shortcode_css' );
	add_shortcode( 'code', __NAMESPACE__ . '\do_shortcode_code' );

	add_filter(
		'no_texturize_shortcodes',
		function ( $shortcodes ) {
			$shortcodes[] = 'php';
			$shortcodes[] = 'js';
			$shortcodes[] = 'css';
			$shortcodes[] = 'code';
			return $shortcodes;
		}
	);
}

/**
 * Customize the syntax highlighter style.
 * See https://github.com/PrismJS/prism-themes.
 *
 * @param string $url Absolute URL of the default CSS file you want to enqueue.
 * @return string
 */
function update_prism_css_url( $url ) {
	return plugins_url( 'prism.css', __FILE__ );
}

/**
 * Render the php shortcode using the Code Syntax Block syntax.
 *
 * @param array|string $attr    Shortcode attributes array or empty string.
 * @param string       $content Shortcode content.
 * @param string       $tag     Shortcode name.
 * @return string
 */
function do_shortcode_php( $attr, $content, $tag ) {
	$attr = is_array( $attr ) ? $attr : array();
	$attr['lang'] = 'php';

	return do_shortcode_code( $attr, $content, $tag );
}

/**
 * Render the js shortcode using the Code Syntax Block syntax.
 *
 * @param array|string $attr    Shortcode attributes array or empty string.
 * @param string       $content Shortcode content.
 * @param string       $tag     Shortcode name.
 * @return string
 */
function do_shortcode_js( $attr, $content, $tag ) {
	$attr = is_array( $attr ) ? $attr : array();
	$attr['lang'] = 'js';

	return do_shortcode_code( $attr, $content, $tag );
}

/**
 * Render the css shortcode using the Code Syntax Block syntax.
 *
 * @param array|string $attr    Shortcode attributes array or empty string.
 * @param string       $content Shortcode content.
 * @param string       $tag     Shortcode name.
 * @return string
 */
function do_shortcode_css( $attr, $content, $tag ) {
	$attr = is_array( $attr ) ? $attr : array();
	$attr['lang'] = 'css';

	return do_shortcode_code( $attr, $content, $tag );
}

/**
 * Render the code shortcode using the Code Syntax Block syntax.
 *
 * @param array|string $attr    Shortcode attributes array or empty string.
 * @param string       $content Shortcode content.
 * @param string       $tag     Shortcode name.
 * @return string
 */
function do_shortcode_code( $attr, $content, $tag ) {
	// Use an allowedlist of languages, falling back to PHP.
	// This should account for all languages used in the handbooks.
	$lang_list = [ 'js', 'json', 'sh', 'bash', 'html', 'css', 'scss', 'php', 'markdown', 'yaml' ];
	$lang = in_array( $attr['lang'] ?? '', $lang_list ) ? $attr['lang'] ?? '' : 'php';

	$content = _trim_code( $content );
	// Hides numbers if <= 4 lines of code (last line has no linebreak).
	$show_line_numbers = substr_count( $content, "\n" ) > 3;

	// Shell is flagged with `sh` or `bash` in the handbooks, but Prism uses `shell`.
	if ( 'sh' === $lang || 'bash' === $lang ) {
		$lang = 'shell';
	}

	return do_blocks(
		sprintf(
			'<!-- wp:code {"lineNumbers":%3$s} --><pre class="wp-block-code"><code lang="%1$s" class="language-%1$s %4$s">%2$s</code></pre><!-- /wp:code -->',
			$lang,
			$content,
			$show_line_numbers ? 'true' : 'false',
			$show_line_numbers ? 'line-numbers' : ''
		)
	);
}

/**
 * Trim off any extra space, including initial new lines.
 * Strip out <br /> and <p> added by WordPress.
 *
 * @param string $content Shortcode content.
 * @return string
 */
function _trim_code( $content ) {
	$content = preg_replace( '/<br \/>/', '', $content );
	$content = preg_replace( '/<\/p>\s*<p>/', "\n\n", $content );
	// Trim everything except leading spaces.
	$content = trim( $content, "\n\r\t\v\x00" );
	return $content;
}
