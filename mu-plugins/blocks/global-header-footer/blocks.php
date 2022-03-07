<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer;

use Rosetta_Sites, WP_Post, WP_REST_Server, WP_Theme_JSON_Resolver;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\register_block_types' );
add_action( 'admin_bar_init', __NAMESPACE__ . '\remove_admin_bar_callback', 15 );
add_action( 'rest_api_init', __NAMESPACE__ . '\register_routes' );
add_action( 'enqueue_block_assets', __NAMESPACE__ . '\register_block_types_js' );
add_filter( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_block_assets', 200 ); // Always last.
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_compat_wp4_styles', 5 ); // Before any theme CSS.
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\unregister_classic_global_styles', 20 );
add_action( 'wp_head', __NAMESPACE__ . '\preload_google_fonts' );
add_filter( 'style_loader_src', __NAMESPACE__ . '\update_google_fonts_url', 10, 2 );
add_filter( 'render_block_core/navigation-link', __NAMESPACE__ . '\swap_submenu_arrow_svg' );
add_filter( 'render_block_core/search', __NAMESPACE__ . '\swap_header_search_action', 10, 2 );

/**
 * Register block types
 *
 * These are intentionally missing arguments like `title`, `category`, `icon`, etc, because we don't want them
 * showing up in the Block Inserter, regardless of which theme is running.
 */
function register_block_types() {
	register_block_type(
		'wporg/global-header',
		array(
			'title'           => 'Global Header',
			'render_callback' => __NAMESPACE__ . '\render_global_header',
			'style'           => 'wporg-global-header-footer',
			'editor_style'    => 'wporg-global-header-footer',
			'script'          => 'wporg-global-header-script',
		)
	);

	register_block_type(
		'wporg/global-footer',
		array(
			'title'           => 'Global Footer',
			'render_callback' => __NAMESPACE__ . '\render_global_footer',
			'style'           => 'wporg-global-header-footer',
			'editor_style'    => 'wporg-global-header-footer',
		)
	);
}

/**
 * Register the script & stylesheet for use in the blocks.
 */
function register_block_assets() {
	$suffix = is_rtl() ? '-rtl' : '';

	// Load `block-library` styles first, so that our styles override them.
	$style_dependencies = array( 'wp-block-library' );
	if ( wp_style_is( 'wporg-global-fonts', 'registered' ) ) {
		$style_dependencies[] = 'wporg-global-fonts';
	}
	wp_register_style(
		'wporg-global-header-footer',
		plugins_url( "/build/style$suffix.css", __FILE__ ),
		$style_dependencies,
		filemtime( __DIR__ . "/build/style$suffix.css" )
	);

	wp_register_script(
		'wporg-global-header-script',
		plugins_url( '/js/wporg-global-header-script.js', __FILE__ ),
		array(),
		filemtime( __DIR__ . '/js/wporg-global-header-script.js' ),
		true
	);

	wp_localize_script(
		'wporg-global-header-script',
		'wporgGlobalHeaderI18n',
		array(
			'openSearchLabel' => __( 'Open Search', 'wporg' ),
			'closeSearchLabel' => __( 'Close Search', 'wporg' ),
		)
	);
}

/**
 * Remove the default margin-top added when the admin bar is used.
 *
 * The core handling uses `!important`, which overrides the sticky header offset in `common.pcss`.
 */
function remove_admin_bar_callback() {
	remove_action( 'gp_head', '_admin_bar_bump_cb' );
	remove_action( 'wp_head', '_admin_bar_bump_cb' );
}

/**
 * Register REST API routes, so non-WP applications can integrate it.
 */
function register_routes() {
	register_rest_route(
		'global-header-footer/v1',
		'header',
		array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => __NAMESPACE__ . '\rest_render_global_header',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route(
		'global-header-footer/v1',
		'header/codex',
		array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => __NAMESPACE__ . '\rest_render_codex_global_header',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route(
		'global-header-footer/v1',
		'header/planet',
		array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => __NAMESPACE__ . '\rest_render_planet_global_header',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route(
		'global-header-footer/v1',
		'footer',
		array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => __NAMESPACE__ . '\rest_render_global_footer',
				'permission_callback' => '__return_true',
			),
		)
	);

	// Requesting this on another network would create an infinite loop.
	if ( is_wporg_network() ) {
		register_rest_route(
			'global-header-footer/v1',
			'styles',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => __NAMESPACE__ . '\rest_render_global_styles',
					'permission_callback' => '__return_true',
				),
			)
		);
	}
}

