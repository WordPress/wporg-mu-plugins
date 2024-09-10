<?php
namespace WordPressdotorg\MU_Plugins\Chapter_List;

defined( 'WPINC' ) || die();

require_once __DIR__ . '/class-chapter-walker.php';

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
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
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}

	$post_id   = $block->context['postId'];
	$post_type = get_post_type( $post_id );

	$args = array(
		'title_li'    => '',
		'echo'        => 0,
		'sort_column' => 'menu_order, title',
		'post_type'   => $post_type,

		// Use custom walker that excludes display of orphaned pages (an ancestor
		// of such a page is likely not published and thus this is not accessible).
		'walker'      => new Chapter_Walker(),
	);

	$post_type_obj = get_post_type_object( $post_type );

	if ( $post_type_obj && current_user_can( $post_type_obj->cap->read_private_posts ) ) {
		$args['post_status'] = array( 'publish', 'private' );
	}

	$content = wp_list_pages( $args );

	$header = '<div class="wporg-chapter-list__header">';
	$header .= do_blocks(
		'<!-- wp:heading {"style":{"typography":{"fontStyle":"normal","fontWeight":"400"}},"fontSize":"normal","fontFamily":"inter"} -->
		<h2 class="wp-block-heading has-inter-font-family has-normal-font-size" style="font-style:normal;font-weight:400">' . esc_html__( 'Chapters', 'wporg' ) . '</h2>
		<!-- /wp:heading -->'
	);
	$header .= '<button type="button" class="wporg-chapter-list__toggle" aria-expanded="false">';
	$header .= '<span class="screen-reader-text">' . esc_html__( 'Chapter list', 'wporg' ) . '</span>';
	$header .= '</button>';
	$header .= '</div>';

	$wrapper_attributes = get_block_wrapper_attributes();
	return sprintf(
		'<aside %1$s><nav>%2$s<ul class="wporg-chapter-list__list">%3$s</ul></nav></aside>',
		$wrapper_attributes,
		$header,
		$content
	);
}
