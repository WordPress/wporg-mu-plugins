<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer;

defined( 'WPINC' ) || die();

add_action( 'init', __NAMESPACE__ . '\register_block_types' );
add_action( 'enqueue_block_assets', __NAMESPACE__ . '\register_block_types_js' );

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
		wp_get_theme()->get( 'Version' ),
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
 * Render the global header in a block context.
 *
 * @return string
 */
function render_global_header() {
	ob_start();

	// Allow multiple includes for the `site-header-offset` workaround.
	require __DIR__ . '/header.php';
	return do_blocks( ob_get_clean() );
}

/**
 * Render the global footer in a block context.
 *
 * @return string
 */
function render_global_footer() {
	ob_start();
	require_once __DIR__ . '/footer.php';
	return do_blocks( ob_get_clean() );
}
