<?php

/**
 * Block Name: WordPress.org Google Map
 * Description: Renders a Google Map in a block template (no editor UI).
 */

namespace WordPressdotorg\MU_Plugins\Google_Map;

require_once __DIR__ . '/inc/event-filters.php';

add_action( 'init', __NAMESPACE__ . '\init' );
add_filter( 'wporg_query_filter_options_map_format', __NAMESPACE__ . '\get_map_format_options' );
add_filter( 'wporg_query_filter_options_map_type', __NAMESPACE__ . '\get_map_type_options' );


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
	if ( ! empty( $attributes['apiKey'] ) ) {
		// See README for why this has to be a constant.
		$attributes['apiKey'] = constant( $attributes['apiKey'] );
	}

	$attributes['startDate'] = (int) strtotime( $attributes['startDate'] );
	$attributes['endDate']   = (int) strtotime( $attributes['endDate'] );

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
		$attributes['markers'] = get_events( $attributes['filterSlug'], $attributes['startDate'], $attributes['endDate'] );

		// This has to be called in `render()` to know which slug/dates to use.
		schedule_filter_cron( $attributes['filterSlug'], $attributes['startDate'], $attributes['endDate'] );
	}

	$handles = array( $block->block_type->view_script_handles[0], $block->block_type->editor_script_handles[0] );

	foreach ( $handles as $handle ) {
		wp_add_inline_script(
			$handle,
			sprintf(
				'var wporgGoogleMap = wporgGoogleMap || {};
				wporgGoogleMap["%s"] = %s;',
				$attributes['id'],
				wp_json_encode( $attributes )
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

	<div class="wporg-google-map-container">
		<?php if ( $attributes['showFilters'] ) : ?>
			<?php echo do_blocks( '
				<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"nowrap"},"className":"wporg-google-map-query-filters"} -->
					<div class="wp-block-group wporg-query-filters">
						<!-- wp:wporg/query-filter {"key":"map_format"} /-->
						<!-- wp:wporg/query-filter {"key":"map_type"} /-->
						<!-- wp:wporg/query-filter {"key":"map_month"} /-->
						<!-- wp:wporg/query-filter {"key":"map_country"} /-->
					</div>
				<!-- /wp:group -->
			' ); ?>
		<?php endif; ?>

		<!-- TODO: rearrange this so that .wporg-google-map-query-filters above and the map/list/search elements below are all direct descendents of the same container.
		  -- then use Grid to change the `order` so that filters shows up after search
		  -->

		<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
			Loading...
		</div>
	</div>

	<?php

	return ob_get_clean();
}

//
function get_map_format_options( array $options ): array {
	return array(
		'label' => __( 'Format', 'wporg' ),
		'title' => __( 'Format', 'wporg' ),
		'key' => 'map_format',
		'action' => 'javascript:;',
			// TODO: this is temporary, to prevent Apply from making a POST request.
			// ideally we'd update the block to accept an `event` param in addition to `action`.
			// if `event` exists, that event is fired and passed the data so it can filter the map markers based on the selected filters.
		'options' => array(
			'in-person' => 'In Person',
			'online'    => 'Online',
		),
		'selected' => array(),
	);
}

//
function get_map_type_options( array $options ): array {
	return array(
		'label' => __( 'Type', 'wporg' ),
		'title' => __( 'Type', 'wporg' ),
		'key' => 'map_type',
		'action' => 'javascript:;',
		'options' => array(
			'meetup'   => 'Meetup',
			'wordcamp' => 'WordCamp',
		),
		'selected' => array(),
	);
}

// TODO: add month, country
