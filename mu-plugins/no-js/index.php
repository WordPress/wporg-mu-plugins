<?php
/**
 * Plugin Name: No-JS
 * Description: This adds a 'no-js' class and removes it with JS for styling.
 */

namespace WordPressdotorg\MU_Plugins\No_JS;

// Actions & Filters
add_filter( 'language_attributes', __NAMESPACE__ . '\add_no_js_tag', 10, 2 );
add_action( 'wp_head', __NAMESPACE__ . '\remove_js_tag' );

/**
 * Add a 'no-js' tag by default.
 *
 * @param string $output
 * @param string $doctype
 * @return string
 */
function add_no_js_tag( $output, $doctype ) {
	if ( 'html' !== $doctype ) {
		return $output;
	}

	$output .= ' class="no-js"';

	return $output;
}

/**
 * Remove the 'no-js' class from html using JS.
 */
function remove_js_tag() {
	?>
	<script>
		document.documentElement.classList.remove('no-js')
	</script>
	<?php
}
