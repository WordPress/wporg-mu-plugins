<?php
if ( ! isset( $attributes['src'] ) ) {
	return '';
}

$view_url = wp_http_validate_url( $attributes['src'] );
if ( ! $view_url ) {
	return '';
}

$alt_text = $attributes['alt'] ?? '';
$has_link = isset( $attributes['href'] ) && $attributes['href'];
$width = isset( $attributes['width'] ) ? $attributes['width'] * 2 : 800;
$viewport_width = $attributes['viewportWidth'] ?? 1200;

$cache_key = '20240423'; // To break out of cached image.

$view_url = add_query_arg( 'v', $cache_key, $view_url );
$url = add_query_arg(
	array(
		'w' => $width,
		'vpw' => $viewport_width,
		'vph' => 300, // Smaller than the vast majority of patterns to avoid whitespace.
		'screen_height' => 3600, // Max height of a screenshot.
	),
	'https://s0.wp.com/mshots/v1/' . urlencode( $view_url ),
);

// Initial state to pass to Interactivity API.
$init_state = [
	'base64Image' => '',
	'src' => esc_url( $url ),
	'alt' => $alt_text,
	'attempts' => 0,
	'shouldRetry' => true,
	'hasError' => false,
];
$encoded_state = wp_json_encode( $init_state );

$classname = '';
if ( $has_link ) {
	$classname .= ' is-linked-image';
}

?>
<div
	<?php echo get_block_wrapper_attributes( array( 'class' => $classname ) ); // phpcs:ignore ?>
	data-wp-interactive="wporg/screenshot-preview"
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
	data-wp-init="callbacks.init"
	data-wp-class--has-loaded="state.hasLoaded"
	data-wp-class--has-error="state.hasError"
	tabIndex="-1"
>
	<?php if ( $has_link ) : ?>
	<a href="<?php echo esc_url( $attributes['href'] ); ?>">
	<?php endif; ?>

	<div
		class="wporg-screenshot-preview__container"
		data-wp-class--wporg-screenshot-preview__loader="!state.hasLoaded"
		data-wp-class--wporg-screenshot-preview__error="state.hasError"
	>
		<img
			data-wp-bind--hidden="!state.base64Image"
			data-wp-bind--alt="context.alt"
			data-wp-bind--src="state.base64Image"
		/>
		<span
			data-wp-bind--hidden="state.base64Image"
			class="screen-reader-text"
			data-wp-text="context.alt"
		></span>
	</div>

	<?php if ( $has_link ) : ?>
	</a>
	<?php endif; ?>
</div>
