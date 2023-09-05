<?php
/**
 * Render the query filter.
 */

/**
 * TBD
 *
 * @param string   $options Array of options in format [tbd].
 * @param WP_Block $block   The current block being rendered.
 */
$filter = apply_filters( "wporg_query_filter_{$attributes['key']}", array(), $block );

$init_state = [
	'isOpen' => false,
	'hasHover' => false,
];
$encoded_state = wp_json_encode( [ 'wporg' => [ 'queryFilter' => $init_state ] ] );

// Use the current URL, including query parameters, to build up form action.
$current_path = '';
if ( isset( $_SERVER['REQUEST_URI'] ) ) {
	$current_path = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
}
$form_url = site_url( $current_path );
$has_filter_class = count( $filter['selected'] ) ? 'has-filter-applied' : 'has-no-filter-applied';

$html_id = "filter-{$filter['key']}";
?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>
	data-wp-interactive
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
	data-wp-on--focusout="actions.wporg.queryFilter.handleFocusout"
	data-wp-on--mouseenter="actions.wporg.queryFilter.handleMouseEnter"
	data-wp-on--mouseleave="actions.wporg.queryFilter.handleMouseLeave"
	data-wp-effect="effects.wporg.queryFilter.init"
>
	<button
		class="wporg-query-filter__toggle <?php echo esc_attr( $has_filter_class ); ?>"
		data-wp-class--is-active="context.wporg.queryFilter.isOpen"
		data-wp-on--click="actions.wporg.queryFilter.toggle"
		data-wp-bind--aria-expanded="context.wporg.queryFilter.isOpen"
		aria-controls="<?php echo esc_attr( $html_id ); ?>"
	><?php echo wp_kses_post( $filter['label'] ); ?></button>

	<div
		class="wporg-query-filter__modal"
		id="<?php echo esc_attr( $html_id ); ?>"
		data-wp-bind--hidden="!context.wporg.queryFilter.isOpen"
		data-wp-effect="effects.wporg.queryFilter.focusFirstElement"
		data-wp-on--keydown="actions.wporg.queryFilter.handleKeydown"
	>
		<form action="<?php echo esc_attr( $form_url ); ?>">
			<div class="wporg-query-filter__modal-content">
				<?php foreach ( $filter['options'] as $value => $label ) : ?>
				<div class="wporg-query-filter__option">
					<input
						type="checkbox"
						name="<?php echo esc_attr( $filter['key'] ); ?>[]"
						value="<?php echo esc_attr( $value ); ?>"
						id="<?php echo esc_attr( $html_id . '-' . $value ); ?>"
						<?php checked( in_array( $value, $filter['selected'] ) ); ?>
					/>
					<label for="<?php echo esc_attr( $html_id . '-' . $value ); ?>"><?php echo esc_html( $label ); ?></label>
				</div>
				<?php endforeach; ?>
			</div>

			<?php
			/**
			 * Fires inside the filter form, right before action buttons.
			 *
			 * @param string   $key   The key for the current filter.
			 * @param WP_Block $block The current block being rendered.
			 */
			do_action( 'wporg_query_filter_in_form', $filter['key'], $block );
			?>

			<div class="wporg-query-filter__modal-actions">
				<input type="reset" value="<?php esc_attr_e( 'Reset', 'wporg' ); ?>" />
				<input type="submit" value="<?php esc_html_e( 'Apply', 'wporg' ); ?>" />
			</div> <!-- /.wporg-query-filter__actions -->
		</form>
	</div> <!-- /.wporg-query-filter__modal -->
</div> <!-- /.wporg-query-filter -->