/**
 * Register block types in JS, for the editor.
 *
 * Blocks need to be registered in JS to show up in the editor. We can dynamically register the blocks using
 * ServerSideRender, which will render the PHP callback. This runs through the existing blocks to find any
 * matching `wporg/global-*` blocks, so it will match the header & footer, and any other pattern-blocks we
 * might add in the future.
 *
 * Watch https://github.com/WordPress/gutenberg/issues/28734 for a possible core solution.
 */
function register_block_types_js() {
	$blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
	$wporg_global_blocks = array_filter(
		$blocks,
		function ( $block ) {
			return 'wporg/global-' === substr( $block->name, 0, 13 );
		}
	);
	ob_start();
	?>
	( function( wp ) {
		<?php foreach ( $wporg_global_blocks as $block ) : ?>
		wp.blocks.registerBlockType(
			'<?php echo esc_html( $block->name ); ?>',
			{
				title: '<?php echo esc_html( $block->title ); ?>',
				edit: function( props ) {
					return wp.element.createElement( wp.serverSideRender, {
						block: '<?php echo esc_html( $block->name ); ?>',
						attributes: props.attributes
					} );
				},
			}
		);
		<?php endforeach; ?>
	}( window.wp ));
	<?php
	wp_add_inline_script( 'wp-editor', ob_get_clean(), 'after' );
}

/**
 * Filter the google fonts URL to use the "CSS2" version of the API.
 *
 * @param string $src    The source URL of the enqueued style.
 * @param string $handle The style's registered handle.
 * @return string Updated URL for `open-sans`.
 */
function update_google_fonts_url( $src, $handle ) {
	if ( 'open-sans' === $handle ) {
		return 'https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&display=swap';
	}
	return $src;
}

/**
 * Add preconnect resource hints for the Google Fonts API.
 */
function preload_google_fonts() {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> ';
}

/**
 * Output styles for themes that don't use `wp4-styles`. This provides compat with the classic header.php.
 */
function enqueue_compat_wp4_styles() {
	// See https://wordpress.slack.com/archives/C02QB8GMM/p1642056619063500
	if (
		( defined( 'FEATURE_2021_GLOBAL_HEADER_FOOTER' ) && ! FEATURE_2021_GLOBAL_HEADER_FOOTER ) &&
		( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST )
	) {
		return;
	}

	if (
		( ! wp_is_block_theme() && ! current_theme_supports( 'wp4-styles' ) ) ||
		( defined( 'REST_REQUEST' ) && REST_REQUEST )
	) {
		$suffix = is_rtl() ? '-rtl' : '';

		wp_register_style(
			'wp4-styles',
			'https://s.w.org/style/wp4' . $suffix . '.css',
			array( 'open-sans' ),
			filemtime( WPORGPATH . '/style/wp4' . $suffix . '.css' )
		);

		wp_enqueue_style( 'wp4-styles' );
	}
}

/**
 * Unregister the `global-styles` from classic themes, to avoid overwriting our custom properties.
 */
function unregister_classic_global_styles() {
	if ( wp_is_block_theme() ) {
		return;
	}

	wp_dequeue_style( 'global-styles' );
	wp_deregister_style( 'global-styles' );
}

/**
 * Remove the wrapping element to preserve markup.
 *
 * Core and Gutenberg add a wrapper `div` for backwards-compatibility, but that is unnecessary here, and breaks
 * CSS selectors.
 *
 * @see restore_inner_group_container()
 */
