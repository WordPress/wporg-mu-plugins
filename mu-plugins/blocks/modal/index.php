<?php
/**
 * Block Name: Modal
 * Description: A container hidden behind a button, which pops up on click.
 *
 * This contains 3 variations on this concept:
 * - Modal: The container that floats in the middle of the screen. Clicking
 *   outside the container or hitting escape will close it.
 * - Popover: The container stays attached to the toggle button but overlaps
 *   the content, like a dropdown menu. Clicking outside the container or
 *   hitting escape will close it.
 * - Collapsed (inline): The container is hidden by default, but when expanded,
 *   pushes the content below down to not overlap. Only closed by clicking the
 *   toggle, to prevent content jumps.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Modal_Block;

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
		__DIR__ . '/build/modal',
		array(
			'render_callback' => __NAMESPACE__ . '\render',
		)
	);
	register_block_type(
		__DIR__ . '/build/inner-content',
		array(
			'render_callback' => __NAMESPACE__ . '\render_inner_content',
		)
	);
}

/**
 * Returns a local SVG icon.
 *
 * @param string $icon Name of the icon to render, corresponds to file name.
 * @return string
 */
function render_icon( $icon ) {
	$file_path = __DIR__ . '/icons/' . $icon . '.svg';
	if ( file_exists( $file_path ) ) {
		return file_get_contents( $file_path );
	}
}

/**
 * Render the block content for the modal/popover/inline container.
 *
 * The modal requires more HTML for micromodal support, but inline and popover
 * use the same markup with slightly different CSS.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the block markup.
 */
function render_inner_content( $attributes, $content, $block ) {
	// Fetch the type from the parent block.
	$type = $block->context['wporg/modal/type'] ?? '';
	if ( ! $type ) {
		return;
	}

	if ( 'inline' === $type || 'popover' === $type ) {
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'wporg-modal__modal alignwide' ) );
		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			$content
		);
	}

	$wrapper_attributes = get_block_wrapper_attributes();
	$close_icon = render_icon( 'close' );

	return <<<HTML
<div class="wporg-modal__modal" aria-hidden="true">
	<div tabindex="-1" class="wporg-modal__overlay" data-micromodal-close>
		<div class="wporg-modal__container" role="dialog" aria-modal="true">
			<div {$wrapper_attributes}>
				<button class="wporg-modal__button" aria-label="Close" data-micromodal-close>{$close_icon}</button>
				{$content}
			</div>
		</div>
	</div>
</div>
HTML;
}

/**
 * Render the block content for the parent Modal block (button). The modal
 * container itself is rendered by the child block.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the block markup.
 */
function render( $attributes, $content, $block ) {
	$type = $attributes['type'];
	$class = 'is-type-' . $type;

	// Replace the button block's link with an HTML button, retain all block styles.
	// This only replaces the first link, so the modal can still contain links/buttons.
	if ( preg_match( '/(<a([^>]*)>)(.*?)(<\/a>)/i', $content, $matches ) ) {
		$link = $matches[0]; // full match.
		// Add in the modal button class, used by the view script.
		$attributes = str_replace( 'class="', 'class="wporg-modal__button ', $matches[2] );
		$button = sprintf(
			'<button aria-expanded="false" %1$s>%2$s</button>',
			$attributes,
			$matches[3] // innerText.
		);
		$content = str_replace( $link, $button, $content );
	}

	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $class ) );
	return sprintf(
		'<div %1$s>%2$s</div>',
		$wrapper_attributes,
		$content
	);
}
