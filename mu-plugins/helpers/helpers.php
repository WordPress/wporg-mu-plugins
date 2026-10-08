<?php

namespace WordPressdotorg\MU_Plugins\Helpers;

defined( 'WPINC' ) || die();

require_once __DIR__ . '/locale.php';

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