function remove_inner_group_container() {
	if ( wp_is_block_theme() ) {
		return;
	}

	remove_filter( 'render_block', 'wp_restore_group_inner_container' );
	remove_filter( 'render_block', 'gutenberg_restore_group_inner_container' );
}

/**
 * Restore the wrapping element to prevent side-effects on the content area.
 *
 * @see remove_inner_group_container()
 */
function restore_inner_group_container() {
	if ( wp_is_block_theme() ) {
		return;
	}

	if ( function_exists( 'gutenberg_restore_group_inner_container' ) ) {
		add_filter( 'render_block', 'gutenberg_restore_group_inner_container', 10, 2 );
	} else {
		add_filter( 'render_block', 'wp_restore_group_inner_container', 10, 2 );
	}
}

/**
 * Render the global header via a REST request.
 *
 * @return string
 */
function rest_render_global_header( $request ) {

	// Remove the theme stylesheet from rest requests.
	add_filter( 'wp_enqueue_scripts', function() {
		remove_theme_support( 'wp4-styles' );

		wp_dequeue_style( 'wporg-style' );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'open-sans' );
	}, 20 );

	// Serve the request as HTML.
	add_filter( 'rest_pre_serve_request', function( $served, $result ) {
		header( 'Content-Type: text/html' );
		header( 'X-Robots-Tag: noindex, follow' );

		echo $result->get_data();

		return true;
	}, 10, 2 );

	return render_global_header();
}

/**
 * Render the global header via a REST request for the Codex with appropriate tags.
 *
 * @return string
 */
function rest_render_codex_global_header( $request ) {
	add_action( 'wp_head', function() {
		echo '<!-- [codex head meta] -->', "\n";
	}, 1 );

	add_action( 'wp_head', function() {
		echo '<!-- [codex head scripts] -->', "\n";
	}, 100 );

	add_filter( 'body_class', function( $class ) {
		return [
			'wporg-responsive',
			'wporg-codex'
		];
	} );

	wp_enqueue_style( 'codex-wp4', 'https://s.w.org/style/codex-wp4.css', array( 'wp4-styles' ), 4 );

	// Remove <title> tags.
	remove_theme_support( 'title-tag' );

	$markup = rest_render_global_header( $request );
	$markup = preg_replace( '!<html[^>]+>!i', '<!-- [codex head html] -->', $markup );

	return $markup;
}

/**
 * Render the global header via a REST request for use with Planet.
 *
 * @return string
 */
function rest_render_planet_global_header( $request ) {
	add_filter( 'pre_get_document_title', function() {
		return 'Planet &mdash; WordPress.org';
	} );

	add_filter( 'wporg_canonical_url', function() {
		return 'https://planet.wordpress.org/';
	} );

	add_filter( 'body_class', function( $class ) {
		return [
			'wporg-responsive',
			'wporg-planet'
		];
	} );

	return rest_render_global_header( $request );
}

/**
 * Render the global header in a block context.
 *
 * @return string
 */
function render_global_header() {
	remove_inner_group_container();

	if ( is_rosetta_site() ) {
		$menu_items   = get_rosetta_menu_items();
		$locale_title = get_rosetta_name();
		$show_search  = false;
	} else {
		$menu_items   = get_global_menu_items();
		$locale_title = '';
		$show_search  = true;
	}

	// The mobile Get WordPress button needs to be in both menus.
	$menu_items[] = array(
		'title'   => esc_html_x( 'Get WordPress', 'Menu item title', 'wporg' ),
		'url'     => get_download_url(),
		'type'    => 'custom',
		'classes' => 'global-header__mobile-get-wordpress global-header__get-wordpress',
	);

	$menu_items = set_current_item_class( $menu_items );

	/*
	 * Render the block mockup first, in case anything in that process adds hooks to `wp_head`.
	 * Allow multiple includes to allow for the double `site-header-offset` workaround.
	 */
	ob_start();
	require __DIR__ . '/header.php';
	$markup = do_blocks( ob_get_clean() );

	restore_inner_group_container();

	$is_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;

	/*
	 * Render the classic markup second, so the `wp_head()` call will execute callbacks that blocks added above.
	 *
	 * API requests also need `<head>` etc so they can get the styles.
	 */
	if ( ! wp_is_block_theme() || $is_rest_request ) {
		ob_start();
		require __DIR__ . '/classic-header.php';
		$markup = ob_get_clean() . $markup;
	}

	return $markup;
}

