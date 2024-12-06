<?php
/**
 * Render the modal.
 */

$attributes['label'] = $attributes['label'] ?: __( 'Open modal', 'wporg' );

// Initial state to pass to Interactivity API.
$init_state = [
	'isOpen' => false,
];

// Set up a unique ID for this modal.
$html_id = wp_unique_id( 'modal-' );

?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>
	data-wp-interactive="wporg/modal"
	data-wp-watch="callbacks.init"
	data-wp-on--keydown="actions.handleKeydown"
	data-wp-class--is-modal-open="context.isOpen"
	<?php echo wp_interactivity_data_wp_context( $init_state ); // phpcs:ignore ?>
>
	<div class="wp-block-buttons">
		<div class="wp-block-button">
		<?php if ( isset( $attributes['href'] ) ) : ?>
			<a
				href="<?php echo esc_attr( $attributes['href'] ); ?>"
				download
				class="wporg-modal__toggle wp-block-button__link"
				data-wp-on--click="actions.toggle"
				data-wp-bind--aria-expanded="context.isOpen"
				aria-controls="<?php echo esc_attr( $html_id ); ?>"
			><?php echo wp_kses_post( $attributes['label'] ); ?></a>
		<?php else : ?>
			<button
				class="wporg-modal__toggle wp-block-button__link"
				data-wp-on--click="actions.toggle"
				data-wp-bind--aria-expanded="context.isOpen"
				aria-controls="<?php echo esc_attr( $html_id ); ?>"
			><?php echo wp_kses_post( $attributes['label'] ); ?></button>
		<?php endif; ?>
		</div>
	</div>

	<div
		class="wporg-modal__modal-backdrop"
		data-wp-bind--hidden="!context.isOpen"
		data-wp-on--click="actions.clickBackdrop"
	>
		<div
			class="wporg-modal__modal"
			id="<?php echo esc_attr( $html_id ); ?>"
			data-wp-bind--hidden="!context.isOpen"
		>
			<button
				class="wporg-modal__modal-close"
				data-wp-on--click="actions.close"
				aria-label="<?php esc_attr_e( 'Close', 'wporg' ); ?>"
			></button>

			<div class="wporg-modal__modal-content">
				<?php echo wp_kses_post( $content ); ?>
			</div>
		</div> <!-- /.wporg-modal__modal -->
	</div> <!-- /.wporg-modal__modal-backdrop -->
</div> <!-- /.wporg-modal -->
