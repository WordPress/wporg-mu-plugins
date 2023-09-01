<?php
$tags = get_terms(
	array(
		'taxonomy' => 'post_tag',
		'orderby' => 'name',
	)
);
$categories = get_terms(
	array(
		'taxonomy' => 'category',
		'orderby' => 'name',
	)
);
$flavors = get_terms(
	array(
		'taxonomy' => 'flavor',
		'orderby' => 'name',
	)
);

$options = array(
	array(
		'label' => 'Popular tags <span>0</span>',
		'key' => 'tag',
		'options' => array_combine( wp_list_pluck( $tags, 'slug' ), wp_list_pluck( $tags, 'name' ) ),
	),
	array(
		'label' => 'Category',
		'key' => 'cat',
		'options' => array_combine( wp_list_pluck( $categories, 'term_id' ), wp_list_pluck( $categories, 'name' ) ),
	),
	array(
		'label' => 'Flavors',
		'key' => 'flavor',
		'options' => array_combine( wp_list_pluck( $flavors, 'slug' ), wp_list_pluck( $flavors, 'name' ) ),
	),
);
$keys = wp_list_pluck( $options, 'key' );

$init_state = [ 'isOpen' => array_fill_keys( $keys, false ) ];
$encoded_state = wp_json_encode( [ 'wporg' => [ 'query-filter' => $init_state ] ] );

$current_path = '';
if ( isset( $_SERVER['REQUEST_URI'] ) ) {
	$current_path = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
}
$form_url = site_url( $current_path );
?>

<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>
	data-wp-interactive
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
>
	<?php
	foreach ( $options as $i => $filter ) :
		$html_id = "filter-{$filter['key']}";
		?>
	<div class="wporg-query-filter">
		<button
			class="wporg-query-filter__toggle"
			data-wp-class--is-active="context.wporg.query-filter.isOpen.<?php echo esc_attr( $filter['key'] ); ?>"
			data-wp-on--click="actions.wporg.query-filter.toggle"
			data-wporg-modal-target="<?php echo esc_attr( $filter['key'] ); ?>"
			data-wp-bind--aria-expanded="context.wporg.query-filter.isOpen.<?php echo esc_attr( $filter['key'] ); ?>"
			aria-controls="<?php echo esc_attr( $html_id ); ?>"
		><?php echo $filter['label']; // phpcs:ignore ?></button>

		<div
			class="wporg-query-filter__modal"
			id="<?php echo esc_attr( $html_id ); ?>"
			data-wp-bind--hidden="!context.wporg.query-filter.isOpen.<?php echo esc_attr( $filter['key'] ); ?>"
		>
			<form action="<?php echo esc_attr( $form_url ); ?>">
				<div class="wporg-query-filter__modal-content">
					<?php foreach ( $filter['options'] as $value => $label ) : ?>
					<div class="wporg-query-filter__option">
						<input type="checkbox" name="<?php echo esc_attr( $filter['key'] ); ?>[]" value="<?php echo esc_attr( $value ); ?>" id="<?php echo esc_attr( $html_id . '-' . $value ); ?>" />
						<label for="<?php echo esc_attr( $html_id . '-' . $value ); ?>"><?php echo esc_html( $label ); ?></label>
					</div>
					<?php endforeach; ?>
				</div>

				<div class="wporg-query-filter__modal-actions">
					<input type="reset" value="<?php esc_attr_e( 'Clear', 'wporg' ); ?>" />
					<input type="submit" value="<?php esc_html_e( 'Apply', 'wporg' ); ?>" />
				</div> <!-- /.wporg-query-filter__actions -->
			</form>
		</div> <!-- /.wporg-query-filter__modal -->
	</div> <!-- /.wporg-query-filter -->
	<?php endforeach; ?>
</div>
