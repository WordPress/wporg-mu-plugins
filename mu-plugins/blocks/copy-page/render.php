<?php
/**
 * Output the copy page block.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Copy_Page_Block;

use function WordPressdotorg\MU_Plugins\Helpers\is_markdown_request;

defined( 'WPINC' ) || die();

// A copy button has no meaning inside the Markdown rendering itself.
if ( is_markdown_request() ) {
	return '';
}

$markdown_url = get_markdown_url();
if ( ! $markdown_url ) {
	return '';
}

$has_view_link = $attributes['hasViewLink'] ?? true;

// Manually enqueue this script, so that it's available for the interactivity view script.
wp_enqueue_script( 'wp-a11y' );

$labels = array(
	'copy'       => __( 'Copy page', 'wporg' ),
	'copied'     => __( 'Copied', 'wporg' ),
	'error'      => __( 'Copy failed', 'wporg' ),
	'copyA11y'   => __( 'Copy page as Markdown', 'wporg' ),
	'copiedA11y' => __( 'Page copied to the clipboard as Markdown.', 'wporg' ),
	'errorA11y'  => __( 'The page could not be copied. Try the View as Markdown link instead.', 'wporg' ),
);

// Context holds the values that change or differ per block; everything the
// markup binds to is derived from `status` so the server and client can't disagree.
$context = array(
	'url'    => $markdown_url,
	'status' => 'idle',
	'label'  => $labels,
);

// Server-side derived state, mirrored by the getters in view.js.
wp_interactivity_state(
	'wporg/copy-page',
	array(
		'isLoading' => fn() => 'loading' === wp_interactivity_get_context()['status'],
		'isCopied'  => fn() => 'copied' === wp_interactivity_get_context()['status'],
		'isError'   => fn() => 'error' === wp_interactivity_get_context()['status'],
		'labelText' => function () {
			$ctx = wp_interactivity_get_context();
			return $ctx['label'][ get_label_key( $ctx['status'] ) ];
		},
		'ariaLabel' => function () {
			$ctx = wp_interactivity_get_context();
			return $ctx['label'][ get_label_key( $ctx['status'] ) . 'A11y' ];
		},
	)
);

?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>
	data-wp-interactive="wporg/copy-page"
	<?php echo wp_interactivity_data_wp_context( $context ); // phpcs:ignore ?>
	data-wp-class--is-loading="state.isLoading"
	data-wp-class--is-copied="state.isCopied"
	data-wp-class--is-error="state.isError"
>
	<button
		type="button"
		class="wporg-copy-page__button"
		aria-label="<?php echo esc_attr( $labels['copyA11y'] ); ?>"
		data-wp-on--click="actions.copy"
		data-wp-bind--aria-label="state.ariaLabel"
		data-wp-bind--aria-busy="state.isLoading"
	>
		<span class="wporg-copy-page__icon" aria-hidden="true">
			<svg class="is-icon-copy" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" focusable="false">
				<rect x="4.75" y="4.75" width="11.5" height="11.5" rx="0.875" />
				<path d="M19.25 8.281V17.281C19.25 18.3692 18.3546 19.25 17.251 19.25H6.25" />
			</svg>
			<svg class="is-icon-copied" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" focusable="false">
				<path d="M7 12L10 15L17 8" />
			</svg>
		</span>
		<span class="wporg-copy-page__label" data-wp-text="state.labelText"><?php echo esc_html( $labels['copy'] ); ?></span>
	</button>
	<?php if ( $has_view_link ) : ?>
	<a class="wporg-copy-page__link" href="<?php echo esc_url( $markdown_url ); ?>">
		<span class="wporg-copy-page__icon" aria-hidden="true">
			<svg class="is-icon-markdown" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" focusable="false">
				<rect x="3.25" y="5.25" width="17.5" height="13.5" rx="1.25" />
				<path d="M6.75 15.5v-6l2.75 3 2.75-3v6M16.5 8.5v6.5M14.5 13l2 2 2-2" />
			</svg>
		</span>
		<span class="wporg-copy-page__label"><?php esc_html_e( 'View as Markdown', 'wporg' ); ?></span>
	</a>
	<?php endif; ?>
</div>