/**
 * Determine if the current site is a Rosetta site (e.g., `es-mx.wordpress.org`).
 *
 * This returns `false` for `translate.wordpress.org`; it's part of the Rosetta network, but isn't a Rosetta site.
 *
 * @return bool
 */
function is_rosetta_site() {
	global $rosetta;

	return $rosetta instanceof Rosetta_Sites;
}

/**
 * Get the standard items for the global header menu.
 *
 * These are used on all sites, except Rosetta.
 *
 * @return array[]
 */
function get_global_menu_items() {
	$global_items = array(
		array(
			'title' => esc_html_x( 'Plugins', 'Menu item title', 'wporg' ),
			'url'   => 'https://wordpress.org/plugins/',
			'type'  => 'custom',
		),

		array(
			'title' => esc_html_x( 'Themes', 'Menu item title', 'wporg' ),
			'url'   => 'https://wordpress.org/themes/',
			'type'  => 'custom',
		),

		array(
			'title' => esc_html_x( 'Patterns', 'Menu item title', 'wporg' ),
			'url'   => 'https://wordpress.org/patterns/',
			'type'  => 'custom',
		),

		array(
			'title' => esc_html_x( 'Learn', 'Menu item title', 'wporg' ),
			'url'   => 'https://learn.wordpress.org/',
			'type'  => 'custom',
		),

		array(
			'title' => esc_html_x( 'Support', 'Menu item title', 'wporg' ),
			'url'   => 'https://wordpress.org/support/',
			'type'  => 'custom',

			'submenu' => array(
				array(
					'title' => esc_html_x( 'Documentation', 'Menu item title', 'wporg' ),
					'url'   => 'https://wordpress.org/support/',
					'type'  => 'custom',
				),
				array(
					'title' => esc_html_x( 'Forums', 'Menu item title', 'wporg' ),
					'url'   => 'https://wordpress.org/support/forums/',
					'type'  => 'custom',
				),
			),
		),

		array(
			'title'   => esc_html_x( 'News', 'Menu item title', 'wporg' ),
			'url'     => 'https://wordpress.org/news/',
			'type'    => 'custom',
		),

		array(
			'title' => esc_html_x( 'About', 'Menu item title', 'wporg' ),
			'url'   => 'https://wordpress.org/about/',
			'type'  => 'custom',
		),

		array(
			'title' => esc_html_x( 'Get Involved', 'Menu item title', 'wporg' ),
			'url'   => 'https://make.wordpress.org/',
			'type'  => 'custom',

			'submenu' => array(
				array(
					'title' => esc_html_x( 'Five for the Future', 'Menu item title', 'wporg' ),
					'url'   => 'https://wordpress.org/five-for-the-future/',
					'type'  => 'custom',
				),
			),
		),

		array(
			'title' => esc_html_x( 'Showcase', 'Menu item title', 'wporg' ),
			'url'   => 'https://wordpress.org/showcase/',
			'type'  => 'custom',
		),

		array(
			'title' => esc_html_x( 'Mobile', 'Menu item title', 'wporg' ),
			'url'   => 'https://wordpress.org/mobile/',
			'type'  => 'custom',
		),

		array(
			'title' => esc_html_x( 'Hosting', 'Menu item title', 'wporg' ),
			'url'   => 'https://wordpress.org/hosting/',
			'type'  => 'custom',
		),

		array(
			'title' => esc_html_x( 'Openverse', 'Menu item title', 'wporg' ),
			'url'   => 'https://wordpress.org/openverse/',
			'type'  => 'custom',
		),
	);

	return $global_items;
}

