<?php

/**
 * Block Name: WordPress.org Google Map
 * Description: Renders a Google Map in a block template (no editor UI).
 */

namespace WordPressdotorg\MU_Plugins\Google_Map;

defined( 'WPINC' ) || die();

require_once __DIR__ . '/inc/event-filters.php';

add_action( 'init', __NAMESPACE__ . '\init' );


/**
 * Registers the block from `block.json`.
 */
function init() {
	register_block_type(
		__DIR__ . '/build',
		array(
			'render_callback' => __NAMESPACE__ . '\render',
		)
	);
}

/**
 * Determines whether a URL stays within this site's network.
 *
 * @param string $url URL to test.
 *
 * @return bool True when the URL targets this site or a sibling on its network.
 */
function is_network_url( $url ) {
	$parsed = wp_parse_url( $url );

	// `parse_url()` gives up on `https:///host`, which browsers still resolve to a host.
	if ( false === $parsed ) {
		return false;
	}

	// A scheme with no host parses as a path here, but the browser reads it as an authority.
	if ( isset( $parsed['scheme'] ) && ! isset( $parsed['host'] ) ) {
		return false;
	}

	$host = strtolower( $parsed['host'] ?? '' );

	// Without a host the URL resolves against this page, so it cannot leave.
	if ( '' === $host ) {
		return true;
	}

	$domains = array( strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );

	// Subsites of a network reach each other, so a subsite must also accept the network's domain.
	if ( is_multisite() && get_network() ) {
		$domains[] = strtolower( get_network()->domain );
	}

	foreach ( $domains as $domain ) {
		if ( '' !== $domain && ( $host === $domain || str_ends_with( $host, '.' . $domain ) ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Render the block content.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the block markup.
 */
function render( $attributes, $content, $block ) {
	// Allow the apiKey to be defined dynamically.
	$attributes['apiKey'] = apply_filters( 'wporg_google_map_apikey', $attributes['apiKey'] ?? '', $attributes, $content, $block );

	$attributes['startDate'] = (int) strtotime( $attributes['startDate'] );
	$attributes['endDate']   = (int) strtotime( $attributes['endDate'] );

	// Submitting the form navigates here, so an action that comes from a block attribute must not leave the network.
	$search_form_action             = sanitize_url( (string) ( $attributes['searchFormAction'] ?? '' ), array( 'http', 'https' ) );
	$attributes['searchFormAction'] = is_network_url( $search_form_action ) ? $search_form_action : '';

	$attributes['searchIcon'] = plugins_url( 'images/search.svg', __FILE__ );

	$attributes['markerIcon'] = array(
		'imagesDirUrl'        => plugins_url( 'images', __FILE__ ),
		'markerHeight'        => 68,
		'markerWidth'         => 68,
		'markerAnchorYOffset' => -5,
		'clusterWidth'        => 38,
		'clusterHeight'       => 38,
	);

	$attributes['markerIcon']['markerAnchorXOffset'] = $attributes['markerIcon']['markerWidth'] / -4;

	if ( ! empty( $attributes['filterSlug'] ) ) {
		$attributes['markers'] = get_events( $attributes['filterSlug'], $attributes['startDate'], $attributes['endDate'], array() );
	}

	$handles = array( $block->block_type->view_script_handles[0], $block->block_type->editor_script_handles[0] );

	foreach ( $handles as $handle ) {
		wp_add_inline_script(
			$handle,
			sprintf(
				'var wporgGoogleMap = wporgGoogleMap || {};
				wporgGoogleMap[%s] = %s;',
				wp_json_encode( (string) $attributes['id'], JSON_HEX_TAG | JSON_HEX_AMP ),
				wp_json_encode( $attributes, JSON_HEX_TAG | JSON_HEX_AMP )
			),
			'before'
		);
	}

	$wrapper_attributes = get_block_wrapper_attributes( array(
		'id'          => 'wp-block-wporg-google-map-' . $attributes['id'],
		'class'       => isset( $attributes['align'] ) ? 'align' . $attributes['align'] : '',
		'data-map-id' => $attributes['id'],
	) );

	ob_start();

	?>

	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		Loading...
	</div>

	<?php

	return ob_get_clean();
}
