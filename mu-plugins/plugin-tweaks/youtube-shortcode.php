<?php
/**
 * Add a shortcode for nocookie youtube embeds.
 *
 * These are not supported in the core embed block or shortcode.
 * See https://core.trac.wordpress.org/ticket/44610
 */

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks\Youtube_Shortcode;

add_shortcode( 'youtube-nocookie', __NAMESPACE__ . '\render' );

/**
 * Render the youtube iframe.
 *
 * The shortcode content is the URL, checked against a safelist of
 * `youtube-nocookie` domains. Attributes can be `width`, `height`,
 * and `title.
 *
 * @param array  $attr    Shortcode attributes array, can be empty if the original arguments string cannot be parsed.
 * @param string $content Content inside shortcode tags.
 *
 * @return string HTML code for iframe embed.
 */
function render( $attr, $content ) {
	// Short out early if the content is not a valid URL.
	// Returns null if content is not a URL at all.
	$host = wp_parse_url( $content, PHP_URL_HOST );
	$valid_hosts = [ 'www.youtube-nocookie.com', 'youtube-nocookie.com' ];
	if ( ! in_array( $host, $valid_hosts, true ) ) {
		return '';
	}

	$defaults = array(
		'width' => '100%',
		'height' => false,
		'title' => 'YouTube video player',
	);
	$args = shortcode_atts( $defaults, $attr );

	$html_attrs = '';
	foreach ( $args as $name => $value ) {
		if ( $value ) {
			$html_attrs .= $name . '="' . esc_attr( $value ) . '" ';
		}
	}

	return sprintf(
		// `allow` settings copied from youtube-provided embed code.
		'<iframe style="aspect-ratio: 16/9;" src="%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen %s></iframe>',
		esc_url( $content ),
		$html_attrs
	);
}
