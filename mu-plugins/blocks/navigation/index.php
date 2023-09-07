<?php
/**
 * Extends the core navigation block to use hardcoded menu items, from a defined set of menus provided via a filter.
 * The menu to use is selected via the block inspector controls, and set as the `menuSlug` attribute.
 * Example output: `<!-- wp:navigation {"menuSlug":"main-menu"} -->`
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Navigation_Block;

use WP_Block_List, WP_Block_Supports;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_action( 'init', __NAMESPACE__ . '\init' );
add_filter( 'block_core_navigation_render_inner_blocks', __NAMESPACE__ . '\update_navigation_items' );

/**
 * Add the JS script to update the navigation block.
 *
 * The dependencies are autogenerated in block.json, and can be read with
 * `wp_json_file_decode` & `register_block_script_handle.
 */
function init() {
	$metadata_file = dirname( dirname( __DIR__ ) ) . '/blocks/navigation/build/block.json';
	$metadata = wp_json_file_decode( $metadata_file, array( 'associative' => true ) );
	$metadata['file'] = $metadata_file;
	$editor_script_handle = register_block_script_handle( $metadata, 'editorScript', 0 );
	add_action(
		'enqueue_block_assets',
		function() use ( $editor_script_handle ) {
			if ( is_admin() && wp_should_load_block_editor_scripts_and_styles() ) {
				wp_localize_script(
					$editor_script_handle,
					'wporgLocalNavigationMenus',
					apply_filters( 'wporg_block_navigation_menus', array() )
				);
				wp_enqueue_script( $editor_script_handle );
			}
		}
	);

	// Hide the menu selection when a dynamic menu is selected.
	add_action(
		'admin_print_styles',
		function() {
			global $hook_suffix;
			if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {
				return;
			}
			echo '<style>.wporg-nav-hide-next-panel + .components-panel__body { display: none; }</style>';
		}
	);
}

/**
 * Update the inner blocks (menu items) in a navigation block.
 *
 * @param WP_Block_List $inner_blocks
 */
function update_navigation_items( $inner_blocks ) {
	// This contains the parent block, so that we can check that we're in the correct Navigation.
	$block = WP_Block_Supports::$block_to_render;
	if (
		isset( $block['blockName'] ) &&
		'core/navigation' === $block['blockName'] &&
		isset( $block['attrs']['menuSlug'] ) &&
		$block['attrs']['menuSlug']
	) {
		$menu_content = get_menu_content( $block['attrs']['menuSlug'] );
		$parsed_blocks = parse_blocks( $menu_content );
		$compacted_blocks = block_core_navigation_filter_out_empty_blocks( $parsed_blocks );
		$inner_blocks = new WP_Block_List( $compacted_blocks, $block['attrs'] );
	}
	return $inner_blocks;
}

/**
 * Get the navigation menu content as blocks.
 *
 * @param string $menu_slug
 *
 * @return string Menu items in block syntax.
 */
function get_menu_content( $menu_slug ) {
	$menu_items = get_menu_items( $menu_slug );
	if ( ! $menu_items ) {
		return '';
	}

	$menu_content = '';
	foreach ( $menu_items as $item ) {
		$block_code = '<!-- wp:navigation-link {"label":"%1$s","url":"%2$s","kind":"custom"} /-->';

		// If this is a relative link, convert it to absolute and try to find
		// the corresponding ID, so that the `current` attributes are used.
		if ( str_starts_with( $item['url'], '/' ) ) {
			$page_obj = get_page_by_path( $item['url'] );
			$item['url'] = home_url( $item['url'] );
			if ( $page_obj ) {
				// A page was found, so use the post-type link.
				$block_code = '<!-- wp:navigation-link {"label":"%1$s","url":"%2$s","kind":"post-type","id":"%3$s"} /-->';
				$item['id'] = $page_obj->ID;
			}
		}

		$menu_content .= sprintf(
			$block_code,
			esc_html( $item['label'] ),
			esc_url( $item['url'], ),
			isset( $item['id'] ) ? intval( $item['id'] ) : ''
		);
	}

	return $menu_content;
}

/**
 * Get a list of menu items for a given menu slug.
 *
 * This is used to build up the navigation menu. Relative links should
 * correspond to pages on the site, while absolute URLs can be used to
 * navigate off-site.
 *
 * @param string $menu_slug
 *
 * @return array|boolean List of menu items with label, url, or false if the
 *                       slug does not match a navigation list.
 */
function get_menu_items( $menu_slug ) {
	/**
	 * Get configuration for this navigation from a filter, so that child themes can
	 * dynamically configure the output without needing to rebuild the HTML.
	 *
	 * @param array $menus {
	 *     Array of menus available for this navigation.
	 *
	 *     The return value should use the following format.
	 *
	 *     @var array $menu An associative array that contains the menu items.
	 *                      Each menu item is represented as an associative array with the following keys:
	 *                       - label: The label of the menu item.
	 *                       - url: The URL of the menu item.
	 *     @example
	 *     $menu = array(
	 *         'main-menu' => array(
	 *             array(
	 *                 'label' => 'Home',
	 *                 'url' => '/'
	 *             )
	 *         )
	 *     );
	 * }
	 */
	$menus = apply_filters( 'wporg_block_navigation_menus', array() );

	if ( ! $menu_slug || empty( $menus ) || ! isset( $menus[ $menu_slug ] ) ) {
		return false;
	}

	return $menus[ $menu_slug ];
}
