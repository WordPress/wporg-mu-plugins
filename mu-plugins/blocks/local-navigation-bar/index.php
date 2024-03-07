<?php
/**
 * Block Name: Local Navigation Bar
 * Description: A special block to handle the local navigation on pages in a section.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\LocalNavigationBar_Block;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\init' );
add_filter( 'render_block_data', __NAMESPACE__ . '\update_block_attributes' );
add_filter( 'render_block_data', __NAMESPACE__ . '\update_child_block_attributes', 10, 3 );
add_filter( 'render_block_wporg/local-navigation-bar', __NAMESPACE__ . '\customize_navigation_block_icon' );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type( __DIR__ . '/build' );

	// Add the Brush Stroke block style.
	register_block_style(
		'wporg/local-navigation-bar',
		array(
			'name'         => 'brush-stroke',
			'label'        => __( 'Brush Stroke', 'wporg' ),
		)
	);
}

/**
 * Inject the default block values. In the editor, these are read from block.json.
 * See https://github.com/WordPress/gutenberg/issues/50229.
 *
 * @param array $block The parsed block data.
 *
 * @return array
 */
function update_block_attributes( $block ) {
	if ( ! empty( $block['blockName'] ) && 'wporg/local-navigation-bar' === $block['blockName'] ) {
		// Always override alignment.
		$block['attrs']['align'] = 'full';

		// Set layout values if they don't exist.
		$default_layout = array(
			'type' => 'flex',
			'flexWrap' => 'nowrap',
			'justifyContent' => 'space-between',
		);
		if ( ! empty( $block['attrs']['layout'] ) ) {
			$block['attrs']['layout'] = array_merge( $default_layout, $block['attrs']['layout'] );
		} else {
			$block['attrs']['layout'] = $default_layout;
		}

		// Set position if it doesn't exist (functionally this will always be
		// sticky, unless different positions are added).
		if ( empty( $block['attrs']['style']['position'] ) ) {
			$block['attrs']['style']['position'] = array(
				'type' => 'sticky',
			);
		}
	}

	return $block;
}

/**
 * Ensure the child navigation block uses the expected attributes.
 *
 * @param array         $parsed_block The block being rendered.
 * @param array         $source_block An un-modified copy of $parsed_block, as it appeared in the source content.
 * @param WP_Block|null $parent_block If this is a nested block, a reference to the parent block.
 *
 * @return array The updated block.
 */
function update_child_block_attributes( $parsed_block, $source_block, $parent_block ) {
	if ( empty( $parsed_block['blockName'] ) ) {
		return $parsed_block;
	}

	// If navigation block…
	if ( 'core/navigation' === $parsed_block['blockName'] ) {
		// with the local navigation bar as a parent…
		if ( ! $parent_block || 'wporg/local-navigation-bar' !== $parent_block->name ) {
			return $parsed_block;
		}
		// set the values we need.
		$parsed_block['attrs']['icon'] = 'menu';
		$parsed_block['attrs']['fontSize'] = 'small';
		$parsed_block['attrs']['openSubmenusOnClick'] = true;
		$parsed_block['attrs']['layout'] = array(
			'type' => 'flex',
			'orientation' => 'horizontal',
		);

		// Add an extra navigation block which is always collapsed, so that it
		// can be swapped out when the section title + nav menu collide.
		add_filter( 'render_block_core/navigation', __NAMESPACE__ . '\add_extra_navigation', 10, 3 );
	}

	return $parsed_block;
}

/**
 * Inject an extra navigation block into the local nav, which is enabled when the section title is long.
 */
function add_extra_navigation( $block_content, $block ) {
	remove_filter( 'render_block_core/navigation', __NAMESPACE__ . '\add_extra_navigation', 10, 3 );

	// This menu should always be in the collapsed state.
	$block['attrs']['overlayMenu'] = 'always';

	if ( isset( $block['attrs']['className'] ) ) {
		$block['attrs']['className'] .= ' wporg-is-collapsed-nav';
	} else {
		$block['attrs']['className'] = 'wporg-is-collapsed-nav';
	}

	$menu_block_content = do_blocks( '<!-- wp:navigation ' . wp_json_encode( $block['attrs'] ) . ' /-->' );
	$menu_block_content = customize_navigation_block_icon( $menu_block_content );
	return $block_content . $menu_block_content;
}

/**
 * Replace a nested navigation block mobile button icon with a caret icon.
 * Only applies if it has the 3 bar icon set, as this has an svg with <path> to update.
 *
 * @param string $block_content The block content.
 *
 * @return string
 */
function customize_navigation_block_icon( $block_content ) {
	$tag_processor = new \WP_HTML_Tag_Processor( $block_content );

	if (
		$tag_processor->next_tag(
			array(
				'tag_name' => 'nav',
				'class_name' => 'wp-block-navigation',
			)
		)
	) {
		if (
			$tag_processor->next_tag(
				array(
					'tag_name' => 'button',
					'class_name' => 'wp-block-navigation__responsive-container-open',
				)
			) &&
			$tag_processor->next_tag( 'path' )
		) {
			$tag_processor->set_attribute( 'd', 'M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z' );
		}

		return $tag_processor->get_updated_html();
	}

	return $block_content;
}
