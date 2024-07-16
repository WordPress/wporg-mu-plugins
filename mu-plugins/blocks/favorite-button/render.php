<?php
/**
 * Output the favorite button block.
 *
 * @package wporg
 */

namespace WordPressdotorg\MU_Plugins\Favorite_Button_Block;

$settings = get_block_settings( $block->context['postId'] );
if ( ! $settings ) {
	return '';
}

$user_id = get_current_user_id();
$show_count = $attributes['showCount'] ?? false;
$variant = $attributes['variant'] ?? 'default';

if ( ! $user_id && ! $show_count ) {
	return '';
}

// Manually enqueue this script, so that it's available for the interactivity view script.
wp_enqueue_script( 'wp-api-fetch' );
wp_enqueue_script( 'wp-a11y' );

$is_favorite = $settings['is_favorite'];

$classes = array(
	$is_favorite ? 'is-favorite' : '',
	( 'small' === $variant ) ? 'is-variant-small' : '',
);
$classes = implode( ' ', array_filter( $classes ) );

$labels = array(
	'add' => __( 'Add to favorites', 'wporg' ),
	'remove' => __( 'Remove from favorites', 'wporg' ),
	'favorited' => __( 'Favorited', 'wporg' ),
	'unfavorited' => __( 'Removed from favorites', 'wporg' ),
	// translators: %s: number of users who favorited this item.
	'screenReader' => __( 'Favorited %s times', 'wporg' ),
);

$sr_label = sprintf(
	// translators: %s: number of users who favorited this item.
	_n( 'Favorited %s time', 'Favorited %s times', $settings['count'], 'wporg' ),
	$settings['count']
);

// Initial state to pass to Interactivity API.
$init_state = [
	'id' => $block->context['postId'],
	'count' => $settings['count'],
	'isFavorite' => $is_favorite,
	'label' => $labels,
];
$encoded_state = wp_json_encode( $init_state );

?>
<div
	<?php echo get_block_wrapper_attributes( [ 'class' => $classes ] ); // phpcs:ignore ?>
	data-wp-interactive="wporg/favorite-button"
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
	data-wp-class--is-favorite="context.isFavorite"
	data-wp-class--is-loaded="context.id"
>
	<?php if ( $user_id ) : ?>
	<button
		class="wporg-favorite-button__button"
		disabled="disabled"
		data-wp-bind--disabled="!context.id"
		data-wp-on--click="actions.triggerAction"
	>
	<?php else : ?>
	<span class="wporg-favorite-button__button">
	<?php endif; ?>

		<svg data-wp-bind--hidden="!context.isFavorite" class="is-heart-filled" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" aria-hidden="true" focusable="false">
			<path d="M12 19.5c-2.213-1.953-4.61-3.882-6.394-6.247-.503-.667-.855-1.28-1.056-1.84a5.06 5.06 0 0 1-.3-1.717c0-1.195.407-2.193 1.22-2.994C6.285 5.9 7.299 5.5 8.513 5.5c.672 0 1.312.14 1.919.42.607.28 1.13.674 1.569 1.182A4.588 4.588 0 0 1 15.488 5.5c1.214 0 2.228.4 3.041 1.202.814.8 1.221 1.799 1.221 2.994 0 .585-.1 1.157-.3 1.717-.2.56-.552 1.173-1.056 1.84-1.784 2.365-4.18 4.294-6.394 6.247Z"/>
		</svg>
		<svg data-wp-bind--hidden="context.isFavorite" class="is-heart-outline" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" aria-hidden="true" focusable="false">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M10.967 6.21c.385.243.729.54 1.033.892A4.601 4.601 0 0 1 15.488 5.5c1.214 0 2.228.4 3.041 1.202.814.8 1.221 1.799 1.221 2.994 0 .585-.1 1.157-.3 1.717-.2.56-.552 1.173-1.056 1.84-1.5 1.988-3.431 3.667-5.323 5.312-.36.312-.718.623-1.071.935-.353-.312-.711-.623-1.07-.935-1.893-1.645-3.824-3.325-5.324-5.312-.503-.667-.855-1.28-1.056-1.84a5.06 5.06 0 0 1-.3-1.717c0-1.195.407-2.193 1.22-2.994C6.285 5.9 7.299 5.5 8.513 5.5c.672 0 1.312.14 1.919.42.186.086.365.183.536.29ZM12 17.507l.116-.1c1.892-1.647 3.684-3.207 5.08-5.057.447-.592.708-1.07.841-1.443.143-.4.213-.8.213-1.21 0-.815-.26-1.422-.773-1.926-.514-.507-1.142-.771-1.99-.771a3.088 3.088 0 0 0-2.352 1.082L12 9.397l-1.135-1.315A3.088 3.088 0 0 0 8.512 7c-.847 0-1.475.264-1.99.77-.512.505-.772 1.112-.772 1.926 0 .41.07.812.213 1.211.133.372.394.851.84 1.443 1.397 1.85 3.189 3.41 5.081 5.056l.116.101Z"/>
		</svg>

		<?php if ( $show_count ) : ?>
		<span class="wporg-favorite-button__count">
			<span class="screen-reader-text" data-wp-text="state.labelScreenReader">
				<?php echo esc_html( $sr_label ); ?>
			</span>
			<span aria-hidden="true" data-wp-text="state.labelCount"><?php echo esc_html( $settings['count'] ); ?></span>
		</span>
		<?php endif; ?>

		<?php if ( $user_id ) : ?>
		<span class="wporg-favorite-button__label screen-reader-text" data-wp-text="state.labelAction">
			<?php echo esc_html( $is_favorite ? $labels['remove'] : $labels['add'] ); ?>
		</span>
		<?php endif; ?>

	<?php if ( $user_id ) : ?>
	</button>
	<?php else : ?>
	</span>
	<?php endif; ?>
</div>
