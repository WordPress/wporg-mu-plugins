<?php

namespace WordPressdotorg\MU_Plugins\Helpers;

defined( 'WPINC' ) || die();

require_once __DIR__ . '/locale.php';
require_once __DIR__ . '/rosetta.php';

/**
 * Join a string with a natural language conjunction at the end.
 *
 * Based on https://stackoverflow.com/a/25057951/450127, modified to include an Oxford comma.
 */
function natural_language_join( array $items, $conjunction = 'and' ): string {
	if ( empty( $items ) ) {
		return '';
	}

	$oxford_separator = 2 === count( $items ) ? ' ' : ', ';
	$last             = array_pop( $items );

	if ( $items ) {
		return implode( ', ', $items ) . $oxford_separator . $conjunction . ' ' . $last;
	}

	return $last;
}

/**
 * Whether the current request is for the Markdown rendering of the page.
 *
 * Mirrors the checks used by the Markdown output on WordPress.org: either the
 * `output_format=md` query argument or an `Accept: text/markdown` header.
 *
 * @return bool
 */
function is_markdown_request() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check of a public query argument.
	$output_format = isset( $_GET['output_format'] ) ? sanitize_key( wp_unslash( $_GET['output_format'] ) ) : '';
	$accept        = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';

	$is_markdown = in_array( $output_format, array( 'md', 'markdown' ), true )
		|| 1 === preg_match( '~^text/(?:x-)?markdown(?:[,;]|$)~', $accept );

	/**
	 * Filters whether the current request is for the Markdown rendering of the page.
	 *
	 * @param bool $is_markdown Whether this is a Markdown request.
	 */
	return apply_filters( 'wporg_is_markdown_request', $is_markdown );
}