/**
 * Rosetta sites each have their own menus, rather than using the global menu items.
 *
 * It's a combination of the items that site admins add to the "Rosetta" menu, and some items that are added
 * programmatically to all sites.
 *
 * @return array[]
 */
function get_rosetta_menu_items() : array {
	/** @var Rosetta_Sites $rosetta */
	global $rosetta;

	switch_to_blog( $rosetta->get_root_site_id() );

	// `Rosetta_Sites::wp_nav_menu_objects` sometimes removes redundant items, but sometimes returns early.
	$database_items = wp_get_nav_menu_items( get_nav_menu_locations()['rosetta_main'] );
	$database_items = array_filter( $database_items, __NAMESPACE__ . '\is_valid_rosetta_menu_item' );

	$mock_args                    = (object) array( 'theme_location' => 'rosetta_main' );
	$database_and_hardcoded_items = $rosetta->wp_nav_menu_objects( $database_items, $mock_args );
	$normalized_items             = normalize_rosetta_items( $database_and_hardcoded_items );

	restore_current_blog();

	return $normalized_items;
}

/**
 * Fetch the Rosetta site name.
 *
 * @return string
 */
function get_rosetta_name() : string {
	/** @var Rosetta_Sites $rosetta */
	global $rosetta;

	return get_blog_option( $rosetta->get_root_site_id(), 'blogname' );
}

/**
 * Determines if a Rosetta menu item is valid.
 *
 * Some items saved in Rosetta nav menus are redundant, because the global header already includes Download and
 * Home links (via the logo).
 *
 * @param WP_Post $menu_item
 *
 * @return bool
 */
function is_valid_rosetta_menu_item( $item ) {
	/*
	 * Cover full URLs like `https://ar.wordpress.org/` and `https://ar.wordpress.org/download/`; and relative
	 * ones like  `/` and `/download/`.
	 */
	$redundant_slugs = array( '/download/', '/txt-download/', '/', "/{$_SERVER['HTTP_HOST']}/" );

	// Not using `basename()` because that would match `/foo/download`
	$irrelevant_url_parts = array( 'http://', 'https://', $_SERVER['HTTP_HOST'] );

	$item_slug = str_replace( $irrelevant_url_parts, '', $item->url );
	$item_slug = trailingslashit( $item_slug );

	return ! in_array( $item_slug, $redundant_slugs, true );
}

/**
 * Normalize the data to be consistent with the format of `get_global_menu_items()`.
 *
 * @param object[] $rosetta_items Some are `WP_Post`, and some are `stdClass` that are mocking a `WP_Post`.
 *
 * @return array
 */
function normalize_rosetta_items( $rosetta_items ) {
	$normalized_items = array();
	$parent_indices   = array();

	// Standardise the menu classes.
	foreach ( $rosetta_items as $index => $item ) {
		$rosetta_items[ $index ]->classes  = implode( ' ', (array) $item->classes );
	}

	// Assign the top-level menu items.
	foreach ( $rosetta_items as $index => $item ) {
		$top_level_item = empty( $item->menu_item_parent );

		if ( ! $top_level_item ) {
			continue;
		}

		// Track the indexes of parent items, so the submenu can be built later on.
		$parent_indices[ $item->ID ] = $index;
		$normalized_items[ $index ]  = (array) $item;
	}

	// Add all submenu items.
	foreach ( $rosetta_items as $index => $item ) {
		$top_level_item = empty( $item->menu_item_parent );

		if ( $top_level_item ) {
			continue;
		}

		// Page has a parent that is not in the menu?
		if ( ! isset( $parent_indices[ $item->menu_item_parent ] ) ) {
			continue;
		}

		$parent_index = $parent_indices[ $item->menu_item_parent ];

		$normalized_items[ $parent_index ]['submenu'][] = array(
			'title' => $item->title,
			'url'   => $item->url,
			'type'  => $item->type,
		);
	}

	return $normalized_items;
}

