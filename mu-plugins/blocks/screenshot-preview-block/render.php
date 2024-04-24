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

// Set up the viewport sizes.
$viewport_width = $attributes['viewportWidth'] ?? 1200;
$viewport_height = $attributes['viewportHeight'] ?? 0;
$fullpage = isset( $attributes['fullPage'] ) && $attributes['fullPage'];

// Multiply by 2 for hiDPI-ready sizes.
$width = isset( $attributes['width'] ) ? $attributes['width'] * 2 : 800;

$mshots_args = array(
	'w' => $width,
	'vpw' => $viewport_width,
);
if ( $fullpage ) {
	// `screen_height` is the max height of a screenshot, image can be smaller.
	$mshots_args['screen_height'] = $viewport_height ? $viewport_height : 3600;
	$mshots_args['vph'] = 300; // Smaller than the vast majority of patterns to avoid whitespace.
} else {
	// `vph` is the fixed height of the screenshot (image size will be scaled by w/vpw).
	$mshots_args['vph'] = $viewport_height ? $viewport_height : 900;
}

$cache_key = '20240423'; // To break out of cached image.
$view_url = add_query_arg( 'v', $cache_key, $view_url );
$url = add_query_arg( $mshots_args, 'https://s0.wp.com/mshots/v1/' . urlencode( $view_url ) );

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
