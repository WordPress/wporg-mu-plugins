<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer;
use Rosetta_Sites, WP_Post, WP_REST_Server;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\register_block_types' );
add_action( 'enqueue_block_assets', __NAMESPACE__ . '\register_block_types_js' );
add_filter( 'pre_set_transient_global_styles_wporg-news-2021', __NAMESPACE__ . '\save_dependent_global_styles' );
add_action( 'rest_api_init', __NAMESPACE__ . '\register_routes' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_compat_wp4_styles' );
add_action( 'wp_head', __NAMESPACE__ . '\preload_google_fonts' );
add_filter( 'style_loader_src', __NAMESPACE__ . '\update_google_fonts_url', 10, 2 );

/**
 * Register block types
 *
 * These are intentionally missing arguments like `title`, `category`, `icon`, etc, because we don't want them
 * showing up in the Block Inserter, regardless of which theme is running.
 */
function register_block_types() {
	wp_register_style(
		'wporg-global-header-footer',
		plugins_url( '/build/style.css', __FILE__ ),
		array( 'wp-block-library' ), // Load `block-library` styles first, so that our styles override them.
		filemtime( __DIR__ . '/build/style.css' )
	);

	wp_register_script(
		'wporg-global-header-script',
		plugins_url( '/js/wporg-global-header-script.js', __FILE__ ),
		array(),
		filemtime( __DIR__ . '/js/wporg-global-header-script.js' ),
		true
	);

	// Enqueue them for GlotPress sites. `register_block_type()` will enqueue them for regular WP sites.
	if ( function_exists( 'gp_enqueue_style' ) ) {
		gp_enqueue_style( 'wporg-global-header-footer' );
		gp_enqueue_script( 'wporg-global-header-script' );
	}

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
 * Register REST API routes, so non-WP applications can integrate it.
 */
function register_routes() {
	register_rest_route(
		'global-header-footer/v1',
		'header',
		array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => __NAMESPACE__ . '\render_global_header',
			),
		)
	);

	register_rest_route(
		'global-header-footer/v1',
		'footer',
		array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => __NAMESPACE__ . '\render_global_footer',
			),
		)
	);
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
	if ( ! current_theme_supports( 'wp4-styles' ) ) {
		$cdn_domain = defined( 'WPORG_SANDBOXED' ) && WPORG_SANDBOXED ? 'wordpress.org' : 's.w.org';
		$suffix = 'rtl' === $text_direction ? '-rtl' : '';
		wp_register_style(
			'wp4-styles',
			'https://' . $cdn_domain . '/style/wp4' . $suffix . '.css',
			array( 'open-sans' ),
			'95'
		);

		wp_enqueue_style( 'wp4-styles' );
	}
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
 * Render the global header in a block context.
 *
 * @return string
 */
function render_global_header() {
	remove_inner_group_container();

	if ( is_rosetta_site() ) {
		$menu_items = get_rosetta_menu_items();
	} else {
		$menu_items = get_global_menu_items();
	}

	// The mobile Get WordPress button needs to be in both menus.
	$menu_items[] = array(
		'title'   => esc_html_x( 'Get WordPress', 'Menu item title', 'wporg' ),
		'url'     => get_download_url(),
		'type'    => 'custom',
		'classes' => 'global-header__mobile-get-wordpress global-header__get-wordpress',
	);

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

	if ( $is_rest_request ) {
		header( 'Content-Type: text/html' );
		echo $markup;
		die(); // this is an ugly hack. todo get the api to return html
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
			'classes' => 'current-menu-item',
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

	foreach ( $rosetta_items as $index => $item ) {
		$top_level_item = empty( $item->menu_item_parent );
		$item->classes  = implode( ' ', $item->classes );

		if ( $top_level_item ) {
			// Track the indexes of parent items, so the submenu can be built later on.
			$parent_indices[ $item->ID ] = $index;
			$normalized_items[ $index ]  = (array) $item;

		} else {
			$parent_index = $parent_indices[ $item->menu_item_parent ];

			$normalized_items[ $parent_index ]['submenu'][] = array(
				'title' => $item->title,
				'url'   => $item->url,
				'type'  => $item->type,
			);
		}
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
		switch_to_blog( $root_site );

		$url = home_url();

		restore_current_blog();
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
 * Render the global footer in a block context.
 *
 * @return string
 */
function render_global_footer() {
	remove_inner_group_container();

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

	if ( $is_rest_request ) {
		header( 'Content-Type: text/html' );
		echo $markup;
		die(); // this is an ugly hack. todo get the api to return html
	}

	return $markup;
}

/**
 * Save the FSE global styles that the global header/footer depends on.
 *
 * The header/footer blocks are built primarily for block themes, but also need to work in Classic themes. The
 * styles that the News site generates are saved, so that they can later be loaded for Classic themes.
 *
 * @see `wp_get_global_stylesheet()`
 *
 * @param string $news_transient_value
 *
 * @return string
 */
function save_dependent_global_styles( $news_transient_value ) {
	/*
	 * This function is only called when updating styles for the News theme, but there could be multiple sites
	 * running it -- like `/news` and `/news-test` -- so make sure that only the production styles are saved.
	 *
	 * After a parent FSE theme is created, we may want to adjust this so that only the parent styles are saved.
	 * @see https://github.com/WordPress/wporg-news-2021/issues/13
	 */
	if ( 706 !== get_current_blog_id() ) { // `w.org/news-test`. TODO change this to `w.org/news` when the redesign launches.
		return $news_transient_value;
	}

	$network_option_value = get_network_option( 1, 'global-header-footer-dependent-styles' );

	if ( $network_option_value !== $news_transient_value ) {
		update_network_option( 1, 'global-header-footer-dependent-styles', $news_transient_value );
	}

	// We don't want to change the value, using this filter is just a way to access it when it changes.
	return $news_transient_value;
}
