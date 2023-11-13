<?php

/**
 * Block Name: WordPress.org Google Map
 * Description: Renders a Google Map in a block template (no editor UI).
 */

namespace WordPressdotorg\MU_Plugins\Google_Map;

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
 * Render the block content.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the block markup.
 */
function render( $attributes, $content, $block ) {
	$attributes['id'] = 'wp-block-wporg-google-map-' . $attributes['id'];

	if ( ! empty( $attributes['apiKey'] ) ) {
		// See README for why this has to be a constant.
		$attributes['apiKey'] = constant( $attributes['apiKey'] );
	}

	$attributes['searchIcon'] = plugins_url( 'images/search.svg', __FILE__ );

	$attributes['markerIcon'] = array(
		'markerUrl'           => plugins_url( 'images/map-marker.svg', __FILE__ ),
		'markerHeight'        => 68,
		'markerWidth'         => 68,
		'markerAnchorYOffset' => -5,
		'clusterUrl'          => plugins_url( 'images/cluster-background.svg', __FILE__ ),
		'clusterWidth'        => 38,
		'clusterHeight'       => 38,
	);

	$attributes['markerIcon']['markerAnchorXOffset'] = $attributes['markerIcon']['markerWidth'] / -4;

	wp_add_inline_script(
		$block->block_type->view_script_handles[0],
		sprintf(
			'const wporgGoogleMap = %s;',
			wp_json_encode( $attributes )
		),
		'before'
	);

	wp_add_inline_script(
		$block->block_type->editor_script_handles[0],
		sprintf(
			'const wporgGoogleMap = %s;',
			wp_json_encode( $attributes )
		),
		'before'
	);

	$wrapper_attributes = get_block_wrapper_attributes( array( 'id' => $attributes['id'] ) );

	ob_start();

	?>

	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		Loading...
	</div>

	<?php

	return ob_get_clean();
}