/**
 * Retrieve the URL of the home page.
 *
 * Most of the time it will just be `w.org/`, but Rosetta sites use the URL of the "root site" homepage.
 */
function get_home_url() {
	/** @var Rosetta_Sites $rosetta */
	global $rosetta;

	$url = false;

	if ( is_rosetta_site() ) {
		$root_site = $rosetta->get_root_site_id();
		$url       = \get_home_url( $root_site, '/' );
	}

	if ( ! $url ) {
		$url = 'https://wordpress.org/';
	}

	return $url;
}

/**
 * Retrieve the URL to download WordPress.
 *
 * Rosetta sites sometimes have a localized page, rather than the main English one.
 *
 * @todo Make DRY with `Rosetta_Sites::wp_nav_menu_objects()` and `WordPressdotorg\MainTheme\get_downloads_url()`.
 * There are some differences between these three that need to be reconciled, though.
 */
function get_download_url() {
	/** @var Rosetta_Sites $rosetta */
	global $rosetta;

	$url = false;

	if ( is_rosetta_site() ) {
		$root_site = $rosetta->get_root_site_id();

		switch_to_blog( $root_site );

		$download = get_page_by_path( 'download' );

		if ( ! $download ) {
			$download = get_page_by_path( 'txt-download' );
		}
		if ( ! $download ) {
			$download = get_page_by_path( 'releases' );
		}

		if ( $download ) {
			$url = get_permalink( $download );
		}

		restore_current_blog();
	}

	if ( ! $url ) {
		$url = 'https://wordpress.org/downloads/';
	}

	return $url;
}

/**
 * Render the global footer via a REST request.
 *
 * @return string
 */
function rest_render_global_footer( $request ) {

	/*
	 * Render the header but discard the markup, so that any header styles/scripts
	 * required are then available for output in the footer.
	 */
	render_global_header();

	// Serve the request as HTML
	add_filter( 'rest_pre_serve_request', function( $served, $result ) {
		header( 'Content-Type: text/html' );
		header( 'X-Robots-Tag: noindex, follow' );

		echo $result->get_data();

		return true;
	}, 10, 2 );

	return render_global_footer();
}

/**
 * Render the global footer in a block context.
 *
 * @return string
 */
function render_global_footer() {
	remove_inner_group_container();

	if ( is_rosetta_site() ) {
		$locale_title = get_rosetta_name();
		add_filter( 'render_block_data', __NAMESPACE__ . '\localize_nav_links' );
	} else {
		$locale_title = '';
	}

	// Render the block mockup first, because `wp_render_layout_support_flag()` adds callbacks to `wp_footer`.
	ob_start();
	require_once __DIR__ . '/footer.php';
	$markup = do_blocks( ob_get_clean() );

	restore_inner_group_container();

	$is_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;

	// Render the classic markup second, so the `wp_footer()` call will execute callbacks that blocks added.
	if ( ! wp_is_block_theme() || $is_rest_request ) {
		ob_start();
		require_once __DIR__ . '/classic-footer.php';
		$markup .= ob_get_clean();
	}

	remove_filter( 'render_block_data', __NAMESPACE__ . '\localize_nav_links' );

	return $markup;
}

/**
 * Localise a `core/navigation-link` block link to point to the Rosetta site resource.
 *
 * Unfortunately WordPress doesn't have a block-specific pre- filter, only a block-specific post-filter.
 * That's why we specifically check for the blockName here.
 *
 * @param array $block The parsed block data.
 *
 * @return array
 */
function localize_nav_links( $block ) {
	if (
		! empty( $block['blockName'] ) &&
		'core/navigation-link' === $block['blockName'] &&
		! empty( $block['attrs']['url'] )
	) {
		$block['attrs']['url'] = get_localized_footer_link( $block['attrs']['url'] );
	}

	return $block;
}

/**
 * Get a localized variant of a link included in the global footer.
 *
 * @param string $url The URL as it is in the menu.
 *
 * @return string Replacement URL, which may be localised.
 */
