<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\register_block_types' );
add_action( 'enqueue_block_assets', __NAMESPACE__ . '\register_block_types_js' );
add_filter( 'pre_set_transient_global_styles_wporg-news-2021', __NAMESPACE__ . '\save_dependent_global_styles' );


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

	wp_enqueue_script(
		'wporg-global-header-script',
		plugins_url( '/js/wporg-global-header-script.js', __FILE__ ),
		array(),
		filemtime( __DIR__ . '/js/wporg-global-header-script.js' ),
		true
	);

	register_block_type(
		'wporg/global-header',
		array(
			'title'           => 'Global Header',
			'render_callback' => __NAMESPACE__ . '\render_global_header',
			'style'           => 'wporg-global-header-footer',
			'editor_style'    => 'wporg-global-header-footer',
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
 * Remove the wrapping element to preserve markup.
 *
 * Core and Gutenberg add a wrapper `div` for backwards-compatibility, but that is unnecessary here, and breaks
 * CSS selectors.
 *
 * @see restore_inner_group_container()
 */
function remove_inner_group_container() {
	if ( ! wp_is_block_theme() ) {
		remove_filter( 'render_block', 'wp_restore_group_inner_container' );
		remove_filter( 'render_block', 'gutenberg_restore_group_inner_container' );
	}
}

/**
 * Restore the wrapping element to prevent side-effects on the content area.
 *
 * @see remove_inner_group_container()
 */
function restore_inner_group_container() {
	if ( ! wp_is_block_theme() ) {
		if ( function_exists( 'gutenberg_restore_group_inner_container' ) ) {
			add_filter( 'render_block', 'gutenberg_restore_group_inner_container', 10, 2 );
		} else {
			add_filter( 'render_block', 'wp_restore_group_inner_container', 10, 2 );
		}
	}
}

/**
 * Render the global header in a block context.
 *
 * @return string
 */
function render_global_header() {
	remove_inner_group_container();

	/*
	 * Render the block mockup first, in case anything in that process adds hooks to `wp_head`.
	 * Allow multiple includes to allow for the double `site-header-offset` workaround.
	 */
	ob_start();
	require __DIR__ . '/header.php';
	$markup = do_blocks( ob_get_clean() );

	restore_inner_group_container();

	// Render the classic markup second, so the `wp_head()` call will execute callbacks that blocks added.
	if ( ! wp_is_block_theme() ) {
		ob_start();
		require __DIR__ . '/classic-header.php';
		$markup = ob_get_clean() . $markup;
	}

	return $markup;
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

	// Render the classic markup second, so the `wp_footer()` call will execute callbacks that blocks added.
	if ( ! wp_is_block_theme() ) {
		ob_start();
		require_once __DIR__ . '/classic-footer.php';
		$markup .= ob_get_clean();
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
	 * There could be multiple sites running the News theme -- like `/news` and `/news-test` -- so make sure that
	 * only the production styles are used.
	 */
	if ( 706 === get_current_blog_id() ) { // `w.org/news-test`. TODO change this to `w.org/news` when the redesign launches.
		return $news_transient_value;
	}

	$network_option_value = get_network_option( 1, 'global-header-footer-dependent-styles' );

	if ( $network_option_value !== $news_transient_value ) {
		update_network_option( 1, 'global-header-footer-dependent-styles', $news_transient_value );
	}

	// We don't want to change the value, using this filter is just a way to access it when it changes.
	return $news_transient_value;
}