function get_localized_footer_link( $url ) {
	global $rosetta;
	if ( empty( $rosetta->current_site_domain ) ) {
		return $url;
	}

	switch ( $url ) {
		case 'https://wordpress.org/showcase/':
		case 'https://wordpress.org/hosting/':
			return $url;

		case 'https://wordpress.org/support/':
			// Check if support forum exists.
			if ( ! $rosetta->has_support_forum() ) {
				return $url;
			}
			break;

		case 'https://learn.wordpress.org/':
			return add_query_arg( 'locale', get_locale(), $url );
	}

	return str_replace( 'https://wordpress.org/', 'https://' . $rosetta->current_site_domain . '/', $url );
}

/**
 * Check if the current site is part of the w.org network.
 *
 * These blocks are used on some sites (like profiles.w.org) that are running WP, but in a different network.
 * In those sites, some things need to behave differently (e.g., because `switch_to_blog()` wouldn't work).
 */
function is_wporg_network() {
	return defined( 'WPORGPATH' ) && 0 === strpos( $_SERVER['SCRIPT_FILENAME'], WPORGPATH );
}

/**
 * Render the global styles via a REST request.
 *
 * @return string
 */
function rest_render_global_styles( $request ) {
	// Serve the request as CSS.
	add_filter( 'rest_pre_serve_request', function( $served, $result ) {
		header( 'Content-Type: text/css' );
		header( 'X-Robots-Tag: noindex, follow' );

		echo $result->get_data();

		return true;
	}, 10, 2 );

	return get_global_styles();
}

/**
 * Output just the variables generated by `theme.json` from the News site.
 *
 * This will let other themes on the network use the variables, without also
 * loading in the block & element styling.
 *
 * @see `wp_get_global_stylesheet()`
 */
function get_global_styles() {
	/*
	 * The block is used on some sites (like profiles.w.org) that are running WP, but in a different
	 * network. On those sites, things like `switch_to_blog()` won't work, so they need to use the API.
	 */
	if ( ! is_wporg_network() ) {
		return fetch_global_styles();
	}

	// Switch to `w.org/news` to generate correct theme properties.
	switch_to_blog( WPORG_NEWS_BLOGID );

	// Clear the static `$theme` property, which is set by the current (classic theme) site.
	WP_Theme_JSON_Resolver::clean_cached_data();

	$styles = wp_get_global_stylesheet( [ 'variables', 'presets' ] );
	// Also set the block-gap style, which isn't technically a theme variable.
	$styles .= 'body { --wp--style--block-gap: 24px; }';

	// Restore to current site.
	restore_current_blog();
	WP_Theme_JSON_Resolver::clean_cached_data();

	return $styles;
}

/**
 * Fetch the global styles via the API endpoint
 *
 * @see `get_global_styles()` for background.
 */
function fetch_global_styles() {
	$cache_key = 'global_header_styles';
	$styles    = get_transient( $cache_key );

	if ( ! $styles ) {
		$styles       = '';
		$request_args = array();

		// Route request to sandbox when testing.
		if ( 'staging' === wp_get_environment_type() ) {
			$hostname                        = '127.0.0.1';
			$request_args['headers']['host'] = 'wordpress.org';

			/*
			 * It's expected that the sandbox hostname won't be valid. This is safe because we're only connecting
			 * to `127.0.0.1`.
			 */
			$request_args['sslverify'] = false;

		} else {
			$hostname = 'wordpress.org';
		}

		$response      = wp_remote_get( "https://$hostname/wp-json/global-header-footer/v1/styles", $request_args );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) || 200 !== $response_code ) {
			trigger_error( "Fetching global styles failed.", E_USER_WARNING );

		} else {
			$styles = wp_remote_retrieve_body( $response );

			set_transient( $cache_key, $styles, HOUR_IN_SECONDS );
		}
	}

	return $styles;
}

/**
 * Set the menu active state for the currently selected menu item.
 *
 * @param array $menu_items The menu menu items.
 *
 * @return array The altered menu items.
 */
function set_current_item_class( $menu_items ) {
	$current_url = get_menu_url_for_current_page( $menu_items );

	foreach ( $menu_items as & $item ) {
		$sub = false;
		if ( ! empty( $item['submenu'] ) ) {
			foreach ( $item['submenu'] as & $subitem ) {
				if ( $current_url === $subitem['url'] ) {
					$subitem['classes'] = trim( ( $subitem['classes'] ?? '' ) . ' current-menu-item' );
					$sub                = true;
					break;
				}
			}
		}

		if ( $sub || $current_url === $item['url'] ) {
			$item['classes'] = trim( ( $item['classes'] ?? '' ) . ' current-menu-item' );
		}
	}

	return $menu_items;
}

/**
 * Determine the menu item which best describes the current request.
 *
 * @param array $menu_items The menu menu items.
 *
 * @return string
 */
function get_menu_url_for_current_page( $menu_items ) {
	$host    = strtolower( $_SERVER['HTTP_HOST'] );
	$uri     = strtolower( $_SERVER['REQUEST_URI'] );
	$compare = "https://{$host}{$uri}";

	if ( 'translate.wordpress.org' === $host ) {
		return 'https://make.wordpress.org/';
	}

	if ( 'developer.wordpress.org' === $host ) {
		// DevHub doesn't exist within the menu.
		return '';
	}

	// Is it the Global Search?
	if ( str_starts_with( $compare, 'https://wordpress.org/search/' ) ) {
		if ( isset( $_GET['in'] ) ) {
			if ( 'support_docs' === $_GET['in'] ) {
				return 'https://wordpress.org/support/';
			} elseif ( 'developer_documentation' === $_GET['in'] ) {
				// DevHub doesn't exist within the menu.
				return '';
			}
		}

		return 'https://wordpress.org/support/forums/';
	}

	// Select the correct Support menu item.
	if ( str_starts_with( $uri, '/support/' ) ) {
		// Documentation => /$, /article/*, /wordpress-version/*
		// Forums => Everything else.

		if (
			'/support/' === $uri ||
			str_starts_with( $uri, '/support/article/' ) ||
			str_starts_with( $uri, '/support/wordpress-version/' ) ||
			str_starts_with( $uri, '/support/category/' )
		) {
			$compare = "https://{$host}/support/";
		} else {
			$compare = "https://{$host}/support/forums/";
		}
	}

	// Extract all URLs, toplevel and child.
	$urls = [];
	array_walk_recursive(
		$menu_items,
		function( $val, $key ) use ( &$urls ) {
			if ( 'url' === $key ) {
				$urls[] = $val;
			}
		}
	);

	// Sort long to short, we need the deepest path to match.
	usort( $urls, function( $a, $b ) {
		return strlen( $b ) - strlen( $a );
	} );

	foreach ( $urls as $url ) {
		if ( str_starts_with( $compare, $url ) ) {
			return $url;
		}
	}

	return home_url('/');
}

/**
 * Replace the current submenu down-arrow with a custom icon.
 *
 * @param string $block_content The block content about to be appended.
 * @return string The filtered block content.
 */
function swap_submenu_arrow_svg( $block_content ) {
	return str_replace( block_core_navigation_link_render_submenu_icon(), "<svg width='10' height='7' viewBox='0 0 10 7' stroke-width='1.2' xmlns='http://www.w3.org/2000/svg'><path d='M0.416667 1.33325L5 5.49992L9.58331 1.33325'></path></svg>", $block_content );
}

/**
 * Replace the search action url with the custom attribute.
 *
 * @param string $block_content The block content about to be appended.
 * @param array  $block         The block details.
 * @return string The filtered block content.
 */
function swap_header_search_action( $block_content, $block ) {
	if ( ! empty( $block['attrs']['formAction'] ) ) {
		$block_content = str_replace(
			'action="' . esc_url( home_url( '/' ) ) . '"',
			'action="' . esc_url( $block['attrs']['formAction'] ) . '"',
			$block_content
		);
	}

	return $block_content;
}
